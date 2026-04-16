@extends('layouts.modern-admin')

@section('title', 'Stock Adjustment History')

@section('page_title', 'Stock Adjustment History')

@section('header_actions')
    <a href="{{ route('products.stock-adjustment') }}" class="btn modern-btn modern-btn-outline">
        <i class="fas fa-balance-scale"></i> Stock Adjustment
    </a>
    <a href="{{ route('products.index') }}" class="btn modern-btn modern-btn-outline">
        <i class="fas fa-arrow-left"></i> Back to Products
    </a>
@stop

@section('page_content')
    <div class="card modern-card">
        <div class="card-header modern-header">
            <div class="header-content">
                <div class="header-title">
                    <i class="fas fa-history header-icon"></i>
                    <h3 class="card-title">Adjusted Products History</h3>
                </div>
            </div>
        </div>

        <div class="card-body modern-card-body">
            <div class="alert alert-info mb-4">
                <i class="fas fa-info-circle mr-2"></i>
                This log shows every saved stock adjustment with before/after quantity and who made the change.
            </div>

            <div class="filter-section mb-4">
                <div class="row">
                    <div class="col-md-2">
                        <label for="fromDateFilter">From Date</label>
                        <input type="date" id="fromDateFilter" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label for="toDateFilter">To Date</label>
                        <input type="date" id="toDateFilter" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label for="companyFilter">Company</label>
                        <select id="companyFilter" class="form-control">
                            <option value="">All Companies</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="categoryFilter">Category</label>
                        <select id="categoryFilter" class="form-control">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if($godowns->isNotEmpty())
                        <div class="col-md-2">
                            <label for="godownFilter">Godown</label>
                            <select id="godownFilter" class="form-control">
                                <option value="">All Godowns</option>
                                @foreach($godowns as $godown)
                                    <option value="{{ $godown->id }}">{{ $godown->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="{{ $godowns->isNotEmpty() ? 'col-md-2' : 'col-md-4' }}">
                        <label for="nameFilter">Product Name</label>
                        <input type="text" id="nameFilter" class="form-control" placeholder="Search product...">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-12 d-flex align-items-end">
                        <button type="button" class="btn modern-btn modern-btn-primary mr-2" onclick="applyFilters()">
                            <i class="fas fa-filter"></i> Apply
                        </button>
                        <button type="button" class="btn modern-btn modern-btn-outline" onclick="resetFilters()">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover" id="stockAdjustmentHistoryTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date & Time</th>
                            <th>Product</th>
                            <th>Company</th>
                            <th>Category</th>
                            <th>Godown</th>
                            <th class="text-right">System Stock</th>
                            <th class="text-right">Physical Count</th>
                            <th class="text-right">Difference</th>
                            <th>Adjusted By</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
@stop

@section('additional_css')
    <link href="{{ asset('css/modern-admin.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    <style>
        .filter-section {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.02) 0%, rgba(139, 92, 246, 0.02) 100%);
            border: 1px solid rgba(99, 102, 241, 0.1);
            border-radius: 12px;
            padding: 20px;
        }

        #stockAdjustmentHistoryTable thead th {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(139, 92, 246, 0.05) 100%);
            font-weight: 600;
            font-size: 13px;
        }

        #stockAdjustmentHistoryTable tbody td {
            vertical-align: middle;
        }

        .difference-positive {
            color: #10b981;
            font-weight: 600;
        }

        .difference-negative {
            color: #ef4444;
            font-weight: 600;
        }

        .difference-zero {
            color: #6b7280;
            font-weight: 600;
        }
    </style>
@stop

@section('additional_js')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
    <script src="{{ asset('js/modern-admin.js') }}"></script>
    <script>
        let dataTable;

        $(document).ready(function() {
            dataTable = $('#stockAdjustmentHistoryTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('products.stock-adjustment.history.data') }}",
                    data: function(d) {
                        d.company_id = $('#companyFilter').val();
                        d.category_id = $('#categoryFilter').val();
                        d.godown_id = $('#godownFilter').length ? $('#godownFilter').val() : '';
                        d.name = $('#nameFilter').val();
                        d.from_date = $('#fromDateFilter').val();
                        d.to_date = $('#toDateFilter').val();
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'adjusted_at_formatted', name: 'adjusted_at' },
                    { data: 'product_name', name: 'product_name', orderable: false, searchable: false },
                    { data: 'company_name', name: 'company_name', orderable: false, searchable: false },
                    { data: 'category_name', name: 'category_name', orderable: false, searchable: false },
                    { data: 'godown_name', name: 'godown_name', orderable: false, searchable: false },
                    {
                        data: 'system_stock',
                        name: 'system_stock',
                        className: 'text-right',
                        render: function(data) {
                            return parseFloat(data || 0).toFixed(2);
                        }
                    },
                    {
                        data: 'physical_count',
                        name: 'physical_count',
                        className: 'text-right',
                        render: function(data) {
                            return parseFloat(data || 0).toFixed(2);
                        }
                    },
                    {
                        data: 'difference',
                        name: 'difference',
                        className: 'text-right',
                        render: function(data) {
                            const value = parseFloat(data || 0);
                            const cssClass = value > 0 ? 'difference-positive' : (value < 0 ? 'difference-negative' : 'difference-zero');
                            const text = (value >= 0 ? '+' : '') + value.toFixed(2);
                            return `<span class="${cssClass}">${text}</span>`;
                        }
                    },
                    { data: 'adjusted_by_name', name: 'adjusted_by_name', orderable: false, searchable: false }
                ],
                order: [[1, 'desc']],
                pageLength: 25
            });
        });

        function applyFilters() {
            dataTable.ajax.reload();
        }

        function resetFilters() {
            $('#fromDateFilter').val('');
            $('#toDateFilter').val('');
            $('#companyFilter').val('');
            $('#categoryFilter').val('');
            $('#godownFilter').val('');
            $('#nameFilter').val('');
            dataTable.ajax.reload();
        }
    </script>
@stop
