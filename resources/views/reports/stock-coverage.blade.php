@extends('reports.layout')
@section('title', 'Stock Coverage')

@section('report-content')
    <div class="report-header">
        <h1 class="report-title">Stock Coverage</h1>
        <p class="report-subtitle">Produk dengan coverage rendah — berpotensi habis stok</p>
    </div>

    <form class="filter-bar" method="GET">
        <label style="color: var(--text-secondary); font-size: 0.85rem;">Maks. SWC (minggu):</label>
        <input type="number" name="max_swc" value="{{ $maxSwc }}" class="filter-input" style="width: 80px;">
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
                    <th>Gudang</th>
                    <th class="text-right">On Hand</th>
                    <th class="text-right">WAS</th>
                    <th class="text-right">SWC</th>
                    <th class="text-right">Nilai Stok</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data as $i => $row)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td><code style="font-size: 0.8rem; color: var(--accent-hover);">{{ $row->sku }}</code></td>
                        <td style="color: var(--text-primary); font-weight: 500; max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $row->name }}</td>
                        <td><span class="badge badge-info" style="text-transform: uppercase;">{{ $row->branch }}</span></td>
                        <td>{{ $row->warehouse_name }}</td>
                        <td class="text-right number">{{ number_format($row->on_hand_base) }}</td>
                        <td class="text-right number">{{ number_format($row->was, 1) }}</td>
                        <td class="text-right">
                            <span class="badge {{ $row->swc <= 1 ? 'badge-danger' : ($row->swc <= 2 ? 'badge-warning' : 'badge-info') }}">
                                {{ $row->swc }} minggu
                            </span>
                        </td>
                        <td class="text-right number">Rp {{ number_format($row->stock_value_on_hand, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" style="text-align: center; color: var(--text-muted); padding: 2rem;">Semua stok memiliki coverage yang cukup.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
