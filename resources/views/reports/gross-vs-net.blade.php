@extends('reports.layout')
@section('title', 'Gross vs Net Sales')

@section('report-content')
    <div class="report-header">
        <h1 class="report-title">Gross vs Net Sales</h1>
        <p class="report-subtitle">Perbandingan harga kotor, diskon, dan harga bersih per hari</p>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th class="text-right">Gross</th>
                    <th class="text-right">Diskon</th>
                    <th class="text-right">Net</th>
                    <th class="text-right">% Diskon</th>
                    <th class="text-right">PPN</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data as $row)
                    <tr>
                        <td style="color: var(--text-primary); font-weight: 500;">{{ \Carbon\Carbon::parse($row->date)->format('d M Y') }}</td>
                        <td class="text-right number">Rp {{ number_format($row->gross, 0, ',', '.') }}</td>
                        <td class="text-right number text-warning">Rp {{ number_format($row->discount_amount, 0, ',', '.') }}</td>
                        <td class="text-right number text-success" style="font-weight: 600;">Rp {{ number_format($row->net, 0, ',', '.') }}</td>
                        <td class="text-right">
                            <span class="badge {{ $row->discount_pct >= 15 ? 'badge-danger' : ($row->discount_pct >= 5 ? 'badge-warning' : 'badge-success') }}">
                                {{ $row->discount_pct }}%
                            </span>
                        </td>
                        <td class="text-right number">Rp {{ number_format($row->vat, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
