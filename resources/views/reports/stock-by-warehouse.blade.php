@extends('reports.layout')
@section('title', 'Stok per Gudang')

@section('report-content')
    <div class="report-header">
        <h1 class="report-title">Stok per Gudang</h1>
        <p class="report-subtitle">Ringkasan stok terakhir per cabang dan gudang</p>
    </div>

    @php $maxValue = $data->max('total_value_on_hand') ?: 1; @endphp
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Cabang</th>
                    <th>Gudang</th>
                    <th class="text-right">Total Item</th>
                    <th class="text-right">On Hand</th>
                    <th class="text-right">On Sales</th>
                    <th class="text-right">Nilai Stok (On Hand)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data as $row)
                    <tr>
                        <td>
                            <span class="badge badge-info" style="text-transform: uppercase;">{{ $row->branch }}</span>
                        </td>
                        <td style="color: var(--text-primary); font-weight: 500;">{{ $row->warehouse_name ?? $row->warehouse_code }}</td>
                        <td class="text-right number">{{ number_format($row->total_items) }}</td>
                        <td class="text-right number">{{ number_format($row->total_on_hand) }}</td>
                        <td class="text-right number">{{ number_format($row->total_on_sales) }}</td>
                        <td class="text-right bar-cell">
                            <div class="bar-fill" style="width: {{ ($row->total_value_on_hand / $maxValue) * 100 }}%"></div>
                            <span class="number" style="font-weight: 600;">Rp {{ number_format($row->total_value_on_hand, 0, ',', '.') }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">Belum ada data stok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
