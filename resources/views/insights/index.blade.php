@extends('layouts.app')

@section('title', 'Keputusan Strategis')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem;">
    <div>
        <h1 style="font-size: 1.8rem; font-weight: 800; background: linear-gradient(135deg, var(--accent), #f472b6); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
            💡 Pusat Kendali Keputusan
        </h1>
        <div style="display: flex; gap: 1rem; align-items: center; margin-top: 0.25rem;">
            <p style="color: var(--text-muted); font-size: 1rem; margin: 0;">Pilar analisis berdasarkan <strong>90 Hari Terakhir</strong> data transaksi.</p>
            <a href="{{ route('insights.guide') }}" style="color: var(--accent); font-size: 0.9rem; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 0.4rem; padding: 0.2rem 0.6rem; border: 1px solid var(--accent); border-radius: 8px;">
                📖 Panduan Baca
            </a>
        </div>
    </div>
    
    <form method="GET" action="{{ route('insights.index') }}" style="display: flex; gap: 0.75rem; align-items: center; background: var(--bg-card); padding: 0.5rem 1rem; border-radius: 12px; border: 1px solid var(--border);">
        <label for="branch" style="font-size: 0.85rem; color: var(--text-secondary); font-weight: 600;">Wilayah:</label>
        <select name="branch" id="branch" onchange="this.form.submit()" style="padding: 0.4rem; border: none; background: transparent; color: var(--text-primary); font-weight: 700; outline: none; cursor: pointer;">
            <option value="all" {{ $data['selected_branch'] === 'all' ? 'selected' : '' }}>Semua Cabang</option>
            <option value="OBM_01" {{ $data['selected_branch'] === 'OBM_01' ? 'selected' : '' }}>Banjarmasin (OBM_01)</option>
            <option value="OBM_02" {{ $data['selected_branch'] === 'OBM_02' ? 'selected' : '' }}>Barabai (OBM_02)</option>
            <option value="OBM_03" {{ $data['selected_branch'] === 'OBM_03' ? 'selected' : '' }}>Batulicin (OBM_03)</option>
        </select>
    </form>
</div>

<style>
    .pillar-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1.5rem;
    }
    .pillar-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 1.75rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none;
        display: flex;
        flex-direction: column;
        position: relative;
        overflow: hidden;
    }
    .pillar-card:hover {
        transform: translateY(-5px);
        border-color: var(--accent);
        box-shadow: 0 12px 24px -10px rgba(0,0,0,0.3);
    }
    .pillar-card::after {
        content: "→";
        position: absolute;
        right: 1.5rem;
        bottom: 1.5rem;
        font-size: 1.5rem;
        color: var(--text-muted);
        opacity: 0;
        transition: all 0.3s;
    }
    .pillar-card:hover::after {
        opacity: 1;
        right: 1.25rem;
        color: var(--accent);
    }
    .pillar-icon {
        font-size: 2.2rem;
        margin-bottom: 1rem;
    }
    .pillar-title {
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
    }
    .pillar-desc {
        font-size: 0.875rem;
        color: var(--text-muted);
        line-height: 1.5;
        margin-bottom: 1.5rem;
    }
    .pillar-stat {
        margin-top: auto;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--accent);
        background: rgba(99, 102, 241, 0.1);
        padding: 0.4rem 0.8rem;
        border-radius: 8px;
        align-self: flex-start;
    }
</style>

