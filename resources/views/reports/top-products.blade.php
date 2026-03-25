@extends('reports.layout')
@section('title', 'Top Produk')

@section('report-content')
    <div class="report-header">
        <h1 class="report-title">Top Produk</h1>
        <p class="report-subtitle">Produk terlaris berdasarkan nilai atau kuantitas</p>
    </div>

    <form class="filter-bar" method="GET">
        <select name="sort" class="filter-input" onchange="this.form.submit()">
            <option value="value" {{ $sortBy === 'value' ? 'selected' : '' }}>Urutkan: Nilai</option>
            <option value="qty" {{ $sortBy === 'qty' ? 'selected' : '' }}>Urutkan: Qty</option>
        </select>
        <select name="limit" class="filter-input" onchange="this.form.submit()">
            <option value="10" {{ $limit == 10 ? 'selected' : '' }}>Top 10</option>
            <option value="20" {{ $limit == 20 ? 'selected' : '' }}>Top 20</option>
            <option value="50" {{ $limit == 50 ? 'selected' : '' }}>Top 50</option>
        </select>
    </form>

    @php $maxValue = $data->max('total_value') ?: 1; @endphp
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>SKU</th>
                    <th>Produk</th>
                    <th>Kategori</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Transaksi</th>
                    <th class="text-right">Total Nilai</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $i => $row)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td><code style="font-size: 0.8rem; color: var(--accent-hover);">{{ $row->sku }}</code></td>
                        <td style="color: var(--text-primary); font-weight: 500;">{{ $row->name }}</td>
                        <td>{{ $row->category ?? '-' }}</td>
                        <td class="text-right number">{{ number_format($row->total_qty) }}</td>
                        <td class="text-right number">{{ number_format($row->total_transactions) }}</td>
                        <td class="text-right bar-cell">
                            <div class="bar-fill" style="width: {{ ($row->total_value / $maxValue) * 100 }}%"></div>
                            <span class="number" style="font-weight: 600;">Rp {{ number_format($row->total_value, 0, ',', '.') }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
