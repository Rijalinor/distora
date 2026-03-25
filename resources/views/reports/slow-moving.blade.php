@extends('reports.layout')
@section('title', 'Slow Moving Items')

@section('report-content')
    <div class="report-header">
        <h1 class="report-title">Slow Moving Items</h1>
        <p class="report-subtitle">Produk dengan umur stok tinggi — perlu perhatian khusus</p>
    </div>

    <form class="filter-bar" method="GET">
        <label style="color: var(--text-secondary); font-size: 0.85rem;">Min. Umur (hari):</label>
        <input type="number" name="min_age" value="{{ $minAge }}" class="filter-input" style="width: 80px;">
        <select name="limit" class="filter-input">
            <option value="20" {{ $limit == 20 ? 'selected' : '' }}>Top 20</option>
            <option value="30" {{ $limit == 30 ? 'selected' : '' }}>Top 30</option>
            <option value="50" {{ $limit == 50 ? 'selected' : '' }}>Top 50</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>SKU</th>
                    <th>Produk</th>
                    <th>Cabang</th>
                    <th class="text-right">On Hand</th>
                    <th class="text-right">Nilai Stok</th>
                    <th class="text-right">WAS</th>
                    <th class="text-right">SWC</th>
                    <th class="text-right">Umur (hari)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $i => $row)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td><code style="font-size: 0.8rem; color: var(--accent-hover);">{{ $row->sku }}</code></td>
                        <td style="color: var(--text-primary); font-weight: 500; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $row->name }}</td>
                        <td><span class="badge badge-info" style="text-transform: uppercase;">{{ $row->branch }}</span></td>
                        <td class="text-right number">{{ number_format($row->on_hand_base) }}</td>
                        <td class="text-right number">Rp {{ number_format($row->stock_value_on_hand, 0, ',', '.') }}</td>
                        <td class="text-right number">{{ number_format($row->was, 1) }}</td>
                        <td class="text-right number">{{ $row->swc }}</td>
                        <td class="text-right">
                            <span class="badge {{ $row->age_of_goods >= 90 ? 'badge-danger' : 'badge-warning' }}">
                                {{ number_format($row->age_of_goods) }} hari
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
