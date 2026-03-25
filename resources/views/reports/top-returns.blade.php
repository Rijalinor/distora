@extends('reports.layout')
@section('title', 'Top Return')

@section('report-content')
    <div class="report-header">
        <h1 class="report-title">Top Return</h1>
        <p class="report-subtitle">Produk dan outlet dengan return tertinggi</p>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Produk</th>
                    <th>Outlet</th>
                    <th>Tanggal</th>
                    <th class="text-right">Qty Return</th>
                    <th class="text-right">Nilai Return</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data as $i => $row)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>
                            <div style="color: var(--text-primary); font-weight: 500;">{{ $row->product_name }}</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $row->sku }}</div>
                        </td>
                        <td>
                            <div>{{ $row->outlet_name }}</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $row->outlet_code }}</div>
                        </td>
                        <td>{{ \Carbon\Carbon::parse($row->transaction_date)->format('d M Y') }}</td>
                        <td class="text-right number text-danger">{{ number_format($row->return_qty) }}</td>
                        <td class="text-right number text-danger" style="font-weight: 600;">Rp {{ number_format($row->return_value, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">Tidak ada data return.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
