@extends('layouts.app')

@section('title', 'Salesman Intelligence AI')

@section('content')
<div class="mb-4">
    <div class="mb-4 d-flex justify-content-between align-items-center" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 700;">🧠 Salesman Intelligence AI</h1>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Analisis performa & prediksi pencapaian Sales Force.</p>
        </div>

        <form method="GET" action="{{ route('insights.salesman-intelligence') }}" style="display: flex; gap: 1rem; align-items: center; background: var(--bg-card); padding: 0.5rem 1rem; border-radius: 12px; border: 1px solid var(--border); flex-wrap: wrap;">
            <!-- Filter Cabang -->
            <div style="display: flex; flex-direction: column;">
                <label style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Wilayah</label>
                <select name="branch" onchange="this.form.submit()" style="padding: 0.2rem; border: none; background: transparent; color: var(--text-primary); font-weight: 600; outline: none; cursor: pointer;">
                    <option value="all" {{ $branch === 'all' ? 'selected' : '' }}>Semua Cabang</option>
                    <option value="OBM_01" {{ $branch === 'OBM_01' ? 'selected' : '' }}>Banjarmasin</option>
                    <option value="OBM_02" {{ $branch === 'OBM_02' ? 'selected' : '' }}>Barabai</option>
                    <option value="OBM_03" {{ $branch === 'OBM_03' ? 'selected' : '' }}>Batulicin</option>
                </select>
            </div>

            <div style="width: 1px; height: 30px; background: var(--border);"></div>

            <!-- Filter Periode -->
            <div style="display: flex; flex-direction: column;">
                <label style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Periode Dasar</label>
                <select name="period_id" onchange="this.form.submit()" style="padding: 0.2rem; border: none; background: transparent; color: var(--accent-hover); font-weight: 800; outline: none; cursor: pointer;">
                    @foreach($allPeriods as $p)
                        <option value="{{ $p->id }}" {{ $p->id === $activePeriod->id ? 'selected' : '' }}>
                            {{ $p->name }} {{ $p->status === 'closed' ? '(Closed)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
</div>

<!-- Strategy Matrix Summary -->
<div class="row mb-4" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
    @php
        $stars = $data->filter(fn($s) => $s->ai_trend === 'growing')->count();
        $atRisk = $data->filter(fn($s) => $s->ai_trend === 'declining')->count();
        $steady = $data->count() - $stars - $atRisk;
    @endphp
    
    <div style="background: var(--bg-card); padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border); border-left: 4px solid #10b981;">
        <h3 style="font-size: 0.8rem; color: #10b981; text-transform: uppercase; font-weight: 800; margin-bottom: 0.5rem;">⭐ Performa Bintang</h3>
        <div style="font-size: 2rem; font-weight: 800;">{{ $stars }} <span style="font-size: 1rem; color: var(--text-muted);">Salesman</span></div>
        <p style="font-size: 0.8rem; color: var(--text-secondary);">Salesman dengan tren pertumbuhan positif.</p>
    </div>

    <div style="background: var(--bg-card); padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border); border-left: 4px solid #6366f1;">
        <h3 style="font-size: 0.8rem; color: #6366f1; text-transform: uppercase; font-weight: 800; margin-bottom: 0.5rem;">⚖️ Performa Stabil</h3>
        <div style="font-size: 2rem; font-weight: 800;">{{ $steady }} <span style="font-size: 1rem; color: var(--text-muted);">Salesman</span></div>
        <p style="font-size: 0.8rem; color: var(--text-secondary);">Performance konsisten sesuai rata-rata.</p>
    </div>

    <div style="background: var(--bg-card); padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border); border-left: 4px solid #ef4444;">
        <h3 style="font-size: 0.8rem; color: #ef4444; text-transform: uppercase; font-weight: 800; margin-bottom: 0.5rem;">⚠️ Risiko Penurunan</h3>
        <div style="font-size: 2rem; font-weight: 800;">{{ $atRisk }} <span style="font-size: 1rem; color: var(--text-muted);">Salesman</span></div>
        <p style="font-size: 0.8rem; color: var(--text-secondary);">Butuh perhatian karena tren cenderung turun.</p>
    </div>
</div>

<div class="main-card" style="background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border); padding: 1.5rem;">
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border); text-align: left;">
                    <th style="padding: 1rem; color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase;">Salesman</th>
                    <th style="padding: 1rem; color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase; text-align: right;">Coverage</th>
                    <th style="padding: 1rem; color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase; text-align: right;">Rerata Sales (3bln)</th>
                    <th style="padding: 1rem; color: #6366f1; font-size: 0.75rem; text-transform: uppercase; text-align: right; background: rgba(99, 102, 241, 0.03);">🧠 AI Run Rate</th>
                    <th style="padding: 1rem; color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase; text-align: center;">Tren Pertumbuhan</th>
                    <th style="padding: 1rem; color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase; text-align: center;">Confidence</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $s)
                    <tr style="border-bottom: 1px solid var(--border); transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                        <td style="padding: 1rem;">
                            <div style="font-size: 0.7rem; color: var(--text-muted); font-weight: 700;">#{{ $s->sales_id }}</div>
                            <strong style="color: var(--text-primary); font-size: 0.95rem;">{{ $s->sales_name }}</strong>
                        </td>
                        <td style="padding: 1rem; text-align: right;">
                            <div style="font-weight: 700; color: var(--text-primary);">{{ number_format($s->total_outlets) }} <span style="font-weight: 400; font-size: 0.75rem; color: var(--text-muted);">Toko</span></div>
                            <div style="font-size: 0.7rem; color: var(--text-secondary);">{{ number_format($s->total_tx) }} Transaksi</div>
                        </td>
                        <td style="padding: 1rem; text-align: right; font-weight: 700; color: var(--text-primary);">
                            Rp {{ number_format($s->avg_revenue, 0, ',', '.') }}
                        </td>
                        <td style="padding: 1rem; text-align: right; background: rgba(99, 102, 241, 0.03);">
                            <div style="font-weight: 800; color: #6366f1; font-size: 1rem;">Rp {{ number_format($s->ai_prediction, 0, ',', '.') }}</div>
                            @php $ratio = ($s->ai_prediction / ($s->avg_revenue ?: 1)) * 100 - 100; @endphp
                            <div style="font-size: 0.7rem; color: {{ $ratio >= 0 ? '#10b981' : '#ef4444' }}; font-weight: 700;">
                                {{ $ratio >= 0 ? '+' : '' }}{{ round($ratio, 1) }}% vs Rerata
                            </div>
                        </td>
                        <td style="padding: 1rem; text-align: center;">
                            @if($s->ai_trend === 'growing')
                                <span style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 0.4rem 0.8rem; border-radius: 20px; font-size: 0.75rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.3rem;">
                                    📈 Tumbuh Pesat
                                </span>
                            @elseif($s->ai_trend === 'declining')
                                <span style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 0.4rem 0.8rem; border-radius: 20px; font-size: 0.75rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.3rem;">
                                    📉 Berisiko Turun
                                </span>
                            @else
                                <span style="background: rgba(99, 102, 241, 0.1); color: #6366f1; padding: 0.4rem 0.8rem; border-radius: 20px; font-size: 0.75rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.3rem;">
                                    ⚖️ Stabil
                                </span>
                            @endif
                        </td>
                        <td style="padding: 1rem; text-align: center;">
                            <div style="width: 100px; height: 8px; background: var(--border); border-radius: 4px; margin: 0 auto 0.3rem; overflow: hidden;">
                                <div style="width: {{ $s->ai_confidence }}%; height: 100%; background: {{ $s->ai_confidence > 70 ? '#10b981' : ($s->ai_confidence > 40 ? '#f59e0b' : '#ef4444') }};"></div>
                            </div>
                            <span style="font-size: 0.65rem; color: var(--text-muted); font-weight: 600;">{{ $s->ai_confidence }}% Confidence</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4" style="background: rgba(99, 102, 241, 0.05); padding: 1.5rem; border-radius: 16px; border: 1px dashed #6366f1;">
    <h4 style="font-size: 0.9rem; color: #6366f1; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">📖 Cara Membaca Analisis Salesman</h4>
    <ul style="font-size: 0.8rem; color: var(--text-secondary); line-height: 1.6; padding-left: 1.2rem;">
        <li><strong>AI Run Rate:</strong> Estimasi pencapaian total bulan depan berdasarkan pola penjualan 6 bulan terakhir.</li>
        <li><strong>Tren Pertumbuhan:</strong> Dihitung menggunakan <em>Weighted Linear Regression</em>, di mana performa bulan terbaru memiliki pengaruh lebih besar.</li>
        <li><strong>Confidence:</strong> Semakin tinggi persentasenya, semakin konsisten pola penjualan salesman tersebut (semakin akurat prediksinya).</li>
    </ul>
</div>
@endsection