<div class="pillar-grid">
    <!-- Pillar 1: RFM -->
    <a href="{{ route('insights.rfm', ['branch' => $data['selected_branch']]) }}" class="pillar-card" style="border-top: 4px solid var(--accent);">
        <div class="pillar-icon">💎</div>
        <div class="pillar-title">Segmentasi Toko (RFM)</div>
        <div class="pillar-desc">Klasifikasi otomatis toko ke segmen Sultan, Gold, atau Sleeper untuk prioritas kunjungan.</div>
        <div class="pillar-stat">📊 {{ $data['summary']['outlets'] }} Outlet Terdata</div>
    </a>

    <!-- Pillar 2: Bundling -->
    <a href="{{ route('insights.bundling', ['branch' => $data['selected_branch']]) }}" class="pillar-card" style="border-top: 4px solid #f59e0b;">
        <div class="pillar-icon">📦</div>
        <div class="pillar-title">Rekomendasi Bundling</div>
        <div class="pillar-desc">Algoritma Market Basket untuk mencari pasangan produk yang paling laku dibeli bersama.</div>
        <div class="pillar-stat" style="color: #f59e0b; background: rgba(245, 158, 11, 0.1);">🛒 Analisis: {{ $data['summary']['bundles'] }}</div>
    </a>


    <!-- Pillar 3: Monitoring Stok Kritis -->
    <a href="{{ route('insights.stock-forecast', ['branch' => $data['selected_branch']]) }}" class="pillar-card" style="border-top: 4px solid var(--danger);">
        <div class="pillar-icon">🚨</div>
        <div class="pillar-title">Monitoring Stok Kritis</div>
        <div class="pillar-desc">Pantau barang yang hampir habis dalam 1-3 hari. Pantau level "Kritis" untuk segera amankan stok.</div>
        <div class="pillar-stat" style="color: var(--danger); background: rgba(239, 68, 68, 0.1);">🚩 {{ $data['summary']['stock_alerts'] }} Produk Kritis</div>
    </a>

     <!-- Pillar 4: Rekomendasi Order Pabrik -->
     <a href="{{ route('insights.purchase-order', ['branch' => $data['selected_branch']]) }}" class="pillar-card" style="border-top: 4px solid var(--accent);">
        <div class="pillar-icon">🛒</div>
        <div class="pillar-title">Rekomendasi Order Pabrik</div>
        <div class="pillar-desc">Hitung otomatis jumlah pesanan ke pabrik berdasarkan target "Buffer" hari yang Anda mau.</div>
        <div class="pillar-stat">🛒 Hitung Kebutuhan</div>
    </a>

     <!-- Pillar 5: Dead Stock -->
     <a href="{{ route('insights.dead-stock', ['branch' => $data['selected_branch']]) }}" class="pillar-card" style="border-top: 4px solid #f472b6;">
        <div class="pillar-icon">🧊</div>
        <div class="pillar-title">Stok Mati (Dead Stock)</div>
        <div class="pillar-desc">Daftar produk yang mengendap di gudang tanpa penjualan. Rekomendasi cuci gudang.</div>
        <div class="pillar-stat" style="color: #f472b6; background: rgba(244, 114, 182, 0.1);">❄️ {{ $data['summary']['dead_stock'] }} Produk Tidur</div>
    </a>

    <!-- Pillar 6: Growth -->
    <a href="{{ route('insights.growth', ['branch' => $data['selected_branch']]) }}" class="pillar-card" style="border-top: 4px solid #10b981;">
        <div class="pillar-icon">📈</div>
        <div class="pillar-title">Pertumbuhan Prinsipel</div>
        <div class="pillar-desc">Monitoring momentum kenaikan atau penurunan omzet mingguan tiap pabrikan.</div>
        <div class="pillar-stat" style="color: #10b981; background: rgba(16, 185, 129, 0.1);">🚀 Cek Momentum</div>
    </a>

    <!-- Pillar 7: Discounts -->
    <a href="{{ route('insights.discounts', ['branch' => $data['selected_branch']]) }}" class="pillar-card" style="border-top: 4px solid #06b6d4;">
        <div class="pillar-icon">💸</div>
        <div class="pillar-title">Evaluasi Efektivitas Diskon</div>
        <div class="pillar-desc">Berapa biaya "Bakar Uang" dibandingkan dengan pendapatan bersih yang didapat?</div>
        <div class="pillar-stat" style="color: #06b6d4; background: rgba(6, 182, 212, 0.1);">💰 Rasio Kontribusi</div>
    </a>


    <!-- Pillar 8: Anomalies -->
    <a href="{{ route('insights.anomalies', ['branch' => $data['selected_branch']]) }}" class="pillar-card" style="border-top: 4px solid var(--warning);">
        <div class="pillar-icon">🕵️</div>
        <div class="pillar-title">Audit Sales & Anomali</div>
        <div class="pillar-desc">Deteksi dini kecurangan atau masalah kualitas barang lewat pola retur salesman.</div>
        <div class="pillar-stat" style="color: var(--warning); background: rgba(245, 158, 11, 0.1);">⚠️ {{ $data['summary']['anomalies'] }} Kasus Waspada</div>
    </a>

    <!-- Pillar 9: Principal Intelligence -->
    <a href="{{ route('insights.principal-report', ['branch' => $data['selected_branch']]) }}" class="pillar-card" style="border-top: 4px solid #3b82f6;">
        <div class="pillar-icon">🏪</div>
        <div class="pillar-title">Laporan Detail Prinsipel</div>
        <div class="pillar-desc">Deep-dive performa per brand. Cek tren, produk terlaris, dan outlet terloyal khusus untuk prinsipel pilihan.</div>
        <div class="pillar-stat" style="color: #3b82f6; background: rgba(59, 130, 246, 0.1);">📊 Intelligence Per Brand</div>
    </a>
</div>


@endsection
