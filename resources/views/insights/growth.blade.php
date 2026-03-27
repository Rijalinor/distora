@extends('layouts.app')
@section('title', 'Pertumbuhan Prinsipel')
@section('content')
<div class="mb-4 d-flex justify-content-between align-items-center" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <a href="{{ route('insights.index', ['branch' => $selected_branch, 'period_id' => $activePeriod->id]) }}" class="btn-back" style="text-decoration: none; color: var(--accent); font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">← Kembali</a>
        <h1 style="font-size: 1.5rem; font-weight: 700;">📈 Tren Pertumbuhan Prinsipel</h1>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Perbandingan performa penjualan <strong>{{ $activePeriod->name }}</strong> vs Bulan Sebelumnya.</p>
    </div>

    <form method="GET" action="{{ route('insights.growth') }}" style="display: flex; gap: 0.75rem; align-items: center; background: var(--bg-card); padding: 0.5rem 1rem; border-radius: 12px; border: 1px solid var(--border);">
        <label for="period_id" style="font-size: 0.85rem; color: var(--text-secondary); font-weight: 600;">Periode:</label>
        <select name="period_id" onchange="this.form.submit()" style="padding: 0.4rem; border: none; background: transparent; color: var(--accent-hover); font-weight: 800; outline: none; cursor: pointer;">
            @foreach($allPeriods as $p)
                <option value="{{ $p->id }}" {{ $p->id === $activePeriod->id ? 'selected' : '' }}>
                    {{ $p->name }} {{ $p->status === 'closed' ? '(Closed)' : '' }}
                </option>
            @endforeach
        </select>
        
        <div style="width: 1px; height: 20px; background: var(--border);"></div>

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
<div class="main-card mb-4" style="background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border); padding: 1.5rem; margin-bottom: 1.5rem; border-left: 4px solid #10b981;">
    <h3 style="font-size: 1rem; margin-bottom: 0.5rem; color: var(--text-primary); display: flex; align-items: center; gap: 0.5rem;">📖 Cara Membaca & Bertindak</h3>
    <p style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.5;">
        Monitor <strong>Momentum</strong> mingguan. Alokasikan modal lebih banyak ke merk yang trend-nya naik (Hijau) 
        dan selidiki penyebab penurunan pada merk yang trend-nya turun (Merah).
    </p>
</div>

<div class="grid-layout" style="display: grid; grid-template-columns: 1fr 350px; gap: 1.5rem; align-items: start;">
    <div class="main-card" style="background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border); padding: 1.5rem;">
        <div style="height: 300px; margin-bottom: 2rem;">
            <canvas id="growthChart"></canvas>
        </div>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border); text-align: left;">
                    <th style="padding: 1rem 0.5rem; color: var(--text-secondary); font-size: 0.8rem; text-transform: uppercase;">Prinsipel</th>
                    <th style="padding: 1rem 0.5rem; color: var(--text-secondary); font-size: 0.8rem; text-transform: uppercase; text-align: right;">Total Omzet</th>
                    <th style="padding: 1rem 0.5rem; color: var(--text-secondary); font-size: 0.8rem; text-transform: uppercase; text-align: right;">Pertumbuhan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $item)
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 1rem 0.5rem;"><strong style="color: var(--text-primary); font-size: 0.85rem;">{{ $item->principle }}</strong></td>
                        <td style="padding: 1rem 0.5rem; text-align: right;">Rp {{ number_format($item->current, 0, ',', '.') }}</td>
                        <td style="padding: 1rem 0.5rem; text-align: right;">
                             <span class="badge {{ $item->growth >= 0 ? 'badge-success' : 'badge-danger' }}">
                                 {!! $item->growth >= 0 ? '↑' : '↓' !!} {{ abs($item->growth) }}%
                             </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="sidebar">
        <div class="impact-box" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(99, 102, 241, 0.1)); border: 1px solid #10b981; border-radius: 16px; padding: 1.5rem;">
            <h3 style="font-size: 1rem; margin-bottom: 1rem; color: #10b981; display: flex; align-items: center; gap: 0.5rem;">🚀 Analisis Momentum</h3>
            <p style="color: var(--text-primary); font-size: 0.875rem; line-height: 1.5;">Data ini menunjukkan prinsipel mana yang sedang "booming" di pasar dan mana yang sedang turun. Gunakan untuk negosiasi alokasi stok berikutnya.</p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('growthChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: {!! json_encode(array_values(collect($data)->pluck('principle')->toArray())) !!},
            datasets: [{
                label: 'Pertumbuhan %',
                data: {!! json_encode(array_values(collect($data)->pluck('growth')->toArray())) !!},
                backgroundColor: {!! json_encode(array_values(collect($data)->map(fn($item) => $item->growth >= 0 ? '#10b981' : '#ef4444')->toArray())) !!},
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#8888a0' } },
                x: { ticks: { display: false } }
            },
            plugins: { legend: { display: false } }
        }
    });
</script>
@endsection
