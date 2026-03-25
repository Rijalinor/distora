@extends('reports.layout')
@section('title', 'Ringkasan Penjualan')

@section('report-content')
    <div class="report-header">
        <h1 class="report-title">Ringkasan Penjualan</h1>
        <p class="report-subtitle">Total penjualan per hari atau per bulan</p>
    </div>

    <form class="filter-bar" method="GET">
        <select name="period" class="filter-input" onchange="this.form.submit()">
            <option value="daily" {{ $period === 'daily' ? 'selected' : '' }}>Harian</option>
            <option value="monthly" {{ $period === 'monthly' ? 'selected' : '' }}>Bulanan</option>
        </select>
        <input type="date" name="from" value="{{ $dateFrom }}" class="filter-input" placeholder="Dari">
        <input type="date" name="to" value="{{ $dateTo }}" class="filter-input" placeholder="Sampai">
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Periode</th>
                    <th class="text-right">Transaksi</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Total Penjualan</th>
                    <th class="text-right">PPN</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data as $row)
                    <tr>
                        <td style="color: var(--text-primary); font-weight: 500;">
                            {{ $period === 'monthly' ? $row->period : \Carbon\Carbon::parse($row->period)->format('d M Y') }}
                        </td>
                        <td class="text-right number">{{ number_format($row->total_transactions) }}</td>
                        <td class="text-right number">{{ number_format($row->total_qty) }}</td>
                        <td class="text-right number" style="color: var(--success); font-weight: 600;">Rp {{ number_format($row->total_sales, 0, ',', '.') }}</td>
                        <td class="text-right number">Rp {{ number_format($row->total_vat, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 2rem;">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
