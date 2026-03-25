@extends('reports.layout')
@section('title', 'Return Rate')

@section('report-content')
    <div class="report-header">
        <h1 class="report-title">Return Rate</h1>
        <p class="report-subtitle">Persentase return vs penjualan per produk</p>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>SKU</th>
                    <th>Produk</th>
                    <th class="text-right">Qty Jual</th>
                    <th class="text-right">Qty Return</th>
                    <th class="text-right">Rate (Qty)</th>
                    <th class="text-right">Nilai Jual</th>
                    <th class="text-right">Nilai Return</th>
                    <th class="text-right">Rate (Nilai)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($salesByProduct as $i => $row)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td><code style="font-size: 0.8rem; color: var(--accent-hover);">{{ $row->sku }}</code></td>
                        <td style="color: var(--text-primary); font-weight: 500; max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $row->name }}</td>
                        <td class="text-right number">{{ number_format($row->sales_qty) }}</td>
                        <td class="text-right number text-danger">{{ number_format($row->return_qty) }}</td>
                        <td class="text-right">
                            <span class="badge {{ $row->return_rate_qty >= 20 ? 'badge-danger' : ($row->return_rate_qty >= 10 ? 'badge-warning' : 'badge-success') }}">
                                {{ $row->return_rate_qty }}%
                            </span>
                        </td>
                        <td class="text-right number">Rp {{ number_format($row->sales_value, 0, ',', '.') }}</td>
                        <td class="text-right number text-danger">Rp {{ number_format($row->return_value, 0, ',', '.') }}</td>
                        <td class="text-right">
                            <span class="badge {{ $row->return_rate_value >= 20 ? 'badge-danger' : ($row->return_rate_value >= 10 ? 'badge-warning' : 'badge-success') }}">
                                {{ $row->return_rate_value }}%
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
