@extends('layouts.app')
@section('title', 'Audit Retur & Anomali')
@section('content')
<div style="margin-bottom: 2rem;">
    <a href="{{ route('insights.index', ['branch' => $selected_branch, 'period_id' => $activePeriod->id]) }}" class="btn-back" style="text-decoration: none; color: var(--accent); font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
        ← Kembali ke Pusat Kendali
    </a>
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 700;">🕵️ Audit Sales & Anomali</h1>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Analisis pola retur <strong>3 bulan terakhir</strong> (hingga {{ $activePeriod->name }}) untuk deteksi dini masalah.</p>
        </div>
        <form method="GET" action="{{ route('insights.anomalies') }}" style="display: flex; gap: 0.75rem; align-items: center; background: var(--bg-card); padding: 0.5rem 1rem; border-radius: 12px; border: 1px solid var(--border);">
            <select name="period_id" onchange="this.form.submit()" style="padding: 0.4rem; border: none; background: transparent; color: var(--accent-hover); font-weight: 800; outline: none; cursor: pointer;">
                @foreach($allPeriods as $p)
                    <option value="{{ $p->id }}" {{ $p->id === $activePeriod->id ? 'selected' : '' }}>
                        {{ $p->name }} {{ $p->status === 'closed' ? '(Closed)' : '' }}
                    </option>
                @endforeach
            </select>
            
            <div style="width: 1px; height: 20px; background: var(--border);"></div>

            <select name="branch" onchange="this.form.submit()" style="padding: 0.4rem; border: none; background: transparent; color: var(--text-primary); font-weight: 700; outline: none; cursor: pointer;">
                <option value="all" {{ $selected_branch === 'all' ? 'selected' : '' }}>Semua Cabang</option>
                <option value="OBM_01" {{ $selected_branch === 'OBM_01' ? 'selected' : '' }}>Banjarmasin (OBM_01)</option>
                <option value="OBM_02" {{ $selected_branch === 'OBM_02' ? 'selected' : '' }}>Barabai (OBM_02)</option>
                <option value="OBM_03" {{ $selected_branch === 'OBM_03' ? 'selected' : '' }}>Batulicin (OBM_03)</option>
            </select>
        </form>
    </div>
</div>

<div class="grid-layout" style="display: grid; grid-template-columns: 1fr 350px; gap: 1.5rem; align-items: start;">
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <!-- Cara Membaca Card -->
        <div class="main-card" style="background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border); padding: 1.5rem; border-left: 4px solid var(--warning);">
            <h3 style="font-size: 1rem; margin-bottom: 0.5rem; color: var(--text-primary); display: flex; align-items: center; gap: 0.5rem;">📖 Cara Membaca & Bertindak</h3>
            <p style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.5;">
                Waspadai salesman dengan <strong>Return Rate > 2%</strong>. Ini adalah indikasi awal "Kanvas Fiktif" atau masalah kualitas barang di rute tersebut. 
                Segera lakukan audit lapangan pada rute tersebut.
            </p>
        </div>

        <!-- Table Card -->
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
                            <td style="padding: 1rem 0.5rem; text-align: right; color: var(--text-primary);">Rp {{ number_format($item->gross_value, 0, ',', '.') }}</td>
                            <td style="padding: 1rem 0.5rem; text-align: right; color: #ef4444; font-weight: 600;">Rp {{ number_format($item->return_value, 0, ',', '.') }}</td>
                            <td style="padding: 1rem 0.5rem; text-align: right;">
                                <span style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: 700; border: 1px solid rgba(239, 68, 68, 0.2);">
                                    {{ number_format($item->return_rate, 2) }}%
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="text-align: center; padding: 4rem; color: var(--text-muted);">
                            <div style="font-size: 3rem; margin-bottom: 1rem;">✅</div>
                            <div style="font-weight: 600;">Tidak ada anomali terdeteksi (> 2%).</div>
                            <div style="font-size: 0.8rem;">Semua salesman terpantau dalam batas aman.</div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Sidebar Section -->
    <div class="sidebar">
        <div class="impact-box" style="background: var(--bg-card); border: 1px solid var(--border); border-top: 4px solid var(--warning); border-radius: 16px; padding: 1.5rem;">
            <h3 style="font-size: 1rem; margin-bottom: 1.5rem; color: var(--warning); display: flex; align-items: center; gap: 0.5rem;">🛡️ Pencegahan Masalah</h3>
            <ul style="list-style: none; color: var(--text-primary); font-size: 0.875rem; display: flex; flex-direction: column; gap: 1.2rem; padding: 0;">
                <li style="display: flex; gap: 0.75rem; align-items: start;">
                    <span style="font-size: 1.2rem;">🛑</span>
                    <div>
                        <strong style="display: block; margin-bottom: 0.25rem;">Audit Sales:</strong> 
                        <span style="color: var(--text-secondary); font-size: 0.8rem; line-height: 1.4;">Salesman dengan return rate tinggi perlu diaudit apakah ada manipulasi data (kanvas fiktif) untuk mengejar target.</span>
                    </div>
                </li>
                <li style="display: flex; gap: 0.75rem; align-items: start;">
                    <span style="font-size: 1.2rem;">🏢</span>
                    <div>
                        <strong style="display: block; margin-bottom: 0.25rem;">Gudang:</strong> 
                        <span style="color: var(--text-secondary); font-size: 0.8rem; line-height: 1.4;">Jika retur masif terjadi pada produk tertentu, cek kualitas penyimpanan di gudang atau pengiriman.</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</div>
@endsection
