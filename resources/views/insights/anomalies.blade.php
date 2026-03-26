@extends('layouts.app')
@section('title', 'Audit Retur & Anomali')
@section('content')
<div class="mb-4 d-flex justify-content-between align-items-center" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <a href="{{ route('insights.index') }}" class="btn-back" style="text-decoration: none; color: var(--accent); font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">← Kembali</a>
        <h1 style="font-size: 1.5rem; font-weight: 700;">🕵️ Audit Retur & Anomali</h1>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Monitoring return rate per salesman periode <strong>90 Hari Terakhir</strong>.</p>
    </div>

    <form method="GET" action="{{ route('insights.anomalies') }}" style="display: flex; gap: 0.75rem; align-items: center; background: var(--bg-card); padding: 0.5rem 1rem; border-radius: 12px; border: 1px solid var(--border);">
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
<div class="main-card mb-4" style="background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border); padding: 1.5rem; margin-bottom: 1.5rem; border-left: 4px solid var(--warning);">
    <h3 style="font-size: 1rem; margin-bottom: 0.5rem; color: var(--text-primary); display: flex; align-items: center; gap: 0.5rem;">📖 Cara Membaca & Bertindak</h3>
    <p style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.5;">
        Waspadai salesman dengan <strong>Return Rate > 2%</strong>. Ini adalah indikasi awal "Kanvas Fiktif" atau masalah kualitas barang di rute tersebut. 
        Segera lakukan audit lapangan pada rute tersebut.
    </p>
</div>

<div class="grid-layout" style="display: grid; grid-template-columns: 1fr 350px; gap: 1.5rem; align-items: start;">
    <div class="main-card" style="background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border); padding: 1.5rem;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border); text-align: left;">
                    <th style="padding: 1rem 0.5rem; color: var(--text-secondary); font-size: 0.8rem; text-transform: uppercase;">Salesman</th>
                    <th style="padding: 1rem 0.5rem; color: var(--text-secondary); font-size: 0.8rem; text-transform: uppercase; text-align: right;">Nilai Gross</th>
                    <th style="padding: 1rem 0.5rem; color: var(--text-secondary); font-size: 0.8rem; text-transform: uppercase; text-align: right;">Nilai Retur</th>
                    <th style="padding: 1rem 0.5rem; color: var(--text-secondary); font-size: 0.8rem; text-transform: uppercase; text-align: right;">Return Rate</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data as $item)
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 1rem 0.5rem;"><strong style="color: var(--text-primary);">{{ $item->salesman }}</strong></td>
                        <td style="padding: 1rem 0.5rem; text-align: right;">Rp {{ number_format($item->gross_sales, 0, ',', '.') }}</td>
                        <td style="padding: 1rem 0.5rem; text-align: right; color: var(--danger);">Rp {{ number_format($item->return_value, 0, ',', '.') }}</td>
                        <td style="padding: 1rem 0.5rem; text-align: right;"><span class="badge badge-danger">{{ number_format($item->return_rate, 2) }}%</span></td>
                    </tr>
                @empty
                    <tr><td colspan="4" style="text-align: center; padding: 2rem; color: var(--text-muted);">Tidak ada anomali terdeteksi (> 2%).</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="sidebar">
        <div class="impact-box" style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(239, 68, 68, 0.1)); border: 1px solid var(--warning); border-radius: 16px; padding: 1.5rem;">
            <h3 style="font-size: 1rem; margin-bottom: 1rem; color: var(--warning); display: flex; align-items: center; gap: 0.5rem;">🛡️ Pencegahan Masalah</h3>
            <ul style="list-style: none; color: var(--text-primary); font-size: 0.875rem; display: flex; flex-direction: column; gap: 1rem;">
                <li>🛑 <strong>Audit Sales:</strong> Salesman dengan return rate tinggi perlu diaudit apakah ada manipulasi data (kanvas fiktif) untuk mengejar target.</li>
                <li>🏢 <strong>Gudang:</strong> Jika retur masif terjadi pada produk tertentu, cek kualitas penyimpanan di gudang atau pengiriman.</li>
            </ul>
        </div>
    </div>
</div>
@endsection
