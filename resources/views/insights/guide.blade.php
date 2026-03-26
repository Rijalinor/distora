@extends('layouts.app')

@section('title', 'Panduan Pengambilan Keputusan')

@section('content')
<div class="mb-4">
    <a href="{{ route('insights.index') }}" class="btn-back" style="text-decoration: none; color: var(--accent); font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
        ← Kembali ke Pusat Kendali
    </a>
    <h1 style="font-size: 1.8rem; font-weight: 800;">📖 Panduan Strategis DSS</h1>
    <p style="color: var(--text-muted);">Cara membaca data dan mengambil tindakan nyata dari 7 pilar keputusan.</p>
</div>

<style>
    .guide-section {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 2rem;
        margin-bottom: 2rem;
    }
    .guide-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--border);
    }
    .guide-icon { font-size: 2rem; }
    .guide-title { font-size: 1.25rem; font-weight: 700; color: var(--text-primary); }
    .guide-content h4 { color: var(--accent); font-size: 1rem; margin-bottom: 0.5rem; margin-top: 1.5rem; }
    .guide-content p { color: var(--text-secondary); line-height: 1.6; font-size: 0.95rem; }
    .action-box {
        background: var(--bg-primary);
        padding: 1rem;
        border-radius: 12px;
        border-left: 4px solid var(--success);
        margin-top: 1rem;
    }
    .action-box strong { color: var(--success); display: block; margin-bottom: 0.25rem; }
</style>

<!-- 1. RFM -->
<div class="guide-section">
    <div class="guide-header">
        <div class="guide-icon">💎</div>
        <div class="guide-title">Segmentasi Outlet (RFM Analysis)</div>
    </div>
    <div class="guide-content">
        <p>Metode klasifikasi toko berdasarkan tiga faktor utama: <strong>Recency</strong> (Kebaruan belanja), <strong>Frequency</strong> (Seberapa sering), dan <strong>Monetary</strong> (Besaran Rupiah).</p>
        
        <h4>Cara Membaca:</h4>
        <p>Fokuslah pada status <strong>Sleeper</strong>. Mereka adalah aset berharga yang sedang 'tidur'. Jika jumlah Sleeper meningkat, berarti distribusi Anda sedang melemah di pasar.</p>
        
        <div class="action-box">
            <strong>🚀 Tindakan Strategis:</strong>
            Utus tim Sales untuk melakukan kunjungan darurat (Re-Call) ke daftar toko Sleeper hari ini juga untuk menghindari mereka berpindah ke kompetitor.
        </div>
    </div>
</div>

<!-- 2. Bundling -->
<div class="guide-section">
    <div class="guide-header">
        <div class="guide-icon">📦</div>
        <div class="guide-title">Market Basket Analysis (Bundling)</div>
    </div>
    <div class="guide-content">
        <p>Mencari hubungan antara dua produk yang paling sering dibeli secara bersamaan dalam satu nomor nota.</p>
        
        <h4>Cara Membaca:</h4>
        <p>Jika Produk A dan B sering muncul bersamaan, artinya mereka saling menunjang (Simbiosis). Pasangan ini memiliki daya tarik tinggi jika disatukan.</p>
        
        <div class="action-box">
            <strong>🎁 Tindakan Strategis:</strong>
            Gunakan produk "Laku Keras" (Leader) untuk mendongkrak produk "Lambat" (Laggard) dengan cara membuat promo paket/bundling.
        </div>
    </div>
</div>

<!-- 3. Forecasting -->
<div class="guide-section">
    <div class="guide-header">
        <div class="guide-icon">🚨</div>
        <div class="guide-title">Prediksi Stok Habis (Forecasting)</div>
    </div>
    <div class="guide-content">
        <p>Memprediksi ketahanan stok fisik di gudang berdasarkan rata-rata penjualan per hari (Sales Velocity).</p>
        
        <h4>Cara Membaca:</h4>
        <p>Angka <strong>"7 Hari"</strong> berarti stok Anda akan habis total dalam seminggu jika tidak ada barang masuk.</p>
        
        <div class="action-box">
            <strong>🛒 Tindakan Strategis:</strong>
            Lakukan Purchase Order (PO) segera untuk item berstatus merah. Jangan sampai terjadi kehilangan potensi penjualan ("Loss Sales") karena barang kosong.
        </div>
    </div>
