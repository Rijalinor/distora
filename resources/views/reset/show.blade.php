@extends('layouts.app')
@section('title', 'Periode ' . $period->name)

@section('content')
    <div style="max-width: 720px; margin: 0 auto;">
        <div class="breadcrumb">
            <a href="{{ route('dashboard') }}">Dashboard</a>
            <span class="sep">›</span>
            <a href="{{ route('reset.index') }}">Tutup Buku</a>
            <span class="sep">›</span>
            <span class="current">{{ $period->name }}</span>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <div>
                <h1 style="font-size: 1.25rem; font-weight: 700;">📚 {{ $period->name }}</h1>
                <span style="color: var(--text-muted); font-size: 0.875rem;">
                    Ditutup {{ $period->closed_at?->format('d M Y H:i') }}
                </span>
            </div>
            <span class="badge badge-success"><span class="badge-dot"></span> Closed</span>
        </div>

        @php $s = $period->summary ?? []; @endphp

        <!-- Summary Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0.75rem; margin-bottom: 1.5rem;">
            <div class="stat-card">
                <div class="stat-label">Upload</div>
                <div class="stat-value" style="font-size: 1.5rem;">{{ number_format($s['uploads'] ?? 0) }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Transaksi</div>
                <div class="stat-value" style="font-size: 1.5rem;">{{ number_format($s['transactions'] ?? 0) }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Sales</div>
                <div class="stat-value" style="font-size: 1.5rem;">{{ number_format($s['sales_count'] ?? 0) }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Return</div>
                <div class="stat-value" style="font-size: 1.5rem;">{{ number_format($s['returns_count'] ?? 0) }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Stok</div>
                <div class="stat-value" style="font-size: 1.5rem;">{{ number_format($s['stocks_count'] ?? 0) }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Outlet</div>
                <div class="stat-value" style="font-size: 1.5rem;">{{ number_format($s['outlets_count'] ?? 0) }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Produk</div>
                <div class="stat-value" style="font-size: 1.5rem;">{{ number_format($s['products_count'] ?? 0) }}</div>
            </div>
        </div>

        <!-- Financial Summary -->
        <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; padding: 1.25rem;">
            <div style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); margin-bottom: 1rem;">💰 Ringkasan Keuangan</div>

            <div class="detail-row">
                <span class="detail-label">Total Gross</span>
                <span class="detail-value">Rp {{ number_format($s['total_gross'] ?? 0, 0, ',', '.') }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Total Diskon</span>
                <span class="detail-value text-warning">Rp {{ number_format($s['total_discount'] ?? 0, 0, ',', '.') }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Total Penjualan (Net)</span>
                <span class="detail-value text-success" style="font-weight: 700;">Rp {{ number_format($s['total_sales'] ?? 0, 0, ',', '.') }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Total Return</span>
                <span class="detail-value text-danger">Rp {{ number_format(abs($s['total_returns'] ?? 0), 0, ',', '.') }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Total PPN</span>
                <span class="detail-value">Rp {{ number_format($s['total_vat'] ?? 0, 0, ',', '.') }}</span>
            </div>
            <div style="border-top: 1px solid var(--border); margin-top: 0.75rem; padding-top: 0.75rem;">
                <div class="detail-row">
                    <span class="detail-label" style="font-weight: 600;">Net setelah Return</span>
                    <span class="detail-value" style="font-weight: 700; font-size: 1.1rem; color: var(--accent-hover);">
                        Rp {{ number_format(($s['total_sales'] ?? 0) + ($s['total_returns'] ?? 0), 0, ',', '.') }}
                    </span>
                </div>
            </div>
        </div>
    </div>
@endsection
