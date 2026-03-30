@extends('layouts.app')

@section('title', 'ML Monitoring')

@section('content')
<style>
    .hint {
        position: relative;
        cursor: help;
        border-bottom: 1px dashed rgba(255, 255, 255, 0.25);
    }
    .hint:hover::after {
        content: attr(data-tip);
        position: absolute;
        left: 0;
        top: 125%;
        min-width: 220px;
        max-width: 320px;
        background: #111827;
        color: #e5e7eb;
        border: 1px solid #374151;
        border-radius: 8px;
        padding: 0.45rem 0.6rem;
        font-size: 0.72rem;
        line-height: 1.35;
        z-index: 20;
        white-space: normal;
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.35);
    }
</style>
<div class="mb-4" style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;">
    <div>
        <a href="{{ route('insights.index', ['branch' => $selected_branch, 'period_id' => $activePeriod->id]) }}" class="btn-back" style="text-decoration:none;color:var(--accent);font-weight:600;display:inline-flex;align-items:center;gap:.5rem;margin-bottom:.5rem;">← Kembali</a>
        <h1 style="font-size:1.5rem;font-weight:800;">📊 ML Monitoring Dashboard</h1>
        <p style="color:var(--text-muted);font-size:.9rem;">Pantau performa model forecast per konteks dan entitas.</p>
    </div>

    <form method="GET" action="{{ route('insights.ml-monitor') }}" style="display:flex;gap:.75rem;align-items:center;background:var(--bg-card);padding:.5rem 1rem;border-radius:12px;border:1px solid var(--border);flex-wrap:wrap;">
        <select name="period_id" onchange="this.form.submit()" style="padding:.35rem;border:none;background:transparent;color:var(--accent-hover);font-weight:800;outline:none;">
            @foreach($allPeriods as $p)
                <option value="{{ $p->id }}" {{ $p->id === $activePeriod->id ? 'selected' : '' }}>{{ $p->name }}</option>
            @endforeach
        </select>
        <select name="branch" onchange="this.form.submit()" style="padding:.35rem;border:none;background:transparent;color:var(--text-primary);font-weight:700;outline:none;">
            <option value="all" {{ $selected_branch === 'all' ? 'selected' : '' }}>Semua Cabang</option>
            <option value="OBM_01" {{ $selected_branch === 'OBM_01' ? 'selected' : '' }}>Banjarmasin</option>
            <option value="OBM_02" {{ $selected_branch === 'OBM_02' ? 'selected' : '' }}>Barabai</option>
            <option value="OBM_03" {{ $selected_branch === 'OBM_03' ? 'selected' : '' }}>Batulicin</option>
        </select>
        <select name="context" onchange="this.form.submit()" style="padding:.35rem;border:none;background:transparent;color:var(--text-primary);font-weight:700;outline:none;">
            <option value="all" {{ $selected_context === 'all' ? 'selected' : '' }}>Semua Konteks</option>
            @foreach($contexts as $ctx)
                <option value="{{ $ctx }}" {{ $selected_context === $ctx ? 'selected' : '' }}>{{ $ctx }}</option>
            @endforeach
        </select>
    </form>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;margin-bottom:1rem;">
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:1rem;">
        <div class="hint" data-tip="Jumlah seluruh baris hasil forecast yang tercatat sesuai filter periode/cabang/konteks." style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;display:inline-block;">Total Runs</div>
        <div style="font-size:1.4rem;font-weight:800;">{{ number_format($summary['total_runs']) }}</div>
    </div>
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:1rem;">
        <div class="hint" data-tip="Jumlah run yang benar-benar diprediksi model ML (bukan fallback rule)." style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;display:inline-block;">ML Runs</div>
        <div style="font-size:1.4rem;font-weight:800;">{{ number_format($summary['ml_runs']) }}</div>
    </div>
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:1rem;">
        <div class="hint" data-tip="Rata-rata confidence model. Semakin tinggi umumnya semakin stabil prediksinya." style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;display:inline-block;">Avg Confidence</div>
        <div style="font-size:1.4rem;font-weight:800;">{{ number_format($summary['avg_confidence'], 2) }}%</div>
    </div>
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:1rem;">
        <div class="hint" data-tip="Rata-rata WAPE validasi model. Lebih rendah lebih baik." style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;display:inline-block;">Avg WAPE</div>
        <div style="font-size:1.4rem;font-weight:800;color:{{ $summary['avg_wape'] <= 15 ? '#10b981' : ($summary['avg_wape'] <= 30 ? '#f59e0b' : '#ef4444') }};">{{ number_format($summary['avg_wape'], 2) }}%</div>
    </div>
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:1rem;">
        <div class="hint" data-tip="Jumlah run yang sudah punya nilai aktual (realisasi) dan berhasil dievaluasi." style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;display:inline-block;">Evaluated</div>
        <div style="font-size:1.4rem;font-weight:800;">{{ number_format($summary['evaluated_runs']) }}</div>
    </div>
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:1rem;">
        <div class="hint" data-tip="Rata-rata persen selisih prediksi vs aktual dari run yang sudah dievaluasi." style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;display:inline-block;">Avg Forecast Error</div>
        <div style="font-size:1.4rem;font-weight:800;color:{{ $summary['avg_error_pct'] <= 15 ? '#10b981' : ($summary['avg_error_pct'] <= 30 ? '#f59e0b' : '#ef4444') }};">{{ number_format($summary['avg_error_pct'], 2) }}%</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1rem;margin-bottom:1.25rem;">
    @foreach($byContext as $ctx => $row)
        <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:1rem;">
            <div style="font-size:.75rem;color:var(--text-muted);text-transform:uppercase;">{{ $ctx }}</div>
            <div style="font-size:1.15rem;font-weight:700;margin-top:.35rem;">{{ number_format($row['count']) }} run</div>
            <div style="font-size:.8rem;color:var(--text-secondary);margin-top:.35rem;">Confidence {{ number_format($row['avg_confidence'], 2) }}%</div>
            <div style="font-size:.8rem;color:var(--text-secondary);">WAPE {{ number_format($row['avg_wape'], 2) }}%</div>
        </div>
    @endforeach
