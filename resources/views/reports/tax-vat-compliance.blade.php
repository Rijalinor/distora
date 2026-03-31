@extends('reports.layout')
@section('title', 'Tax & VAT Compliance')

@section('report-content')
    <div class="report-header">
        <h1 class="report-title">Tax & VAT Compliance Automator</h1>
        <p class="report-subtitle">Rekap otomatis PPN Keluaran bulanan dan kelengkapan Tax Invoice.</p>
    </div>

    <form method="GET" class="filter-bar">
        <input type="date" name="from" value="{{ $dateFrom }}" class="filter-input">
        <input type="date" name="to" value="{{ $dateTo }}" class="filter-input">
        <select name="branch" class="filter-input">
            <option value="all" {{ $branch === 'all' ? 'selected' : '' }}>Semua Cabang</option>
            <option value="OBM_01" {{ $branch === 'OBM_01' ? 'selected' : '' }}>Banjarmasin</option>
            <option value="OBM_02" {{ $branch === 'OBM_02' ? 'selected' : '' }}>Barabai</option>
            <option value="OBM_03" {{ $branch === 'OBM_03' ? 'selected' : '' }}>Batulicin</option>
        </select>
        <select name="principle" class="filter-input">
            <option value="all" {{ $principle === 'all' ? 'selected' : '' }}>Semua Principle</option>
            @foreach($principles as $p)
                <option value="{{ $p }}" {{ $principle === $p ? 'selected' : '' }}>{{ $p }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-primary">Apply</button>
    </form>

    <div class="stats-grid" style="margin-bottom:1.25rem;">
        <div class="stat-card">
            <div class="stat-label">Total DPP Net</div>
            <div class="stat-value" style="font-size:1.35rem;">Rp {{ number_format($summary->dpp_net ?? 0, 0, ',', '.') }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total PPN Keluaran</div>
            <div class="stat-value" style="font-size:1.35rem;">Rp {{ number_format($summary->vat_output ?? 0, 0, ',', '.') }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Baris Dengan Tax Invoice</div>
            <div class="stat-value" style="font-size:1.35rem;">{{ number_format($summary->rows_with_tax_invoice ?? 0, 0, ',', '.') }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Compliance Rate</div>
            <div class="stat-value" style="font-size:1.35rem;">{{ number_format($summary->compliance_pct ?? 0, 2) }}%</div>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Periode</th>
                    <th class="text-right">DPP Net</th>
                    <th class="text-right">PPN Keluaran</th>
                    <th class="text-right">Rows Sales</th>
                    <th class="text-right">Rows Tax Invoice</th>
                    <th class="text-right">Compliance</th>
                </tr>
            </thead>
            <tbody>
                @forelse($monthly as $row)
                    <tr>
                        <td style="color:var(--text-primary);font-weight:600;">{{ \Carbon\Carbon::createFromFormat('Y-m', $row->period)->translatedFormat('F Y') }}</td>
                        <td class="text-right number">Rp {{ number_format($row->dpp_net, 0, ',', '.') }}</td>
                        <td class="text-right number text-success" style="font-weight:700;">Rp {{ number_format($row->vat_output, 0, ',', '.') }}</td>
                        <td class="text-right number">{{ number_format($row->sales_rows, 0, ',', '.') }}</td>
                        <td class="text-right number">{{ number_format($row->rows_with_tax_invoice, 0, ',', '.') }}</td>
                        <td class="text-right">
                            <span class="badge {{ $row->compliance_pct >= 95 ? 'badge-success' : ($row->compliance_pct >= 80 ? 'badge-warning' : 'badge-danger') }}">
                                {{ number_format($row->compliance_pct, 2) }}%
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center;color:var(--text-muted);padding:2rem;">Belum ada data untuk filter ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

