<div class="table-responsive">
    <table class="table table-sm mb-0 dalert-table">
        <thead>
            <tr>
                <th>Invoice #</th>
                <th class="d-none d-sm-table-cell">Date</th>
                <th>Customer</th>
                <th>Status</th>
                <th class="text-right">Total</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoices as $inv)
            <tr>
                <td>{{ $inv->invoice_number }}</td>
                <td class="d-none d-sm-table-cell">{{ optional($inv->invoice_date)->format('d M Y') }}</td>
                <td>{{ optional($inv->customer)->name ?? 'Walk-in' }}</td>
                <td>
                    <span class="tag {{ $inv->delivery_status === 'partial' ? 'warn' : 'danger' }}">
                        {{ ucfirst($inv->delivery_status) }}
                    </span>
                </td>
                <td class="text-right">৳{{ number_format($inv->total, 2) }}</td>
                <td>
                    <a href="{{ route('challans.create', ['invoice_id' => $inv->id]) }}"
                       class="btn modern-btn modern-btn-primary btn-sm" style="font-size:11px;"
                       target="_blank" rel="noopener">
                        <i class="fas fa-truck mr-1"></i>Deliver
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