</div>

<!-- 4. Dead Stock -->
<div class="guide-section" style="border-top: 4px solid #f472b6;">
    <div class="guide-header">
        <div class="guide-icon">❄️</div>
        <div class="guide-title">Analisis Produk Mati (Dead Stock)</div>
    </div>
    <div class="guide-content">
        <p>Mengidentifikasi modal Anda yang "membeku" di gudang dalam bentuk barang yang tidak laku terjual sama sekali bulan ini.</p>
        
        <h4>Cara Membaca:</h4>
        <p>Tabel menampilkan produk yang punya stok tapi Nol Penjualan. Cek kolom <strong>Nilai Aset</strong> untuk melihat berapa besar uang Anda yang tidak berputar.</p>
        
        <div class="action-box" style="border-left-color: #f472b6;">
            <strong>🏷️ Tindakan Strategis:</strong>
            Prioritaskan cuci gudang atau retur ke supplier. Cairkan kembali aset mati ini menjadi uang tunai (Cash) untuk modal operasional lain.
        </div>
    </div>
</div>

<!-- 5. Growth -->
<div class="guide-section" style="border-top: 4px solid #10b981;">
    <div class="guide-header">
        <div class="guide-icon">📈</div>
        <div class="guide-title">Tren Pertumbuhan Prinsipel</div>
    </div>
    <div class="guide-content">
        <p>Melihat momentum popularitas merk dagang secara mingguan untuk menentukan strategi stok masa depan.</p>
        
        <h4>Cara Membaca:</h4>
        <p>Bandingkan omzet 7 hari terakhir vs 14 hari terakhir. Panah naik menunjukkan merk tersebut sedang disukai pasar.</p>
        
        <div class="action-box" style="border-left-color: #10b981;">
            <strong>🚀 Tindakan Strategis:</strong>
            Pindahkan alokasi modal dari merk yang sedang turun (Trend Turun) ke merk yang sedang naik daun (Trend Naik) untuk memaksimalkan cuan.
        </div>
    </div>
</div>

<!-- 6. Discounts -->
<div class="guide-section">
    <div class="guide-header">
        <div class="guide-icon">💸</div>
        <div class="guide-title">Evaluasi Efektivitas Diskon</div>
    </div>
    <div class="guide-content">
        <p>Menghitung Rasio "Bakar Uang" (Total Diskon) dibandingkan dengan pendapatan bersih yang masuk.</p>
        
        <h4>Cara Membaca:</h4>
        <p>Rasio ideal biasanya di bawah 10%. Jika rasio tinggi tapi omzet tidak naik signifikan, berarti strategi diskon Anda tidak efisien.</p>
        
        <div class="action-box">
            <strong>💰 Tindakan Strategis:</strong>
            Evaluasi kembali program promosi pada prinsipel dengan rasio diskon tinggi. Ganti strategi diskon tunai dengan bonus barang jika diperlukan.
        </div>
    </div>
</div>

<!-- 7. Anomalies -->
<div class="guide-section">
    <div class="guide-header">
        <div class="guide-icon">🕵️</div>
        <div class="guide-title">Audit Retur & Anomali</div>
    </div>
    <div class="guide-content">
        <p>Kontrol kualitas pengiriman dan integritas data salesman di lapangan.</p>
        
        <h4>Cara Membaca:</h4>
        <p>Return Rate di atas 2% dianggap sebagai anomali yang perlu diselidiki sumbernya.</p>
        
        <div class="action-box" style="border-left-color: var(--warning);">
            <strong>🛡️ Tindakan Strategis:</strong>
            Lakukan audit acak (Spot Check) pada salesman dengan retur tinggi. Cek apakah ada indikasi pemalsuan nota (Sales Fiktif) untuk mengejar target.
        </div>
    </div>
</div>

@endsection