</div>

<div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:1rem;">
    <div style="font-size:.9rem;font-weight:700;margin-bottom:.75rem;">Run Logs Terbaru</div>
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="border-bottom:1px solid var(--border);text-align:left;">
                    <th style="padding:.6rem .4rem;font-size:.72rem;color:var(--text-muted);text-transform:uppercase;">Waktu</th>
                    <th style="padding:.6rem .4rem;font-size:.72rem;color:var(--text-muted);text-transform:uppercase;">Konteks</th>
                    <th style="padding:.6rem .4rem;font-size:.72rem;color:var(--text-muted);text-transform:uppercase;">Entitas</th>
                    <th style="padding:.6rem .4rem;font-size:.72rem;color:var(--text-muted);text-transform:uppercase;">Model</th>
                    <th class="hint" data-tip="Nilai forecast model untuk periode target." style="padding:.6rem .4rem;font-size:.72rem;color:var(--text-muted);text-transform:uppercase;text-align:right;">Prediksi</th>
                    <th class="hint" data-tip="Nilai realisasi aktual pada periode yang sama." style="padding:.6rem .4rem;font-size:.72rem;color:var(--text-muted);text-transform:uppercase;text-align:right;">Actual</th>
                    <th class="hint" data-tip="Persentase selisih antara prediksi dan aktual. Lebih kecil lebih baik." style="padding:.6rem .4rem;font-size:.72rem;color:var(--text-muted);text-transform:uppercase;text-align:right;">Error</th>
                    <th class="hint" data-tip="Rentang ketidakpastian prediksi (low-high)." style="padding:.6rem .4rem;font-size:.72rem;color:var(--text-muted);text-transform:uppercase;text-align:right;">Range</th>
                    <th class="hint" data-tip="Confidence internal model untuk run ini." style="padding:.6rem .4rem;font-size:.72rem;color:var(--text-muted);text-transform:uppercase;text-align:right;">Conf</th>
                    <th class="hint" data-tip="WAPE validasi model saat model dilatih. Lebih rendah lebih baik." style="padding:.6rem .4rem;font-size:.72rem;color:var(--text-muted);text-transform:uppercase;text-align:right;">WAPE</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $r)
                    <tr style="border-bottom:1px solid var(--border);">
                        <td style="padding:.55rem .4rem;font-size:.78rem;">{{ optional($r->forecasted_at)->format('d M H:i') }}</td>
                        <td style="padding:.55rem .4rem;font-size:.78rem;">{{ $r->context }}</td>
                        <td style="padding:.55rem .4rem;font-size:.78rem;">{{ $r->entity_name ?? '-' }}</td>
                        <td style="padding:.55rem .4rem;font-size:.78rem;text-transform:uppercase;">{{ $r->model ?? '-' }}</td>
                        <td style="padding:.55rem .4rem;font-size:.78rem;text-align:right;">{{ is_null($r->prediction) ? '-' : number_format($r->prediction, 2, ',', '.') }}</td>
                        <td style="padding:.55rem .4rem;font-size:.78rem;text-align:right;">{{ is_null($r->actual_value) ? '-' : number_format($r->actual_value, 2, ',', '.') }}</td>
                        <td style="padding:.55rem .4rem;font-size:.78rem;text-align:right;color:{{ is_null($r->error_pct) ? 'var(--text-muted)' : ($r->error_pct <= 15 ? '#10b981' : ($r->error_pct <= 30 ? '#f59e0b' : '#ef4444')) }};">
                            @if(is_null($r->error_pct))
                                -
                            @else
                                {{ number_format($r->error_pct, 2, ',', '.') }}%
                            @endif
                        </td>
                        <td style="padding:.55rem .4rem;font-size:.78rem;text-align:right;">
                            @if(!is_null($r->prediction_low) && !is_null($r->prediction_high))
                                {{ number_format($r->prediction_low, 2, ',', '.') }} - {{ number_format($r->prediction_high, 2, ',', '.') }}
                            @else
                                -
                            @endif
                        </td>
                        <td style="padding:.55rem .4rem;font-size:.78rem;text-align:right;">{{ is_null($r->confidence) ? '-' : number_format($r->confidence, 2, ',', '.') . '%' }}</td>
                        <td style="padding:.55rem .4rem;font-size:.78rem;text-align:right;">{{ is_null($r->wape) ? '-' : number_format($r->wape, 2, ',', '.') . '%' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" style="padding:1rem .4rem;color:var(--text-muted);text-align:center;">Belum ada run forecast tercatat.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
