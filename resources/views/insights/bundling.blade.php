@extends('layouts.app')
@section('title', 'Rekomendasi Bundling')
@section('content')
<div class="mb-4">
    <a href="{{ route('insights.index') }}" class="btn-back" style="text-decoration: none; color: var(--accent); font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">← Kembali</a>
    <div class="d-flex justify-content-between align-items-center" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 700;">📦 Rekomendasi Bundling (Market Basket)</h1>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Analisis pola belanja <strong>90 Hari Terakhir</strong> untuk mencari pasangan produk paling laku.</p>
        </div>
    </div>
</div>

<!-- Cara Membaca Card -->
<div class="main-card mb-4" style="background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border); padding: 1.5rem; margin-bottom: 1.5rem; border-left: 4px solid var(--info);">
    <h3 style="font-size: 1rem; margin-bottom: 0.5rem; color: var(--text-primary); display: flex; align-items: center; gap: 0.5rem;">📖 Cara Membaca & Bertindak</h3>
    <p style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.5;">
        Daftar ini menunjukkan produk yang sering dibeli bersamaan. Gunakan data ini untuk membuat <strong>Bundling Promo</strong> 
        atau menginstruksikan Salesman melakukan <strong>Cross-Selling</strong>. Jika toko beli Produk A, tawarkan Produk B sebagai pelengkap.
    </p>
</div>

<div class="grid-layout" style="display: grid; grid-template-columns: 1fr 350px; gap: 1.5rem; align-items: start;">
    <div class="main-card" style="background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border); padding: 1.5rem;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border); text-align: left;">
                    <th style="padding: 1rem 0.5rem; color: var(--text-secondary); font-size: 0.8rem; text-transform: uppercase;">Kombinasi Produk A</th>
                    <th style="padding: 1rem 0.5rem; color: var(--text-secondary); font-size: 0.8rem; text-transform: uppercase;">Kombinasi Produk B</th>
                    <th style="padding: 1rem 0.5rem; color: var(--text-secondary); font-size: 0.8rem; text-transform: uppercase; text-align: right;">Frekuensi Bersama</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $item)
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 1rem 0.5rem;"><strong style="color: var(--text-primary); font-size: 0.85rem;">{{ $item->product_a }}</strong></td>
                        <td style="padding: 1rem 0.5rem;"><strong style="color: var(--text-primary); font-size: 0.85rem;">{{ $item->product_b }}</strong></td>
                        <td style="padding: 1rem 0.5rem; text-align: right;"><span class="badge badge-info">{{ $item->times_bought_together }} Nota</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="sidebar">
        <div class="impact-box" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(59, 130, 246, 0.1)); border: 1px solid var(--info); border-radius: 16px; padding: 1.5rem;">
            <h3 style="font-size: 1rem; margin-bottom: 1rem; color: var(--info); display: flex; align-items: center; gap: 0.5rem;">🎯 Dampak & Eksekusi</h3>
            <ul style="list-style: none; color: var(--text-primary); font-size: 0.875rem; display: flex; flex-direction: column; gap: 1rem;">
                <li>📢 <strong>Cross-Selling:</strong> Instruksikan salesman untuk menawarkan Produk B jika toko hanya memesan Produk A.</li>
                <li>🎁 <strong>Bundling Promo:</strong> Buat paket harga khusus (Bundle) untuk pasangan top ini guna mempercepat perputaran stok.</li>
                <li>📍 <strong>Display:</strong> Tempatkan produk-produk ini berdekatan jika ada display khusus/gondola.</li>
            </ul>
        </div>
    </div>
</div>
@endsection
