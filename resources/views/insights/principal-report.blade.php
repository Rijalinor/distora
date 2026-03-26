@extends('layouts.app')
@section('title', 'Principal Intelligence - ' . $selected_principle)

@section('content')
<div class="mb-4 d-flex justify-content-between align-items-center" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <a href="{{ route('insights.index') }}" class="btn-back" style="text-decoration: none; color: var(--accent); font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">← Kembali</a>
        <h1 style="font-size: 1.5rem; font-weight: 700;">🏪 Principal Intelligence: {{ $selected_principle }}</h1>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Laporan menyeluruh performa brand dalam <strong>90 Hari Terakhir</strong>.</p>
    </div>

    <div style="display: flex; gap: 1rem;">
        <form method="GET" action="{{ route('insights.principal-report') }}" id="filterForm" style="display: flex; gap: 0.75rem; align-items: center; background: var(--bg-card); padding: 0.5rem 1rem; border-radius: 12px; border: 1px solid var(--border);">
            <label style="font-size: 0.85rem; color: var(--text-secondary); font-weight: 600;">Pilih Prinsipel:</label>
            <select name="principle" onchange="this.form.submit()" style="padding: 0.4rem; border: none; background: transparent; color: var(--text-primary); font-weight: 700; outline: none; cursor: pointer;">
                @foreach($principles as $p)
                    <option value="{{ $p }}" {{ $selected_principle == $p ? 'selected' : '' }}>{{ $p }}</option>
                @endforeach
            </select>

            <div style="width: 1px; height: 20px; background: var(--border);"></div>

            <label style="font-size: 0.85rem; color: var(--text-secondary); font-weight: 600;">Wilayah:</label>
            <select name="branch" onchange="this.form.submit()" style="padding: 0.4rem; border: none; background: transparent; color: var(--text-primary); font-weight: 700; outline: none; cursor: pointer;">
                <option value="all" {{ $selected_branch === 'all' ? 'selected' : '' }}>Semua Cabang</option>
                <option value="OBM_01" {{ $selected_branch === 'OBM_01' ? 'selected' : '' }}>Banjarmasin (OBM_01)</option>
                <option value="OBM_02" {{ $selected_branch === 'OBM_02' ? 'selected' : '' }}>Barabai (OBM_02)</option>
                <option value="OBM_03" {{ $selected_branch === 'OBM_03' ? 'selected' : '' }}>Batulicin (OBM_03)</option>
            </select>
        </form>
    </div>
</div>

@if($summary)
<!-- Summary Cards -->
<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 2rem;">
    <div style="background: var(--bg-card); padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border); border-top: 4px solid var(--accent);">
        <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600; text-transform: uppercase; margin-bottom: 0.5rem;">Total Omzet (90 Hari)</div>
        <div style="font-size: 1.5rem; font-weight: 800; color: var(--text-primary);">Rp {{ number_format($summary->total_value, 0, ',', '.') }}</div>
    </div>
    <div style="background: var(--bg-card); padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border); border-top: 4px solid #10b981;">
        <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600; text-transform: uppercase; margin-bottom: 0.5rem;">Volume Terjual</div>
        <div style="font-size: 1.5rem; font-weight: 800; color: #10b981;">{{ number_format($summary->total_qty, 0, ',', '.') }} <span style="font-size: 0.9rem; font-weight: 400;">Unit</span></div>
    </div>
    <div style="background: var(--bg-card); padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border); border-top: 4px solid #f59e0b;">
        <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600; text-transform: uppercase; margin-bottom: 0.5rem;">Outlet Coverage</div>
        <div style="font-size: 1.5rem; font-weight: 800; color: #f59e0b;">{{ number_format($summary->total_outlets, 0, ',', '.') }} <span style="font-size: 0.9rem; font-weight: 400;">Toko Aktif</span></div>
    </div>
</div>

<!-- Main Section: Trends -->
<div style="background: var(--bg-card); padding: 2rem; border-radius: 16px; border: 1px solid var(--border); margin-bottom: 2rem;">
    <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">📉 Tren Penjualan Mingguan</h3>
    <div style="height: 350px;">
        <canvas id="trendChart"></canvas>
    </div>
</div>

<!-- Analytics Grid -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
    <!-- Top Products -->
    <div style="background: var(--bg-card); padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border);">
        <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 1.2rem; color: var(--accent);">🏆 Produk Terlaris (Top 10)</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <tbody>
                @foreach($topProducts as $p)
                <tr style="border-bottom: 1px solid var(--border);">
                    <td style="padding: 0.75rem 0.5rem;"><strong style="color: var(--text-primary); font-size: 0.85rem;">{{ $p->name }}</strong></td>
                    <td style="padding: 0.75rem 0.5rem; text-align: right; font-weight: 700; color: var(--text-primary);">Rp {{ number_format($p->value, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Top Outlets -->
    <div style="background: var(--bg-card); padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border);">
        <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 1.2rem; color: #f59e0b;">🏰 Outlet Terloyal (Top 10)</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <tbody>
                @foreach($topOutlets as $o)
                <tr style="border-bottom: 1px solid var(--border);">
                    <td style="padding: 0.75rem 0.5rem;"><strong style="color: var(--text-primary); font-size: 0.85rem;">{{ $o->name }}</strong></td>
                    <td style="padding: 0.75rem 0.5rem; text-align: right; font-weight: 700; color: #f59e0b;">Rp {{ number_format($o->value, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Salesman Breakdown -->
<div style="background: var(--bg-card); padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border);">
    <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 1.2rem; color: #10b981;">💼 Salesman Force (Kontribusi Merek)</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem;">
        @foreach($topSalesmen as $s)
        <div style="background: var(--bg-primary); padding: 1rem; border-radius: 12px; border: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
            <div style="font-weight: 600; color: var(--text-primary);">{{ $s->name ?: 'Tanpa Nama' }}</div>
            <div style="color: #10b981; font-weight: 700;">Rp {{ number_format($s->value / 1000000, 1) }}jt</div>
        </div>
        @endforeach
    </div>
</div>

@else
<div style="text-align: center; padding: 5rem; background: var(--bg-card); border-radius: 20px; border: 1px dashed var(--border);">
    <div style="font-size: 3rem; margin-bottom: 1rem;">🔍</div>
    <h3 style="color: var(--text-primary);">Belum ada data untuk Prinsipel ini.</h3>
    <p style="color: var(--text-muted);">Silakan pilih Prinsipel lain dari dropdown di atas.</p>
</div>
@endif

@if($summary)
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctxTrend = document.getElementById('trendChart').getContext('2d');
        new Chart(ctxTrend, {
            type: 'line',
            data: {
                labels: {!! json_encode($trend->pluck('week_start')->map(fn($d) => date('d M', strtotime($d)))->toArray()) !!},
                datasets: [{
                    label: 'Omzet Mingguan',
                    data: {!! json_encode($trend->pluck('total')->toArray()) !!},
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#6366f1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255,255,255,0.05)' },
                        ticks: { 
                            color: '#8888a0',
                            callback: value => 'Rp ' + (value/1000000).toFixed(1) + 'jt'
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#8888a0' }
                    }
                }
            }
        });
    });
</script>
@endif

@endsection
