@extends('layouts.app')

@section('title', 'Segmentasi Outlet (RFM)')

@section('content')
<div class="mb-4">
    <a href="{{ route('insights.index') }}" class="btn-back" style="text-decoration: none; color: var(--accent); font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
        ← Kembali ke Pusat Kendali
    </a>
    <div class="d-flex justify-content-between align-items-center" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 700;">💎 Segmentasi Outlet (RFM)</h1>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Analisis periode <strong>90 Hari Terakhir</strong> untuk menentukan prioritas kunjungan.</p>
        </div>
        <form method="GET" action="{{ route('insights.rfm') }}" style="background: var(--bg-card); padding: 0.5rem 1rem; border-radius: 12px; border: 1px solid var(--border);">
            <select name="branch" onchange="this.form.submit()" style="padding: 0.4rem; border: none; background: transparent; color: var(--text-primary); font-weight: 700; outline: none;">
                <option value="all" {{ $selected_branch === 'all' ? 'selected' : '' }}>Semua Cabang</option>
                <option value="OBM_01" {{ $selected_branch === 'OBM_01' ? 'selected' : '' }}>Banjarmasin (OBM_01)</option>
                <option value="OBM_02" {{ $selected_branch === 'OBM_02' ? 'selected' : '' }}>Barabai (OBM_02)</option>
                <option value="OBM_03" {{ $selected_branch === 'OBM_03' ? 'selected' : '' }}>Batulicin (OBM_03)</option>
            </select>
</div>

<!-- Cara Membaca Card -->
<div class="main-card mb-4" style="background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border); padding: 1.5rem; margin-bottom: 1.5rem; border-left: 4px solid var(--accent);">
    <h3 style="font-size: 1rem; margin-bottom: 0.5rem; color: var(--text-primary); display: flex; align-items: center; gap: 0.5rem;">📖 Cara Membaca & Bertindak</h3>
    <p style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.5;">
        Gunakan data ini untuk membagi prioritas kunjungan Sales. Fokus utama adalah pada status <strong>Sleeper</strong> 
        (Toko besar yang sudah >14 hari tidak belanja). Jangan biarkan mereka lepas ke kompetitor. 
        Toko <strong>Sultan</strong> adalah prioritas pelayanan harga dan stok.
    </p>
</div>

<div class="grid-layout" style="display: grid; grid-template-columns: 1fr 350px; gap: 1.5rem; align-items: start;">
    <div class="main-card" style="background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border); padding: 1.5rem;">
        <h3 style="font-size: 1rem; margin-bottom: 1.25rem; color: var(--text-primary);">Daftar Outlet Berdasarkan Segmentasi</h3>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border); text-align: left;">
                        <th style="padding: 1rem 0.5rem; color: var(--text-secondary); font-size: 0.8rem; text-transform: uppercase;">Nama Outlet</th>
                        <th style="padding: 1rem 0.5rem; color: var(--text-secondary); font-size: 0.8rem; text-transform: uppercase; text-align: right;">Kunjungan</th>
                        <th style="padding: 1rem 0.5rem; color: var(--text-secondary); font-size: 0.8rem; text-transform: uppercase; text-align: right;">Omzet (Net)</th>
                        <th style="padding: 1rem 0.5rem; color: var(--text-secondary); font-size: 0.8rem; text-transform: uppercase; text-align: right;">Segmen</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data as $item)
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 1rem 0.5rem;"><strong style="color: var(--text-primary);">{{ $item->name }}</strong></td>
                            <td style="padding: 1rem 0.5rem; text-align: right;">{{ $item->frequency }}x</td>
                            <td style="padding: 1rem 0.5rem; text-align: right; color: var(--success);">Rp {{ number_format($item->monetary, 0, ',', '.') }}</td>
                            <td style="padding: 1rem 0.5rem; text-align: right;">
                                <span class="badge badge-{{ $item->color }}" style="padding: 0.35rem 0.75rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600;">
                                    {{ $item->segment }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="sidebar">
        <!-- Impact Box -->
        <div class="impact-box" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(244, 114, 182, 0.1)); border: 1px solid var(--accent); border-radius: 16px; padding: 1.5rem; margin-bottom: 1.5rem;">
            <h3 style="font-size: 1rem; margin-bottom: 1rem; color: var(--accent); display: flex; align-items: center; gap: 0.5rem;">
                🎯 Rekomendasi & Dampak
            </h3>
            <ul style="list-style: none; color: var(--text-primary); font-size: 0.875rem; display: flex; flex-direction: column; gap: 0.75rem;">
                <li>🚀 <strong>Sultan:</strong> Berikan layanan premium dan pastikan stok tidak pernah kosong untuk mereka.</li>
                <li>📉 <strong>Sleeper:</strong> WAJIB KUNJUNG hari ini. Mereka adalah toko besar yang sudah lama tidak order, ada risiko pindah ke kompetitor.</li>
                <li>🛠️ <strong>Regular:</strong> Tawarkan program bundling untuk menaikkan nilai transaksi mereka.</li>
            </ul>
        </div>

        <div class="chart-card" style="background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 1.5rem;">
            <h3 style="font-size: 0.9rem; margin-bottom: 1.25rem; color: var(--text-secondary);">Komposisi Segmen</h3>
            <div style="height: 250px;">
                <canvas id="rfmChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('rfmChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Sultan', 'Gold', 'Sleeper', 'Regular'],
            datasets: [{
                data: [
                    {{ $summary['sultans'] }}, 
                    {{ $data->where('segment', 'Gold (Growth)')->count() }},
                    {{ $summary['sleepers'] }},
                    {{ $data->where('segment', 'Regular')->count() }}
                ],
                backgroundColor: ['#22c55e', '#3b82f6', '#ef4444', '#94a3b8'],
                borderWidth: 0,
                hoverOffset: 15
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { color: '#8888a0', font: { size: 10 } } }
            }
        }
    });
</script>
@endsection
