<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Invoice;
use App\Models\Purchase;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\ProductReturn;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use App\Models\PayableTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Yajra\DataTables\Facades\DataTables;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CashFlowReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:invoice-edit|invoice-delete', ['only' => [
            'index', 'getSalesReport', 'getCollectionReport', 'getPurchaseReport',
            'getCashFlowSummary', 'exportSalesReport', 'exportCollectionReport',
            'exportPurchaseReport', 'exportCashFlowReport', 'getGrossProfitReport', 'getGrossProfitSummary'
        ]]);
    }

    /**
     * Main cash flow reports dashboard
     */
    public function index()
    {
        if (config('perf.enabled')) {
            $customers = Cache::remember('lookup.customers', config('perf.ttl.lookup_lists'), function () {
                return Customer::orderBy('name')->get();
            });
        } else {
            $customers = Customer::orderBy('name')->get();
        }

        // Get current month data for dashboard cards
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        if (config('perf.enabled')) {
            $cacheKey = 'cashflow.dashboard.' . $startOfMonth->toDateString() . '.' . $endOfMonth->toDateString();
            $dashboardData = Cache::remember($cacheKey, config('perf.ttl.cashflow_summary'), function () use ($startOfMonth, $endOfMonth) {
                return $this->getDashboardSummary($startOfMonth, $endOfMonth);
            });
        } else {
            $dashboardData = $this->getDashboardSummary($startOfMonth, $endOfMonth);
        }

        return view('reports.cash-flow.index', compact('customers', 'dashboardData'));
    }

    /**
     * Get sales report data via AJAX
     */
    public function getSalesReport(Request $request)
    {
        if ($request->ajax()) {
            $query = Invoice::with('customer');

            $this->applyDateFilters($query, $request);
            $this->applyCommonFilters($query, $request);

            // Additional sales-specific filters
            if ($request->has('payment_status') && !empty($request->payment_status)) {
                $query->where('payment_status', $request->payment_status);
            }

            if ($request->has('delivery_status') && !empty($request->delivery_status)) {
                $query->where('delivery_status', $request->delivery_status);
            }

            if ($request->has('invoice_type') && !empty($request->invoice_type)) {
                $query->where('invoice_type', $request->invoice_type);
            }

            // Calculate summary for filtered data
            $summary = $this->calculateSalesSummary($query);

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('customer_name', function($row) {
                    return $row->customer->name ?? 'N/A';
                })
                ->addColumn('customer_phone', function($row) {
                    return $row->customer->phone ?? 'N/A';
                })
                ->editColumn('invoice_date', function($row) {
                    return $row->invoice_date->format('d M, Y');
                })
                ->editColumn('total', function($row) {
                    return number_format($row->total, 2);
                })
                ->editColumn('paid_amount', function($row) {
                    return number_format($row->paid_amount, 2);
                })
                ->editColumn('due_amount', function($row) {
                    return number_format($row->due_amount, 2);
                })
                ->addColumn('payment_status_badge', function($row) {
                    $badgeClass = $row->payment_status == 'paid' ? 'success' :
                                 ($row->payment_status == 'partial' ? 'warning' : 'danger');
                    return '<span class="badge badge-' . $badgeClass . '">' . ucfirst($row->payment_status) . '</span>';
                })
                ->addColumn('delivery_status_badge', function($row) {
                    $badgeClass = $row->delivery_status == 'delivered' ? 'success' :
                                 ($row->delivery_status == 'partial' ? 'warning' : 'info');
                    return '<span class="badge badge-' . $badgeClass . '">' . ucfirst($row->delivery_status) . '</span>';
                })
                ->rawColumns(['payment_status_badge', 'delivery_status_badge'])
                ->with(['summary' => $summary])
                ->make(true);
        }
    }

    /**
     * Sales insights (hourly, footfall, basket size, category/company/type breakdown)
     */
    public function getSalesInsights(Request $request)
    {
        $timezone = config('app.timezone') ?: 'UTC';
        $startDate = $request->get('start_date')
            ? Carbon::parse($request->get('start_date'), $timezone)->startOfDay()
            : Carbon::now($timezone)->startOfDay();
        $endDate = $request->get('end_date')
            ? Carbon::parse($request->get('end_date'), $timezone)->endOfDay()
            : Carbon::now($timezone)->endOfDay();
        $invoiceType = $request->get('invoice_type');

        if (config('perf.enabled')) {
            $cacheKey = sprintf(
                'cashflow.sales-insights.v2.%s.%s.%s.%s',
                $startDate->toDateString(),
                $endDate->toDateString(),
                $invoiceType ?: 'all',
                $timezone
            );
            $ttl = config('perf.ttl.sales_insights');
            $data = Cache::remember($cacheKey, $ttl, function () use ($startDate, $endDate, $invoiceType, $timezone) {
                return $this->buildSalesInsightsData($startDate, $endDate, $invoiceType, $timezone);
            });
            return response()->json($data);
        }

        return response()->json(
            $this->buildSalesInsightsData($startDate, $endDate, $invoiceType, $timezone)
        );
    }

    private function buildSalesInsightsData(Carbon $startDate, Carbon $endDate, ?string $invoiceType, string $timezone)
    {
        $rangeStart = $startDate->copy()->setTimezone($timezone)->startOfDay();
        $rangeEnd = $endDate->copy()->setTimezone($timezone)->endOfDay();

        $invoiceQuery = Invoice::query()
            ->whereBetween('invoice_date', [$startDate->toDateString(), $endDate->toDateString()]);

        if ($invoiceType) {
            $invoiceQuery->where('invoice_type', $invoiceType);
        }

        $totalSales = (clone $invoiceQuery)->sum('total');
        $invoiceCount = (clone $invoiceQuery)->count();
        $avgBasket = $invoiceCount > 0 ? ($totalSales / $invoiceCount) : 0;

        // Hourly sales (9 AM - 10 PM) grouped by created_at hour, filtered by local date range
        // Values before 09:00 roll into 09:00; values after 22:00 roll into 22:00.
        $hourlyRows = Invoice::query()
            ->whereBetween('invoice_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->when($invoiceType, fn($q) => $q->where('invoice_type', $invoiceType))
            ->get(['total', 'created_at'])
            ->filter(function ($invoice) use ($rangeStart, $rangeEnd, $timezone) {
                if (!$invoice->created_at) {
                    return false;
                }

                $createdAtLocal = $invoice->created_at->copy()->setTimezone($timezone);

                return $createdAtLocal->greaterThanOrEqualTo($rangeStart)
                    && $createdAtLocal->lessThanOrEqualTo($rangeEnd);
            })
            ->groupBy(function ($invoice) use ($timezone) {
                $hour = $invoice->created_at->copy()->setTimezone($timezone)->hour;

                if ($hour < 9) {
                    return 9;
                }

                if ($hour > 22) {
                    return 22;
                }

                return $hour;
            })
            ->map(function ($rows) {
                return (float) $rows->sum(function ($row) {
                    return (float) $row->total;
                });
            })
            ->toArray();

        $hours = [];
        $hourlyTotals = [];
        for ($hour = 9; $hour <= 22; $hour++) {
            $hours[] = Carbon::createFromTime($hour, 0, 0, $timezone)->format('g A');
            $hourlyTotals[] = (float) ($hourlyRows[$hour] ?? 0);
        }

        $itemsBase = InvoiceItem::query()
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->join('products', 'products.id', '=', 'invoice_items.product_id')
            ->whereBetween('invoices.invoice_date', [$startDate->toDateString(), $endDate->toDateString()]);

        if ($invoiceType) {
            $itemsBase->where('invoices.invoice_type', $invoiceType);
        }

        // Category wise
        $categoryRows = (clone $itemsBase)
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->selectRaw('categories.id as id, COALESCE(categories.name, "Unassigned") as name, SUM(invoice_items.total) as total_amount, SUM(invoice_items.quantity) as quantity')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_amount')
            ->limit(10)
            ->get();

        // Company wise
        $companyRows = (clone $itemsBase)
            ->leftJoin('companies', 'companies.id', '=', 'products.company_id')
            ->selectRaw('companies.id as id, COALESCE(companies.name, "Unassigned") as name, SUM(invoice_items.total) as total_amount, SUM(invoice_items.quantity) as quantity')
            ->groupBy('companies.id', 'companies.name')
            ->orderByDesc('total_amount')
            ->limit(10)
            ->get();

        // Invoice type wise
        $typeRows = Invoice::query()
            ->whereBetween('invoice_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->selectRaw('invoice_type as type, COUNT(*) as invoices, SUM(total) as total_amount')
            ->groupBy('invoice_type')
            ->get();

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'footfall' => $invoiceCount,
            'basket_size' => $avgBasket,
            'total_sales' => $totalSales,
            'hourly' => [
                'labels' => $hours,
                'totals' => $hourlyTotals,
            ],
            'categories' => $categoryRows,
            'companies' => $companyRows,
            'types' => $typeRows,
        ];
    }

    /**
     * Get collection report data via AJAX - FIXED
     */
    public function getCollectionReport(Request $request)
    {
        if ($request->ajax()) {
            $query = Transaction::with('customer')
                ->where('type', 'debit'); // Only payment collections (money in)

            $this->applyDateFilters($query, $request, 'created_at');
            $this->applyCommonFilters($query, $request);

            // Additional collection-specific filters
            if ($request->has('payment_method') && !empty($request->payment_method)) {
                $query->where('method', $request->payment_method);
            }

            if ($request->has('min_amount') && !empty($request->min_amount)) {
                $query->where('amount', '>=', $request->min_amount);
            }

            if ($request->has('max_amount') && !empty($request->max_amount)) {
                $query->where('amount', '<=', $request->max_amount);
            }

            // Calculate summary
            $summary = $this->calculateCollectionSummary($query);

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('customer_name', function($row) {
                    return $row->customer->name ?? 'N/A';
                })
                ->addColumn('customer_phone', function($row) {
                    return $row->customer->phone ?? 'N/A';
                })
                ->editColumn('created_at', function($row) {
                    return $row->created_at->format('d M, Y H:i');
                })
                ->editColumn('amount', function($row) {
                    return number_format($row->amount, 2);
                })
                ->editColumn('discount_amount', function($row) {
                    return number_format($row->discount_amount ?? 0, 2);
                })
                ->addColumn('total_received', function($row) {
                    return number_format($row->amount + ($row->discount_amount ?? 0), 2);
                })
                ->addColumn('method_badge', function($row) {
                    $methods = [
                        'cash' => 'success',
                        'bank' => 'primary',
                        'mobile_bank' => 'info',
                        'cheque' => 'warning'
                    ];
                    $badgeClass = $methods[$row->method] ?? 'secondary';
                    return '<span class="badge badge-' . $badgeClass . '">' . ucfirst(str_replace('_', ' ', $row->method)) . '</span>';
                })
                ->rawColumns(['method_badge'])
                ->with(['summary' => $summary])
                ->make(true);
        }
    }

    /**
     * Get purchase report data via AJAX
     */
    public function getPurchaseReport(Request $request)
    {
        if ($request->ajax()) {
            $query = Purchase::with('company');

            $this->applyDateFilters($query, $request, 'purchase_date');

            if ($request->has('company_id') && !empty($request->company_id)) {
                $query->where('company_id', $request->company_id);
            }

            if ($request->has('min_amount') && !empty($request->min_amount)) {
                $query->where('total_amount', '>=', $request->min_amount);
            }

            if ($request->has('max_amount') && !empty($request->max_amount)) {
                $query->where('total_amount', '<=', $request->max_amount);
            }

            // Calculate summary
            $summary = $this->calculatePurchaseSummary($query);

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('company_name', function($row) {
                    return $row->company->name ?? 'N/A';
                })
                ->editColumn('purchase_date', function($row) {
                    return Carbon::parse($row->purchase_date)->format('d M, Y');
                })
                ->editColumn('total_amount', function($row) {
                    return number_format($row->total_amount, 2);
                })
                ->addColumn('items_count', function($row) {
                    return $row->items ? $row->items->count() : 0;
                })
                ->with(['summary' => $summary])
                ->make(true);
        }
    }

    /**
     * Get comprehensive cash flow summary
     */
    public function getCashFlowSummary(Request $request)
    {
        $period = $request->get('period', 'month');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        // Determine date range based on period
        [$start, $end] = $this->getDateRange($period, $startDate, $endDate);

        if (config('perf.enabled')) {
            $cacheKey = sprintf(
                'cashflow.summary.%s.%s.%s',
                $period,
                $start->toDateString(),
                $end->toDateString()
            );
            $data = Cache::remember($cacheKey, config('perf.ttl.cashflow_summary'), function () use ($start, $end, $period) {
                return $this->buildCashFlowSummaryData($start, $end, $period);
            });
            return response()->json($data);
        }

        return response()->json($this->buildCashFlowSummaryData($start, $end, $period));
    }

    private function buildCashFlowSummaryData(Carbon $start, Carbon $end, string $period)
    {

        // Sales data
        $salesQuery = Invoice::whereBetween('invoice_date', [$start->toDateString(), $end->toDateString()]);
        $totalSales = (clone $salesQuery)->sum('total');
        $totalCollected = (clone $salesQuery)->sum('paid_amount');
        $totalDue = (clone $salesQuery)->sum('due_amount');
        $salesCount = (clone $salesQuery)->count();

        // Collection data (payments received)
        $collectionsQuery = Transaction::where('type', 'debit')
            ->whereBetween('created_at', [$start, $end]);
        $totalCollections = (clone $collectionsQuery)->sum('amount');
        $totalDiscounts = (clone $collectionsQuery)->sum('discount_amount');
        $collectionsCount = (clone $collectionsQuery)->count();

        // Purchase data
        $purchasesQuery = Purchase::whereBetween('purchase_date', [$start->toDateString(), $end->toDateString()]);
        $totalPurchases = (clone $purchasesQuery)->sum('total_amount');
        $purchasesCount = (clone $purchasesQuery)->count();

        // Returns data (if exists)
        $totalReturns = 0;
        $returnsCount = 0;
        if (class_exists('App\Models\ProductReturn')) {
            $returnsQuery = ProductReturn::whereBetween('return_date', [$start->toDateString(), $end->toDateString()]);
            $totalReturns = (clone $returnsQuery)->sum('total');
            $returnsCount = (clone $returnsQuery)->count();
        }

        // Payable transactions (expenses) - if exists
        $totalExpenses = 0;
        $totalPayableIncome = 0;
        if (class_exists('App\Models\PayableTransaction')) {
            $payableQuery = PayableTransaction::whereBetween('transaction_date', [$start, $end]);
            $totalExpenses = (clone $payableQuery)->where('transaction_type', 'cash_out')->sum('amount');
            $totalPayableIncome = (clone $payableQuery)->where('transaction_type', 'cash_in')->sum('amount');
        }

        // Net cash flow calculation
        $totalInflows = $totalCollections + $totalPayableIncome + $totalReturns;
        $totalOutflows = $totalPurchases + $totalExpenses;
        $netCashFlow = $totalInflows - $totalOutflows;

        // Daily breakdown for charts
        $dailyData = $this->getDailyBreakdown($start, $end);
        $gpData = $this->calculateGrossProfit($start, $end);
        return [
            'period' => $period,
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'sales' => [
                'total_amount' => $totalSales,
                'collected_amount' => $totalCollected,
                'due_amount' => $totalDue,
                'count' => $salesCount,
                'average_sale' => $salesCount > 0 ? $totalSales / $salesCount : 0
            ],
            'collections' => [
                'total_amount' => $totalCollections,
                'discount_amount' => $totalDiscounts,
                'net_amount' => $totalCollections + $totalDiscounts,
                'count' => $collectionsCount,
                'average_collection' => $collectionsCount > 0 ? $totalCollections / $collectionsCount : 0
            ],
            'purchases' => [
                'total_amount' => $totalPurchases,
                'count' => $purchasesCount,
                'average_purchase' => $purchasesCount > 0 ? $totalPurchases / $purchasesCount : 0
            ],
            'returns' => [
                'total_amount' => $totalReturns,
                'count' => $returnsCount
            ],
            'expenses' => [
                'total_outflows' => $totalExpenses,
                'total_inflows' => $totalPayableIncome,
                'net_payable' => $totalExpenses - $totalPayableIncome
            ],
            'cash_flow' => [
                'total_inflows' => $totalInflows,
                'total_outflows' => $totalOutflows,
                'net_cash_flow' => $netCashFlow,
                'inflow_percentage' => $totalInflows > 0 ? ($totalCollections / $totalInflows) * 100 : 0
            ],
            'daily_data' => $dailyData,
            'gross_profit' => $gpData,
        ];
    }

    /**
     * Export sales report to Excel
     */
    public function exportSalesReport(Request $request)
    {
        $query = Invoice::with('customer');
        $this->applyDateFilters($query, $request);
        $this->applyCommonFilters($query, $request);

        $invoices = $query->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $headers = [
            'Invoice Number', 'Date', 'Customer', 'Phone', 'Type',
            'Total Amount', 'Paid Amount', 'Due Amount',
            'Payment Status', 'Delivery Status'
        ];

        foreach ($headers as $index => $header) {
            $sheet->setCellValueByColumnAndRow($index + 1, 1, $header);
        }

        // Add data
        $row = 2;
        foreach ($invoices as $invoice) {
            $sheet->setCellValueByColumnAndRow(1, $row, $invoice->invoice_number);
            $sheet->setCellValueByColumnAndRow(2, $row, $invoice->invoice_date->format('Y-m-d'));
            $sheet->setCellValueByColumnAndRow(3, $row, $invoice->customer->name ?? 'N/A');
            $sheet->setCellValueByColumnAndRow(4, $row, $invoice->customer->phone ?? 'N/A');
            $sheet->setCellValueByColumnAndRow(5, $row, ucfirst($invoice->invoice_type));
            $sheet->setCellValueByColumnAndRow(6, $row, $invoice->total);
            $sheet->setCellValueByColumnAndRow(7, $row, $invoice->paid_amount);
            $sheet->setCellValueByColumnAndRow(8, $row, $invoice->due_amount);
            $sheet->setCellValueByColumnAndRow(9, $row, ucfirst($invoice->payment_status));
            $sheet->setCellValueByColumnAndRow(10, $row, ucfirst($invoice->delivery_status));
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'sales_report_' . date('Y_m_d_H_i_s') . '.xlsx';

        $tempFile = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    /**
     * Export collection report to Excel
     */
    public function exportCollectionReport(Request $request)
    {
        $query = Transaction::with('customer')->where('type', 'debit');
        $this->applyDateFilters($query, $request, 'created_at');
        $this->applyCommonFilters($query, $request);

        $transactions = $query->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $headers = [
            'Date', 'Customer', 'Phone', 'Purpose', 'Payment Method',
            'Amount', 'Discount', 'Total Received', 'Reference', 'Notes'
        ];

        foreach ($headers as $index => $header) {
            $sheet->setCellValueByColumnAndRow($index + 1, 1, $header);
        }

        // Add data
        $row = 2;
        foreach ($transactions as $transaction) {
            $sheet->setCellValueByColumnAndRow(1, $row, $transaction->created_at->format('Y-m-d H:i'));
            $sheet->setCellValueByColumnAndRow(2, $row, $transaction->customer->name ?? 'N/A');
            $sheet->setCellValueByColumnAndRow(3, $row, $transaction->customer->phone ?? 'N/A');
            $sheet->setCellValueByColumnAndRow(4, $row, $transaction->purpose);
            $sheet->setCellValueByColumnAndRow(5, $row, ucfirst(str_replace('_', ' ', $transaction->method)));
            $sheet->setCellValueByColumnAndRow(6, $row, $transaction->amount);
            $sheet->setCellValueByColumnAndRow(7, $row, $transaction->discount_amount ?? 0);
            $sheet->setCellValueByColumnAndRow(8, $row, $transaction->amount + ($transaction->discount_amount ?? 0));
            $sheet->setCellValueByColumnAndRow(9, $row, $transaction->reference ?? '');
            $sheet->setCellValueByColumnAndRow(10, $row, $transaction->note ?? '');
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'collection_report_' . date('Y_m_d_H_i_s') . '.xlsx';

        $tempFile = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    /**
     * Export comprehensive cash flow report
     */
    public function exportCashFlowReport(Request $request)
    {
        $period = $request->get('period', 'month');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        [$start, $end] = $this->getDateRange($period, $startDate, $endDate);

        $spreadsheet = new Spreadsheet();

        // Summary Sheet
        $summarySheet = $spreadsheet->getActiveSheet();
        $summarySheet->setTitle('Cash Flow Summary');

        $summaryData = $this->getCashFlowSummary($request)->getData();

        $summarySheet->setCellValue('A1', 'Cash Flow Report');
        $summarySheet->setCellValue('A2', 'Period: ' . $start->format('Y-m-d') . ' to ' . $end->format('Y-m-d'));

        $row = 4;
        $summarySheet->setCellValue('A' . $row, 'SALES SUMMARY');
        $row++;
        $summarySheet->setCellValue('A' . $row, 'Total Sales Amount:');
        $summarySheet->setCellValue('B' . $row, $summaryData->sales->total_amount);
        $row++;
        $summarySheet->setCellValue('A' . $row, 'Collected Amount:');
        $summarySheet->setCellValue('B' . $row, $summaryData->sales->collected_amount);
        $row++;
        $summarySheet->setCellValue('A' . $row, 'Due Amount:');
        $summarySheet->setCellValue('B' . $row, $summaryData->sales->due_amount);

        // Add more summary data...

        $writer = new Xlsx($spreadsheet);
        $filename = 'cash_flow_report_' . date('Y_m_d_H_i_s') . '.xlsx';

        $tempFile = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    // Helper methods
    private function applyDateFilters($query, $request, $dateColumn = 'invoice_date')
    {
        if ($request->has('start_date') && !empty($request->start_date)) {
            $query->whereDate($dateColumn, '>=', $request->start_date);
        }

        if ($request->has('end_date') && !empty($request->end_date)) {
            $query->whereDate($dateColumn, '<=', $request->end_date);
        }
    }

    private function applyCommonFilters($query, $request)
    {
        // Remove customer filter - not needed
        // if ($request->has('customer_id') && !empty($request->customer_id)) {
        //     $query->where('customer_id', $request->customer_id);
        // }
    }

    private function calculateSalesSummary($query)
    {
        $clonedQuery = clone $query;

        return [
            'total_sales' => (clone $clonedQuery)->sum('total'),
            'total_collected' => (clone $clonedQuery)->sum('paid_amount'),
            'total_due' => (clone $clonedQuery)->sum('due_amount'),
            'count' => (clone $clonedQuery)->count(),
            'average_sale' => (clone $clonedQuery)->count() > 0
                ? (clone $clonedQuery)->sum('total') / (clone $clonedQuery)->count()
                : 0
        ];
    }

    private function calculateCollectionSummary($query)
    {
        $clonedQuery = clone $query;

        return [
            'total_amount' => (clone $clonedQuery)->sum('amount'),
            'total_discount' => (clone $clonedQuery)->sum('discount_amount'),
            'count' => (clone $clonedQuery)->count(),
            'average_collection' => (clone $clonedQuery)->count() > 0
                ? (clone $clonedQuery)->sum('amount') / (clone $clonedQuery)->count()
                : 0
        ];
    }

    private function calculatePurchaseSummary($query)
    {
        $clonedQuery = clone $query;

        return [
            'total_amount' => (clone $clonedQuery)->sum('total_amount'),
            'count' => (clone $clonedQuery)->count(),
            'average_purchase' => (clone $clonedQuery)->count() > 0
                ? (clone $clonedQuery)->sum('total_amount') / (clone $clonedQuery)->count()
                : 0
        ];
    }

    private function getDateRange($period, $startDate = null, $endDate = null)
    {
        if ($startDate && $endDate) {
            return [Carbon::parse($startDate), Carbon::parse($endDate)];
        }

        switch ($period) {
            case 'today':
                return [Carbon::today(), Carbon::today()];
            case 'week':
                return [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()];
            case 'month':
                return [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()];
            case 'quarter':
                return [Carbon::now()->startOfQuarter(), Carbon::now()->endOfQuarter()];
            case 'year':
                return [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()];
            default:
                return [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()];
        }
    }

private function getDashboardSummary($start, $end): array
{
    $salesAmount      = Invoice::whereBetween('invoice_date', [$start, $end])->sum('total');
    $collectionsAmount = Transaction::where('type', 'debit')
                            ->whereBetween('created_at', [$start, $end])->sum('amount');
    $purchasesAmount  = Purchase::whereBetween('purchase_date', [$start, $end])->sum('total_amount');

    return [
        'sales_count'         => Invoice::whereBetween('invoice_date', [$start, $end])->count(),
        'sales_amount'        => $salesAmount,
        'collections_amount'  => $collectionsAmount,
        'purchases_amount'    => $purchasesAmount,
        'gross_profit'        => $this->calculateGrossProfit($start, $end),
    ];
}


    private function getDailyBreakdown($start, $end)
    {
        $dailyData = [];
        $current = $start->copy()->startOfDay();
        $endDate = $end->copy()->endOfDay();

        $salesByDate = Invoice::whereBetween('invoice_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('invoice_date as date, SUM(total) as total')
            ->groupBy('invoice_date')
            ->pluck('total', 'date');

        $collectionsByDate = Transaction::where('type', 'debit')
            ->whereBetween('created_at', [$start, $endDate])
            ->selectRaw('DATE(created_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->pluck('total', 'date');

        $purchasesByDate = Purchase::whereBetween('purchase_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('purchase_date as date, SUM(total_amount) as total')
            ->groupBy('purchase_date')
            ->pluck('total', 'date');

        while ($current <= $endDate) {
            $date = $current->format('Y-m-d');

            $dailyData[] = [
                'date' => $date,
                'sales' => (float) ($salesByDate[$date] ?? 0),
                'collections' => (float) ($collectionsByDate[$date] ?? 0),
                'purchases' => (float) ($purchasesByDate[$date] ?? 0)
            ];

            $current->addDay();
        }

        return $dailyData;
    }

    private function calculateGrossProfit(Carbon $start, Carbon $end): array
{
    // Sum of (sell_price - cost_price) × qty  across all items in the period
    $itemProfit = DB::table('invoice_items')
        ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
        ->join('products', 'products.id', '=', 'invoice_items.product_id')
        ->whereNull('invoices.deleted_at')
        ->whereBetween('invoices.invoice_date', [$start->toDateString(), $end->toDateString()])
        ->selectRaw('
            SUM( (invoice_items.unit_price - products.purchase_price) * invoice_items.quantity ) AS gross,
            SUM( invoice_items.total ) AS revenue
        ')
        ->first();

    // Total invoice-level discounts in the period
    $totalDiscount = Invoice::whereNull('deleted_at')
        ->whereBetween('invoice_date', [$start->toDateString(), $end->toDateString()])
        ->sum('discount');

    $grossProfit = (float) ($itemProfit->gross ?? 0) - (float) $totalDiscount;
    $revenue     = (float) ($itemProfit->revenue ?? 0);
    $margin      = $revenue > 0 ? ($grossProfit / $revenue) * 100 : 0;

    return [
        'amount'   => $grossProfit,
        'margin'   => round($margin, 2),
        'revenue'  => $revenue,
        'discount' => (float) $totalDiscount,
    ];
}
public function getGrossProfitReport(Request $request)
{
    abort_unless($request->ajax(), 403);

    // Reusable sub-query for item-level GP aggregation
    $itemSub = DB::table('invoice_items')
        ->join('products', 'products.id', '=', 'invoice_items.product_id')
        ->selectRaw('
            invoice_items.invoice_id,
            SUM((invoice_items.unit_price - products.purchase_price) * invoice_items.quantity) AS item_profit,
            SUM(invoice_items.total) AS item_revenue
        ')
        ->groupBy('invoice_items.invoice_id');

    // ── Helper closure so filters are applied identically to both queries ──
    $applyFilters = function ($q) use ($request) {
        $q->whereNull('invoices.deleted_at');

        if ($request->filled('start_date')) {
            $q->whereDate('invoices.invoice_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $q->whereDate('invoices.invoice_date', '<=', $request->end_date);
        }
        if ($request->filled('invoice_type')) {
            $q->where('invoices.invoice_type', $request->invoice_type);
        }

        return $q;
    };

    // ── Main DataTable query (one row per invoice) ──
    $query = $applyFilters(
        Invoice::query()
            ->join('customers', 'customers.id', '=', 'invoices.customer_id')
            ->leftJoinSub(clone $itemSub, 'gp', 'gp.invoice_id', '=', 'invoices.id')
            ->select([
                'invoices.id',
                'invoices.invoice_number',
                'invoices.invoice_date',
                'invoices.subtotal',
                DB::raw('COALESCE(invoices.discount, 0) AS discount'),
                'invoices.total',
                'customers.name AS customer_name',
                'customers.phone AS customer_phone',
                DB::raw('COALESCE(gp.item_revenue, 0) AS item_revenue'),
                DB::raw('COALESCE(gp.item_profit, 0) AS item_profit'),
                DB::raw('COALESCE(gp.item_profit, 0) - COALESCE(invoices.discount, 0) AS gross_profit'),
            ])
    );

    // ── Totals query (aggregates only — avoids only_full_group_by) ──
    $totals = $applyFilters(
        Invoice::query()
            ->join('customers', 'customers.id', '=', 'invoices.customer_id')
            ->leftJoinSub(clone $itemSub, 'gp', 'gp.invoice_id', '=', 'invoices.id')
    )
    ->selectRaw('
        COUNT(*)                                                                AS invoice_count,
        SUM(COALESCE(gp.item_revenue, 0))                                       AS total_revenue,
        SUM(COALESCE(gp.item_profit, 0))                                        AS total_item_profit,
        SUM(COALESCE(invoices.discount, 0))                                     AS total_discount,
        SUM(COALESCE(gp.item_profit, 0) - COALESCE(invoices.discount, 0))      AS total_gp
    ')
    ->first();

    $totalRevenue = (float) ($totals->total_revenue ?? 0);
    $totalGp      = (float) ($totals->total_gp ?? 0);

    $summary = [
        'invoice_count'     => (int)   ($totals->invoice_count     ?? 0),
        'total_revenue'     => $totalRevenue,
        'total_item_profit' => (float) ($totals->total_item_profit ?? 0),
        'total_discount'    => (float) ($totals->total_discount    ?? 0),
        'total_gp'          => $totalGp,
        'gp_margin'         => $totalRevenue > 0 ? round(($totalGp / $totalRevenue) * 100, 2) : 0,
    ];

    return DataTables::of($query)
        ->addIndexColumn()
        ->editColumn('invoice_date', fn($r) => $r->invoice_date->format('d M, Y'))
        ->editColumn('total',        fn($r) => number_format($r->total, 2))
        ->editColumn('discount',     fn($r) => number_format($r->discount ?? 0, 2))
        ->editColumn('item_profit',  fn($r) => number_format($r->item_profit, 2))
        ->addColumn('gross_profit_fmt', function ($r) {
            $val   = (float) $r->gross_profit;
            $color = $val >= 0 ? 'success' : 'danger';
            return '<span class="text-' . $color . ' font-weight-bold">৳' . number_format($val, 2) . '</span>';
        })
        ->addColumn('gp_margin', function ($r) {
            $revenue = (float) $r->item_revenue;
            $gp      = (float) $r->gross_profit;
            $margin  = $revenue > 0 ? round(($gp / $revenue) * 100, 2) : 0;
            $color   = $margin >= 0 ? 'success' : 'danger';
            return '<span class="badge badge-' . $color . '">' . $margin . '%</span>';
        })
        ->rawColumns(['gross_profit_fmt', 'gp_margin'])
        ->with(['summary' => $summary])
        ->make(true);
}
/**
 * Returns GP summary for the three quick cards (Today, Last Month, Custom).
 * Called via AJAX when user opens the GP tab or changes the custom range.
 */
public function getGrossProfitSummary(Request $request): \Illuminate\Http\JsonResponse
{
    $ranges = [
        'today' => [
            Carbon::today(),
            Carbon::today(),
        ],
        'last_month' => [
            Carbon::now()->subMonth()->startOfMonth(),
            Carbon::now()->subMonth()->endOfMonth(),
        ],
        'custom' => [
            $request->filled('start_date') ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth(),
            $request->filled('end_date')   ? Carbon::parse($request->end_date)   : Carbon::now()->endOfMonth(),
        ],
    ];

    $result = [];
    foreach ($ranges as $key => [$start, $end]) {
        $gp = $this->calculateGrossProfit($start, $end);
        $result[$key] = array_merge($gp, [
            'start' => $start->toDateString(),
            'end'   => $end->toDateString(),
        ]);
    }

    return response()->json($result);
}


}
