@extends('layouts.app')
@section('title', 'Principal Intelligence - ' . $selected_principle)

@section('content')
<div class="mb-4 d-flex justify-content-between align-items-center" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <a href="{{ route('insights.index', ['branch' => $selected_branch, 'period_id' => $activePeriod->id]) }}" class="btn-back" style="text-decoration: none; color: var(--accent); font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">← Kembali</a>
        <h1 style="font-size: 1.5rem; font-weight: 700;">🏪 Principal Intelligence: {{ $selected_principle }}</h1>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Analisis 360 derajat performa brand untuk <strong>3 bulan terakhir</strong> (hingga {{ $activePeriod->name }}).</p>
    </div>

    <div style="display: flex; gap: 1rem;">
        <form method="GET" action="{{ route('insights.principal-report') }}" id="filterForm" style="display: flex; gap: 1rem; align-items: center; background: var(--bg-card); padding: 0.5rem 1rem; border-radius: 12px; border: 1px solid var(--border); flex-wrap: wrap;">
            <!-- Filter Periode -->
            <div style="display: flex; flex-direction: column;">
                <label style="font-size: 0.75rem; color: var(--text-secondary); font-weight: 600;">Periode:</label>
                <select name="period_id" onchange="this.form.submit()" style="padding: 0.2rem; border: none; background: transparent; color: var(--accent-hover); font-weight: 800; outline: none; cursor: pointer;">
                    @foreach($allPeriods as $p)
                        <option value="{{ $p->id }}" {{ $p->id === $activePeriod->id ? 'selected' : '' }}>
                            {{ $p->name }} {{ $p->status === 'closed' ? '(Closed)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div style="width: 1px; height: 30px; background: var(--border);"></div>

            <div style="display: flex; flex-direction: column;">
                <label style="font-size: 0.75rem; color: var(--text-secondary); font-weight: 600;">Prinsipel:</label>
                <select name="principle" onchange="this.form.submit()" style="padding: 0.4rem; border: none; background: transparent; color: var(--text-primary); font-weight: 700; outline: none; cursor: pointer;">
                    @foreach($principles as $p)
                        <option value="{{ $p }}" {{ $selected_principle == $p ? 'selected' : '' }}>{{ $p }}</option>
                    @endforeach
                </select>
            </div>

            <div style="width: 1px; height: 30px; background: var(--border);"></div>

            <div style="display: flex; flex-direction: column;">
                <label style="font-size: 0.75rem; color: var(--text-secondary); font-weight: 600;">Wilayah:</label>
                <select name="branch" onchange="this.form.submit()" style="padding: 0.4rem; border: none; background: transparent; color: var(--text-primary); font-weight: 700; outline: none; cursor: pointer;">
                    <option value="all" {{ $selected_branch === 'all' ? 'selected' : '' }}>Semua Cabang</option>
                    <option value="OBM_01" {{ $selected_branch === 'OBM_01' ? 'selected' : '' }}>Banjarmasin (OBM_01)</option>
                    <option value="OBM_02" {{ $selected_branch === 'OBM_02' ? 'selected' : '' }}>Barabai (OBM_02)</option>
                    <option value="OBM_03" {{ $selected_branch === 'OBM_03' ? 'selected' : '' }}>Batulicin (OBM_03)</option>
                </select>
            </div>
        </form>
    </div>
</div>

@if($summary)
<!-- Summary Cards -->
<div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 1rem; margin-bottom: 2rem;">
    <!-- Gross Sales -->
    <div style="background: var(--bg-card); padding: 1.2rem; border-radius: 16px; border: 1px solid var(--border); border-top: 4px solid var(--accent);">
        <div style="color: var(--text-muted); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; margin-bottom: 0.5rem;">Omzet Kotor</div>
        <div style="font-size: 1.2rem; font-weight: 800; color: var(--text-primary);">Rp {{ number_format($summary->gross_value, 0, ',', '.') }}</div>
    </div>
    <!-- Returns -->
    <div style="background: var(--bg-card); padding: 1.2rem; border-radius: 16px; border: 1px solid var(--border); border-top: 4px solid #ef4444;">
        <div style="color: var(--text-muted); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; margin-bottom: 0.5rem;">Total Retur</div>
        <div style="font-size: 1.2rem; font-weight: 800; color: #ef4444;">Rp {{ number_format($summary->return_value, 0, ',', '.') }}</div>
    </div>
    <!-- Net Sales (Featured) -->
    <div style="background: var(--bg-card); padding: 1.2rem; border-radius: 16px; border: 1px solid var(--border); border-top: 4px solid #10b981; box-shadow: 0 4px 20px rgba(16, 185, 129, 0.1);">
        <div style="color: var(--text-muted); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; margin-bottom: 0.5rem;">Omzet Bersih ✨</div>
        <div style="font-size: 1.2rem; font-weight: 800; color: #10b981;">Rp {{ number_format($summary->net_value, 0, ',', '.') }}</div>
    </div>
    <!-- Growth -->
    <div style="background: var(--bg-card); padding: 1.2rem; border-radius: 16px; border: 1px solid var(--border); border-top: 4px solid #3b82f6;">
        <div style="color: var(--text-muted); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; margin-bottom: 0.5rem;">Growth (3 Bulan)</div>
        <div style="font-size: 1.2rem; font-weight: 800; color: {{ $growth3m >= 0 ? '#10b981' : '#ef4444' }};">
            {!! $growth3m >= 0 ? '↑' : '↓' !!} {{ abs($growth3m) }}%
        </div>
    </div>
    <!-- Coverage -->
    <div style="background: var(--bg-card); padding: 1.2rem; border-radius: 16px; border: 1px solid var(--border); border-top: 4px solid #f59e0b;">
        <div style="color: var(--text-muted); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; margin-bottom: 0.5rem;">Outlet Coverage</div>
        <div style="font-size: 1.2rem; font-weight: 800; color: #f59e0b;">{{ number_format($summary->total_outlets, 0, ',', '.') }} <span style="font-size: 0.8rem; font-weight: 400;">Toko</span></div>
    </div>
</div>

<div class="grid-layout" style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
    <!-- Trends Section -->
    <div style="background: var(--bg-card); padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border);">
        <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">📈 Tren Penjualan Mingguan</h3>
        <div style="height: 250px; margin-bottom: 2rem;">
            <canvas id="trendChart"></canvas>
        </div>

        <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">📊 Tren Growth Bulanan (%)</h3>
        <div style="height: 200px;">
            <canvas id="growthChart"></canvas>
        </div>
    </div>

    <!-- Alert / Risk Analysis -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        @if($isAiMode && $forecast)
        <div style="background: linear-gradient(135deg, #1e1e2d 0%, #252538 100%); padding: 1.5rem; border-radius: 16px; border: 1px solid rgba(99, 102, 241, 0.3); position: relative; overflow: hidden; margin-bottom: 1.5rem;">
            <div style="position: absolute; top: -10px; right: -10px; font-size: 4rem; opacity: 0.1; transform: rotate(15deg);">✨</div>
            <h3 style="font-size: 0.9rem; font-weight: 700; margin-bottom: 1rem; color: var(--accent); display: flex; align-items: center; gap: 0.5rem;">
                🧠 Smart Forecast (Next Month)
            </h3>
            <div style="font-size: 1.5rem; font-weight: 800; color: #fff; margin-bottom: 0.5rem;">
                Rp {{ number_format($forecast['prediction']/1000000, 1) }}jt
            </div>
            <div style="display: flex; gap: 1rem; align-items: center;">
                <span style="font-size: 0.75rem; padding: 2px 8px; border-radius: 4px; background: {{ $forecast['trend'] == 'growing' ? 'rgba(16, 185, 129, 0.2)' : 'rgba(239, 68, 68, 0.2)' }}; color: {{ $forecast['trend'] == 'growing' ? '#10b981' : '#ef4444' }}; font-weight: bold; text-transform: uppercase;">
                    {{ $forecast['trend'] == 'growing' ? '📈 Growing' : '📉 Declining' }}
                </span>
                <span style="font-size: 0.75rem; color: var(--text-muted);">
                    Confidence: <strong>{{ $forecast['confidence'] }}%</strong>
                </span>
            </div>
            @if(isset($forecast['prediction_interval']))
            <div style="margin-top: 0.6rem; font-size: 0.75rem; color: #cbd5e1;">
                Range: <strong>Rp {{ number_format(($forecast['prediction_interval']['low'] ?? 0)/1000000, 1) }}jt</strong>
                -
                <strong>Rp {{ number_format(($forecast['prediction_interval']['high'] ?? 0)/1000000, 1) }}jt</strong>
            </div>
            @endif
            @if(isset($forecast['model']))
            <div style="margin-top: 0.35rem; font-size: 0.7rem; color: var(--text-muted);">
                Model: <strong style="text-transform: uppercase;">{{ $forecast['model'] }}</strong>
                @if(isset($forecast['validation']['wape']))
                    | WAPE: <strong>{{ $forecast['validation']['wape'] }}%</strong>
                @endif
            </div>
            @endif
            <p style="font-size: 0.7rem; color: var(--text-muted); margin-top: 1rem; line-height: 1.4;">
                *Forecast berbasis seleksi model otomatis pada data {{ count($growthSeries) }} bulan terakhir.
            </p>
        </div>
        @endif

        <!-- Sleeper Alert -->
        <div style="background: rgba(239, 68, 68, 0.05); padding: 1.5rem; border-radius: 16px; border: 1px solid #ef4444;">
            <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 1rem; color: #ef4444; display: flex; align-items: center; gap: 0.5rem;">⚠️ Top Toko Lama Tidak Order</h3>
            <p style="color: var(--text-muted); font-size: 0.8rem; margin-bottom: 1rem;">Daftar toko yang paling lama tidak melakukan pemesanan untuk brand ini.</p>
            @forelse($sleepers as $s)
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                    <div style="font-size: 0.85rem; font-weight: 600; color: var(--text-primary);">
                        {{ $s->name }}
                        <div style="font-size: 0.7rem; color: var(--text-muted); font-weight: 400;">
                            Last Order: {{ \Carbon\Carbon::parse($s->last_date)->translatedFormat('d M Y') }}
                        </div>
                    </div>
                    <div style="font-size: 0.75rem; color: #ef4444; font-weight: 800;">{{ floor($s->days) }} Hari</div>
                </div>
            @empty
                <div style="font-size: 0.85rem; color: #10b981;">✅ Semua toko VIP terpantau aktif.</div>
            @endforelse
        </div>

        <!-- Return Summary -->
        <div style="background: var(--bg-card); padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border); border-left: 4px solid #ef4444;">
            <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 1rem; color: var(--text-primary); display: flex; align-items: center; gap: 0.5rem;">📦 Produk Sering Retur</h3>
            @forelse($returns as $r)
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <div style="font-size: 0.8rem; color: var(--text-primary); max-width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $r->name }}</div>
                    <div style="font-size: 0.8rem; color: #ef4444; font-weight: 700;">Rp {{ number_format($r->value, 0, ',', '.') }}</div>
                </div>
            @empty
                <div style="font-size: 0.85rem; color: #10b981;">✅ Tidak ada retur signifikan.</div>
            @endforelse
        </div>
    </div>
</div>

<div class="grid-layout" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
    <!-- City Breakdown -->
    <div style="background: var(--bg-card); padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border);">
        <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 1.5rem; color: #3b82f6;">🏙️ Analisis Per Kota</h3>
        <div style="height: 250px;">
            <canvas id="cityChart"></canvas>
        </div>
    </div>

    <!-- Top Products -->
    <div style="background: var(--bg-card); padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border);">
        <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 1.2rem; color: var(--accent);">🏆 Produk Terlaris (Pareto)</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <tbody>
                @foreach($topProducts as $p)
                <tr style="border-bottom: 1px solid var(--border);">
                    <td style="padding: 0.5rem;"><strong style="color: var(--text-primary); font-size: 0.8rem;">{{ $p->name }}</strong></td>
                    <td style="padding: 0.5rem; text-align: right; font-weight: 700; color: var(--text-primary); font-size: 0.8rem;">Rp {{ number_format($p->value/1000000, 1) }}jt</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Salesman Per Principle -->
    <div style="background: var(--bg-card); padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border);">
        <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 1.2rem; color: #10b981;">💼 Kontribusi Sales Force</h3>
        @foreach($topSalesmen->take(10) as $s)
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; background: var(--bg-primary); padding: 0.5rem 0.75rem; border-radius: 8px;">
                <div style="font-size: 0.8rem; font-weight: 600; color: var(--text-primary);">{{ $s->name ?: 'Tanpa Nama' }}</div>
                <div style="font-size: 0.8rem; color: #10b981; font-weight: 700;">Rp {{ number_format($s->value/1000000, 1) }}jt</div>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        @if($summary)
        // Trend Chart
        new Chart(document.getElementById('trendChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: {!! json_encode($trend->pluck('week_start')->map(fn($d) => date('d M', strtotime($d)))->toArray()) !!},
                datasets: [{
                    label: 'Omzet',
                    data: {!! json_encode($trend->pluck('total')->toArray()) !!},
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    borderWidth: 3, fill: true, tension: 0.4
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#8888a0', callback: v => 'Rp ' + (v/1000000).toFixed(0) + 'jt' } },
                    x: { grid: { display: false }, ticks: { color: '#8888a0' } }
                }
            }
        });

        // Growth Chart
        new Chart(document.getElementById('growthChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: {!! json_encode(collect($growthSeries)->pluck('month')->toArray()) !!},
                datasets: [{
                    label: 'Growth %',
                    data: {!! json_encode(collect($growthSeries)->pluck('growth')->map(fn($g) => round($g, 1))->toArray()) !!},
                    backgroundColor: {!! json_encode(collect($growthSeries)->pluck('growth')->map(fn($g) => $g >= 0 ? 'rgba(16, 185, 129, 0.6)' : 'rgba(239, 68, 68, 0.6)')->toArray()) !!},
                    borderColor: {!! json_encode(collect($growthSeries)->pluck('growth')->map(fn($g) => $g >= 0 ? '#10b981' : '#ef4444')->toArray()) !!},
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { 
                    legend: { display: false },
                    tooltip: { callbacks: { label: context => context.raw + '%' } }
                },
                scales: {
                    y: { 
                        grid: { color: 'rgba(255,255,255,0.05)' }, 
                        ticks: { color: '#8888a0', callback: v => v + '%' } 
                    },
                    x: { grid: { display: false }, ticks: { color: '#8888a0' } }
                }
            }
        });

        // City Chart
        new Chart(document.getElementById('cityChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: {!! json_encode($cityAnalysis->pluck('city')->map(fn($c) => ucfirst(strtolower($c)))->toArray()) !!},
                datasets: [{
                    data: {!! json_encode($cityAnalysis->pluck('value')->toArray()) !!},
                    backgroundColor: '#3b82f6',
                    borderRadius: 4
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#8888a0', callback: v => (v/1000000).toFixed(0) + 'jt' } },
                    y: { grid: { display: false }, ticks: { color: '#8888a0', font: { size: 10 } } }
                }
            }
        });
        @endif
    });
</script>
@endsection
