@extends('layouts.app')
@section('title', 'Analytics & Visualisasi')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 700;">📈 Analytics & Visualisasi</h1>
            <p style="color: var(--text-muted); font-size: 0.95rem;">Insight mendalam tentang performa bisnis</p>
        </div>
        <div style="display: flex; gap: 0.75rem; align-items: center;">
            <!-- Period Selector -->
            <select onchange="window.location.href='{{ route('analytics.index') }}?branch={{ $branch }}&period_id=' + this.value" style="padding: 0.5rem 1rem; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-card); color: var(--accent-hover); font-weight: 700; cursor: pointer; outline: none;">
                @foreach($allPeriods as $p)
                    <option value="{{ $p->id }}" {{ $p->id === $activePeriod->id ? 'selected' : '' }}>
                        {{ $p->name }} {{ $p->status === 'closed' ? '(Closed)' : '' }}
                    </option>
                @endforeach
            </select>

            <form id="branchFilterForm" action="{{ route('analytics.index') }}" method="GET">
                <input type="hidden" name="period_id" value="{{ $activePeriod->id }}">
                <select name="branch" onchange="document.getElementById('branchFilterForm').submit()" style="padding: 0.5rem 1rem; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-card); color: var(--text-primary); font-weight: 500; cursor: pointer; outline: none;">
                    <option value="all" {{ $branch == 'all' ? 'selected' : '' }}>Semua Cabang</option>
                    <option value="OBM_01" {{ $branch == 'OBM_01' ? 'selected' : '' }}>📌 Banjarmasin (OBM_01)</option>
                    <option value="OBM_02" {{ $branch == 'OBM_02' ? 'selected' : '' }}>📌 Barabai (OBM_02)</option>
                    <option value="OBM_03" {{ $branch == 'OBM_03' ? 'selected' : '' }}>📌 Batulicin (OBM_03)</option>
                </select>
            </form>
        </div>
    </div>
 
    <!-- Summary KPI -->
    <div class="stats-grid" style="margin-bottom: 2rem;">
        <div class="stat-card">
            <div class="stat-label">Pendapatan Bersih (Net)</div>
            <div class="stat-value" style="color: #6366f1;">Rp {{ number_format($netSales, 0, ',', '.') }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Penjualan (Gross)</div>
            <div class="stat-value text-success">Rp {{ number_format($totalSales, 0, ',', '.') }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Return</div>
            <div class="stat-value text-danger">Rp {{ number_format($totalSales - $netSales, 0, ',', '.') }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Produk Terjual</div>
            <div class="stat-value">{{ number_format($totalProducts) }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Status Periode</div>
            <div class="stat-value" style="font-size: 1.25rem !important;">
                @if($activePeriod->status === 'active')
                    <span style="color: var(--success);">ACTIVE</span>
                @else
                    <span style="color: var(--warning);">CLOSED</span>
                @endif
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
        <!-- Sales Trend (Line Chart) -->
        <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem;">
            <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 1rem;">Tren Penjualan (Gross vs Net)</h3>
            <div style="position: relative; height: 300px; width: 100%;">
                <canvas id="trendChart"></canvas>
            </div>
        </div>

        <!-- Sales by Principle (Donut Chart) -->
        <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem;">
            <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 1rem;">Share per Principle</h3>
            <div style="position: relative; height: 300px; width: 100%; display: flex; justify-content: center;">
                <canvas id="principleChart"></canvas>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
        <!-- Pareto Chart (Combined Bar & Line) -->
        <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem;">
            <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 1rem;">Grafik Pareto (Top 50 Produk)</h3>
            <div style="position: relative; height: 350px; width: 100%;">
                <canvas id="paretoChart"></canvas>
            </div>
        </div>

        <!-- Pareto Analysis Table -->
        <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                <h3 style="font-size: 1rem; font-weight: 600;">Analisis Kelas ABC (Pareto)</h3>
                <span class="badge badge-warning">Insight</span>
            </div>
            
            <div style="background: var(--bg-input); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border-left: 4px solid var(--warning);">
                <p style="font-size: 0.9rem; color: var(--text-secondary); margin: 0; line-height: 1.5;">
                    Dari total <strong>{{ number_format($totalProducts) }}</strong> produk,<br> 
                    <strong style="color: var(--success);">Kelas A (Prioritas): {{ number_format($paretoCount80) }} produk</strong> pertama 
                    (sekitar {{ $totalProducts > 0 ? number_format(($paretoCount80/$totalProducts)*100, 1) : 0 }}%) 
                    menyumbang <strong>80%</strong> total pendapatan.
                </p>
            </div>

            <!-- Note: Displaying top 100 max in table for performance -->
            <div style="max-height: 250px; overflow-y: auto; padding-right: 0.5rem; border: 1px solid var(--border); border-radius: 8px;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                    <thead style="position: sticky; top: 0; background: var(--bg-card); border-bottom: 1px solid var(--border); z-index: 10;">
                        <tr>
                            <th style="padding: 0.5rem; text-align: center; width: 40px;">#</th>
                            <th style="padding: 0.5rem; text-align: left;">Produk</th>
                            <th style="padding: 0.5rem; text-align: center;">Kelas</th>
                            <th style="padding: 0.5rem; text-align: right;">Kumulatif %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(array_slice($paretoData, 0, 100) as $p)
                            <tr style="{{ $p['class'] == 'A' ? 'background: rgba(16, 185, 129, 0.05);' : ($p['class'] == 'B' ? 'background: rgba(234, 179, 8, 0.05);' : '') }}">
                                <td style="padding: 0.5rem; text-align: center; border-bottom: 1px solid var(--border);">{{ $p['rank'] }}</td>
                                <td style="padding: 0.5rem; color: var(--text-primary); border-bottom: 1px solid var(--border);">
                                    <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 200px;" title="{{ $p['name'] }}">
                                        {{ $p['name'] }}
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">Rp {{ number_format($p['total'], 0, ',', '.') }}</div>
                                </td>
                                <td style="padding: 0.5rem; text-align: center; border-bottom: 1px solid var(--border);">
                                    @if($p['class'] == 'A')
                                        <span class="badge" style="background: rgba(16, 185, 129, 0.2); color: #10b981;">A</span>
                                    @elseif($p['class'] == 'B')
                                        <span class="badge" style="background: rgba(234, 179, 8, 0.2); color: #eab308;">B</span>
                                    @else
                                        <span class="badge" style="background: rgba(107, 114, 128, 0.2); color: #9ca3af;">C</span>
                                    @endif
                                </td>
                                <td style="padding: 0.5rem; text-align: right; border-bottom: 1px solid var(--border); font-weight: 600; {{ $p['class'] == 'A' ? 'color: var(--success);' : ($p['class'] == 'B' ? 'color: var(--warning);' : 'color: var(--text-muted);') }}">
                                    {{ $p['cumulative_pct'] }}%
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if(count($paretoData) > 100)
                <div style="text-align: center; font-size: 0.8rem; color: var(--text-muted); margin-top: 0.5rem;">
                    Menampilkan 100 produk teratas dari total {{ number_format($totalProducts) }} produk.
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Common Chart.js Defaults for Dark Mode
    Chart.defaults.color = '#9ca3af';
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.scale.grid.color = 'rgba(255, 255, 255, 0.05)';
    
    // 1. Trend Chart (Line)
    const trendCtx = document.getElementById('trendChart').getContext('2d');
    const trendData = @json($grossNetTrend);
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: trendData.map(d => d.date.substring(5, 10)), // DD-MM
            datasets: [
                {
                    label: 'Net Sales',
                    data: trendData.map(d => d.net),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
                },
                {
                    label: 'Gross',
                    data: trendData.map(d => d.gross),
                    borderColor: '#6366f1',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    tension: 0.3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                tooltip: { mode: 'index', intersect: false }
            },
            scales: {
                y: { beginAtZero: true, ticks: { callback: v => 'Rp ' + (v/1000000).toFixed(1) + 'M' } }
            }
        }
    });

    // 2. Principle Share (Doughnut)
    const principleCtx = document.getElementById('principleChart').getContext('2d');
    const principleData = @json($byPrinciple->take(6)); // Top 6 for chart
    new Chart(principleCtx, {
        type: 'doughnut',
        data: {
            labels: principleData.map(d => d.principle.substring(0, 15)),
            datasets: [{
                data: principleData.map(d => d.total),
                backgroundColor: ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#3b82f6'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: { position: 'right', labels: { boxWidth: 12, font: { size: 11 } } }
            }
        }
    });

    // 3. Pareto Chart (Combined Bar & Line)
    const paretoCtx = document.getElementById('paretoChart').getContext('2d');
    const pChartData = @json($paretoChartData);
    new Chart(paretoCtx, {
        type: 'bar', // Base type
        data: {
            labels: pChartData.map(d => d.name.substring(0, 15) + '...'),
            datasets: [
                {
                    type: 'line',
                    label: 'Kumulatif %',
                    data: pChartData.map(d => d.cumulative_pct),
                    borderColor: '#f59e0b',
                    backgroundColor: '#f59e0b',
                    borderWidth: 2,
                    tension: 0.3,
                    yAxisID: 'y1',
                    pointRadius: 2,
                },
                {
                    type: 'bar',
                    label: 'Nilai Penjualan',
                    data: pChartData.map(d => d.total),
                    backgroundColor: pChartData.map(d => 
                        d.class === 'A' ? 'rgba(16, 185, 129, 0.8)'   // Green for A
                        : (d.class === 'B' ? 'rgba(234, 179, 8, 0.8)' // Yellow for B
                        : 'rgba(107, 114, 128, 0.8)')                 // Gray for C
                    ),
                    borderRadius: 4,
                    yAxisID: 'y',
                    barPercentage: 1.0,
                    categoryPercentage: 1.0,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) { label += ': '; }
                            if (context.dataset.yAxisID === 'y1') {
                                label += context.parsed.y + '% (Kelas ' + pChartData[context.dataIndex].class + ')';
                            } else {
                                label += 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                x: {
                    ticks: { display: false } // Hide labels to not overwhelm UI
                },
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: { display: true, text: 'Nilai Penjualan', color: '#9ca3af', font: {size: 10} },
                    ticks: { callback: v => (v/1000000).toFixed(0) + 'M' }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: { display: true, text: 'Kumulatif %', color: '#f59e0b', font: {size: 10} },
                    grid: { drawOnChartArea: false },
                    min: 0,
                    max: 100,
                    ticks: { callback: v => v + '%' }
                }
            }
        }
    });
</script>
@endpush
