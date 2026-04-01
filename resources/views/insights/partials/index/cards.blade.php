<div class="pillar-grid">
    <a href="{{ route('insights.rfm', ['branch' => $data['selected_branch'], 'period_id' => $activePeriod->id]) }}" class="pillar-card" style="border-top: 4px solid var(--accent);">
        <div class="pillar-icon">RFM</div>
        <div class="pillar-title">Segmentasi Toko (RFM)</div>
        <div class="pillar-desc">Klasifikasi otomatis toko ke segmen Sultan, Gold, atau Sleeper untuk prioritas kunjungan.</div>
        <div class="pillar-stat">{{ $data['summary']['outlets'] }} Outlet Terdata</div>
    </a>

    <a href="{{ route('insights.bundling', ['branch' => $data['selected_branch'], 'period_id' => $activePeriod->id]) }}" class="pillar-card" style="border-top: 4px solid #f59e0b;">
        <div class="pillar-icon">BND</div>
        <div class="pillar-title">Rekomendasi Bundling</div>
        <div class="pillar-desc">Algoritma Market Basket untuk mencari pasangan produk yang paling laku dibeli bersama.</div>
        <div class="pillar-stat" style="color: #f59e0b; background: rgba(245, 158, 11, 0.1);">Analisis: {{ $data['summary']['bundles'] }}</div>
    </a>

    <a href="{{ route('insights.stock-forecast', ['branch' => $data['selected_branch'], 'period_id' => $activePeriod->id]) }}" class="pillar-card" style="border-top: 4px solid var(--danger);">
        <div class="pillar-icon">STK</div>
        <div class="pillar-title">Monitoring Stok Kritis</div>
        <div class="pillar-desc">Pantau barang yang hampir habis dalam 1-3 hari. Pantau level "Kritis" untuk segera amankan stok.</div>
        <div class="pillar-stat" style="color: var(--danger); background: rgba(239, 68, 68, 0.1);">{{ $data['summary']['stock_alerts'] }} Produk Kritis</div>
    </a>

    <a href="{{ route('insights.stock-redistribution', ['branch' => $data['selected_branch'], 'period_id' => $activePeriod->id]) }}" class="pillar-card" style="border-top: 4px solid #10b981;">
        <div class="pillar-icon">RDS</div>
        <div class="pillar-title">Stock Redistribution Advisor</div>
        <div class="pillar-desc">Bandingkan SWC antar gudang untuk menyarankan mutasi stok sebelum order pabrik.</div>
        <div class="pillar-stat" style="color: #10b981; background: rgba(16, 185, 129, 0.1);">{{ $data['summary']['redistribution'] ?? 0 }} Peluang Mutasi</div>
    </a>

    <a href="{{ route('insights.purchase-order', ['branch' => $data['selected_branch'], 'period_id' => $activePeriod->id]) }}" class="pillar-card" style="border-top: 4px solid var(--accent);">
        <div class="pillar-icon">PO</div>
        <div class="pillar-title">Rekomendasi Order Pabrik</div>
        <div class="pillar-desc">Hitung otomatis jumlah pesanan ke pabrik berdasarkan target "Buffer" hari yang Anda mau.</div>
        <div class="pillar-stat">Hitung Kebutuhan</div>
    </a>

    <a href="{{ route('insights.dead-stock', ['branch' => $data['selected_branch'], 'period_id' => $activePeriod->id]) }}" class="pillar-card" style="border-top: 4px solid #f472b6;">
        <div class="pillar-icon">DS</div>
        <div class="pillar-title">Stok Mati (Dead Stock)</div>
        <div class="pillar-desc">Daftar produk yang mengendap di gudang tanpa penjualan. Rekomendasi cuci gudang.</div>
        <div class="pillar-stat" style="color: #f472b6; background: rgba(244, 114, 182, 0.1);">{{ $data['summary']['dead_stock'] }} Produk Tidur</div>
    </a>

    <a href="{{ route('insights.growth', ['branch' => $data['selected_branch'], 'period_id' => $activePeriod->id]) }}" class="pillar-card" style="border-top: 4px solid #10b981;">
        <div class="pillar-icon">GR</div>
        <div class="pillar-title">Pertumbuhan Prinsipel</div>
        <div class="pillar-desc">Monitoring momentum kenaikan atau penurunan omzet mingguan tiap pabrikan.</div>
        <div class="pillar-stat" style="color: #10b981; background: rgba(16, 185, 129, 0.1);">Cek Momentum</div>
    </a>

    <a href="{{ route('insights.discounts', ['branch' => $data['selected_branch'], 'period_id' => $activePeriod->id]) }}" class="pillar-card" style="border-top: 4px solid #06b6d4;">
        <div class="pillar-icon">DSC</div>
        <div class="pillar-title">Evaluasi Efektivitas Diskon</div>
        <div class="pillar-desc">Berapa biaya "Bakar Uang" dibandingkan dengan pendapatan bersih yang didapat?</div>
        <div class="pillar-stat" style="color: #06b6d4; background: rgba(6, 182, 212, 0.1);">Rasio Kontribusi</div>
    </a>

    <a href="{{ route('insights.ai-advisor', ['branch' => $data['selected_branch'], 'period_id' => $activePeriod->id]) }}" class="pillar-card" style="border-top: 4px solid var(--accent); background: linear-gradient(to bottom right, var(--bg-card), rgba(99, 102, 241, 0.05));">
        <div class="pillar-icon">AI</div>
        <div class="pillar-title">Asisten Keputusan AI</div>
        <div class="pillar-desc">Asisten pintar yang memberikan saran tindakan nyata berdasarkan anomali, stok, dan peluang pasar.</div>
        <div class="pillar-stat" style="color: var(--accent); background: rgba(99, 102, 241, 0.1);">{{ $data['summary']['advisor'] }} Saran Tindakan</div>
    </a>

    <a href="{{ route('insights.ml-monitor', ['branch' => $data['selected_branch'], 'period_id' => $activePeriod->id]) }}" class="pillar-card" style="border-top: 4px solid #22d3ee;">
        <div class="pillar-icon">ML</div>
        <div class="pillar-title">ML Monitoring</div>
        <div class="pillar-desc">Pantau akurasi model, confidence, WAPE, dan histori run forecast secara real-time.</div>
        <div class="pillar-stat" style="color: #06b6d4; background: rgba(6, 182, 212, 0.1);">Model Health</div>
    </a>
</div>