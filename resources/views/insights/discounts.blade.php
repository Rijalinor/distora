@extends('layouts.app')
@section('title', 'Efektivitas Diskon')
@section('content')
<div class="mb-4 d-flex justify-content-between align-items-center" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <a href="{{ route('insights.index') }}" class="btn-back" style="text-decoration: none; color: var(--accent); font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">← Kembali</a>
        <h1 style="font-size: 1.5rem; font-weight: 700;">💸 Evaluasi Efektivitas Diskon</h1>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Analisis rasio biaya promo vs pendapatan bersih periode <strong>90 Hari Terakhir</strong>.</p>
    </div>

    <form method="GET" action="{{ route('insights.discounts') }}" style="display: flex; gap: 0.75rem; align-items: center; background: var(--bg-card); padding: 0.5rem 1rem; border-radius: 12px; border: 1px solid var(--border);">
        <label for="branch" style="font-size: 0.85rem; color: var(--text-secondary); font-weight: 600;">Wilayah:</label>
        <select name="branch" id="branch" onchange="this.form.submit()" style="padding: 0.4rem; border: none; background: transparent; color: var(--text-primary); font-weight: 700; outline: none; cursor: pointer;">
            <option value="all" {{ $selected_branch === 'all' ? 'selected' : '' }}>Semua Cabang</option>
            <option value="OBM_01" {{ $selected_branch === 'OBM_01' ? 'selected' : '' }}>Banjarmasin (OBM_01)</option>
            <option value="OBM_02" {{ $selected_branch === 'OBM_02' ? 'selected' : '' }}>Barabai (OBM_02)</option>
            <option value="OBM_03" {{ $selected_branch === 'OBM_03' ? 'selected' : '' }}>Batulicin (OBM_03)</option>
        </select>
    </form>
</div>

<!-- Cara Membaca Card -->
<div class="main-card mb-4" style="background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border); padding: 1.5rem; margin-bottom: 1.5rem; border-left: 4px solid #a855f7;">
    <h3 style="font-size: 1rem; margin-bottom: 0.5rem; color: var(--text-primary); display: flex; align-items: center; gap: 0.5rem;">📖 Cara Membaca & Bertindak</h3>
    <p style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.5;">
        Monitor <strong>Rasio Diskon</strong>. Jika > 10% namun omzet tidak naik signifikan, berarti strategi diskon "bocor" atau tidak efisien. 
        Evaluasi kembali margin profit setelah dipotong biaya promo ini.
    </p>
</div>

<div class="grid-layout" style="display: grid; grid-template-columns: 1fr 350px; gap: 1.5rem; align-items: start;">
    <div class="main-card" style="background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border); padding: 1.5rem;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border); text-align: left;">
                    <th style="padding: 1rem 0.5rem; color: var(--text-secondary); font-size: 0.8rem; text-transform: uppercase;">Prinsipel</th>
                    <th style="padding: 1rem 0.5rem; color: var(--text-secondary); font-size: 0.8rem; text-transform: uppercase; text-align: right;">Biaya Diskon</th>
                    <th style="padding: 1rem 0.5rem; color: var(--text-secondary); font-size: 0.8rem; text-transform: uppercase; text-align: right;">Net Revenue</th>
                    <th style="padding: 1rem 0.5rem; color: var(--text-secondary); font-size: 0.8rem; text-transform: uppercase; text-align: right;">Rasio</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $item)
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 1rem 0.5rem;"><strong style="color: var(--text-primary); font-size: 0.85rem;">{{ $item->principle }}</strong></td>
                        <td style="padding: 1rem 0.5rem; text-align: right; color: var(--warning);">Rp {{ number_format($item->total_discount, 0, ',', '.') }}</td>
                        <td style="padding: 1rem 0.5rem; text-align: right; color: var(--success);">Rp {{ number_format($item->net_sales, 0, ',', '.') }}</td>
                        <td style="padding: 1rem 0.5rem; text-align: right;">
                             <span class="badge {{ $item->discount_ratio > 10 ? 'badge-danger' : 'badge-success' }}">{{ number_format($item->discount_ratio, 2) }}%</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="sidebar">
        <div class="impact-box" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(168, 85, 247, 0.1)); border: 1px solid #a855f7; border-radius: 16px; padding: 1.5rem; margin-bottom: 1.5rem;">
            <h3 style="font-size: 1rem; margin-bottom: 1rem; color: #a855f7; display: flex; align-items: center; gap: 0.5rem;">📈 Dampak Bisnis</h3>
            <ul style="list-style: none; color: var(--text-primary); font-size: 0.875rem; display: flex; flex-direction: column; gap: 1rem;">
                <li>🔥 <strong>Rasio > 10%:</strong> Evaluasi efisiensi promo. Pastikan diskon yang besar diikuti dengan volume kuantitas yang sebanding.</li>
                <li>✅ <strong>Rasio < 3%:</strong> Peluang untuk sedikit menaikkan promo guna menarik lebih banyak outlet baru.</li>
            </ul>
        </div>
    </div>
</div>
@endsection
