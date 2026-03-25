@extends('reports.layout')
@section('title', 'Sales per Principle')

@section('report-content')
    <div class="report-header">
        <h1 class="report-title">Penjualan per Principle</h1>
        <p class="report-subtitle">Penjualan berdasarkan supplier / brand</p>
    </div>

    @php $maxValue = $data->max('total_sales') ?: 1; @endphp
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>ID</th>
                    <th>Principle</th>
                    <th class="text-right">Transaksi</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Total Penjualan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $i => $row)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td><code style="font-size: 0.8rem; color: var(--accent-hover);">{{ $row->principle_id }}</code></td>
                        <td style="color: var(--text-primary); font-weight: 500;">{{ $row->principle_name }}</td>
                        <td class="text-right number">{{ number_format($row->total_transactions) }}</td>
                        <td class="text-right number">{{ number_format($row->total_qty) }}</td>
                        <td class="text-right bar-cell">
                            <div class="bar-fill" style="width: {{ ($row->total_sales / $maxValue) * 100 }}%"></div>
                            <span class="number" style="font-weight: 600;">Rp {{ number_format($row->total_sales, 0, ',', '.') }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
