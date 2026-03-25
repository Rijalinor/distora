@extends('reports.layout')
@section('title', 'Ringkasan Diskon')

@section('report-content')
    <div class="report-header">
        <h1 class="report-title">Ringkasan Diskon</h1>
        <p class="report-subtitle">Total diskon yang diberikan per kategori</p>
    </div>

    <!-- Summary Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <div class="stat-card">
            <div class="stat-label">Total Gross</div>
            <div class="stat-value" style="font-size: 1.25rem;">Rp {{ number_format($data->total_gross, 0, ',', '.') }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Diskon</div>
            <div class="stat-value text-warning" style="font-size: 1.25rem;">Rp {{ number_format($data->total_discount, 0, ',', '.') }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Net Sales</div>
            <div class="stat-value text-success" style="font-size: 1.25rem;">Rp {{ number_format($data->total_sales, 0, ',', '.') }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total PPN</div>
            <div class="stat-value" style="font-size: 1.25rem;">Rp {{ number_format($data->total_vat, 0, ',', '.') }}</div>
        </div>
    </div>

    <!-- Discount Breakdown -->
    <h2 class="section-title">Breakdown Diskon</h2>
    <div class="table-wrap" style="margin-bottom: 2rem;">
        <table>
            <thead>
                <tr>
                    <th>Jenis Diskon</th>
                    <th class="text-right">Jumlah</th>
                    <th class="text-right">% dari Gross</th>
                </tr>
            </thead>
            <tbody>
                @php $gross = $data->total_gross ?: 1; @endphp
                <tr>
                    <td style="color: var(--text-primary);">Diskon Item</td>
                    <td class="text-right number">Rp {{ number_format($data->total_disc_item, 0, ',', '.') }}</td>
                    <td class="text-right number">{{ number_format(($data->total_disc_item / $gross) * 100, 2) }}%</td>
                </tr>
                <tr>
                    <td style="color: var(--text-primary);">Diskon Internal</td>
                    <td class="text-right number">Rp {{ number_format($data->total_disc_internal, 0, ',', '.') }}</td>
                    <td class="text-right number">{{ number_format(($data->total_disc_internal / $gross) * 100, 2) }}%</td>
                </tr>
                <tr>
                    <td style="color: var(--text-primary);">Diskon External</td>
                    <td class="text-right number">Rp {{ number_format($data->total_disc_external, 0, ',', '.') }}</td>
                    <td class="text-right number">{{ number_format(($data->total_disc_external / $gross) * 100, 2) }}%</td>
                </tr>
                <tr>
                    <td style="color: var(--text-primary);">Diskon Invoice</td>
                    <td class="text-right number">Rp {{ number_format($data->total_disc_invoice, 0, ',', '.') }}</td>
                    <td class="text-right number">{{ number_format(($data->total_disc_invoice / $gross) * 100, 2) }}%</td>
                </tr>
                <tr style="font-weight: 700; border-top: 2px solid var(--border);">
                    <td style="color: var(--warning);">Total Diskon</td>
                    <td class="text-right number text-warning">Rp {{ number_format($data->total_discount, 0, ',', '.') }}</td>
                    <td class="text-right number text-warning">{{ number_format(($data->total_discount / $gross) * 100, 2) }}%</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Per Principle -->
    <h2 class="section-title">Per Principle</h2>
    @php $maxGross = $perPrinciple->max('gross') ?: 1; @endphp
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Principle</th>
                    <th class="text-right">Gross</th>
                    <th class="text-right">Diskon</th>
                    <th class="text-right">Net</th>
                    <th class="text-right">PPN</th>
                </tr>
            </thead>
            <tbody>
                @foreach($perPrinciple as $row)
                    <tr>
                        <td style="color: var(--text-primary); font-weight: 500;">{{ $row->principle_name }}</td>
                        <td class="text-right bar-cell">
                            <div class="bar-fill" style="width: {{ ($row->gross / $maxGross) * 100 }}%"></div>
                            <span class="number">Rp {{ number_format($row->gross, 0, ',', '.') }}</span>
                        </td>
                        <td class="text-right number text-warning">Rp {{ number_format($row->discount, 0, ',', '.') }}</td>
                        <td class="text-right number text-success">Rp {{ number_format($row->net, 0, ',', '.') }}</td>
                        <td class="text-right number">Rp {{ number_format($row->vat, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
