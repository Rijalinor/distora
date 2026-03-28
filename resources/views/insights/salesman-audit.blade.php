@extends('layouts.app')

@section('title', 'Audit Salesman')

@section('content')
<div class="mb-4">
    <div class="breadcrumb">
        <a href="{{ route('insights.ai-advisor') }}">🧠 AI Advisor</a>
        <span class="sep">/</span>
        <span class="current">🕵️ Audit Salesman</span>
    </div>

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 700;">🕵️ Audit Pola Retur Salesman</h1>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Menganalisis anomali retur untuk deteksi dini masalah kualitas atau kecurangan.</p>
        </div>

        <form method="GET" action="{{ route('insights.salesman-audit') }}" style="display: flex; gap: 0.75rem; align-items: center; background: var(--bg-card); padding: 0.5rem 1rem; border-radius: 12px; border: 1px solid var(--border);">
             <select name="branch" id="branch" onchange="this.form.submit()" style="padding: 0.4rem; border: none; background: transparent; color: var(--text-primary); font-weight: 700; outline: none; cursor: pointer;">
                <option value="all" {{ $selected_branch === 'all' ? 'selected' : '' }}>Semua Cabang</option>
                <option value="OBM_01" {{ $selected_branch === 'OBM_01' ? 'selected' : '' }}>Banjarmasin (OBM_01)</option>
                <option value="OBM_02" {{ $selected_branch === 'OBM_02' ? 'selected' : '' }}>Barabai (OBM_02)</option>
                <option value="OBM_03" {{ $selected_branch === 'OBM_03' ? 'selected' : '' }}>Batulicin (OBM_03)</option>
            </select>
            <input type="hidden" name="period_id" value="{{ $activePeriod->id }}">
        </form>
    </div>
</div>

<div class="main-card" style="background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border); padding: 1.5rem; overflow-x: auto;">
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="border-bottom: 1px solid var(--border); text-align: left;">
                <th style="padding: 1rem; color: var(--text-secondary); font-size: 0.8rem; text-transform: uppercase;">Salesman</th>
                <th style="padding: 1rem; color: var(--text-secondary); font-size: 0.8rem; text-transform: uppercase; text-align: right;">Total Jualan (3 Bln)</th>
                <th style="padding: 1rem; color: var(--text-secondary); font-size: 0.8rem; text-transform: uppercase; text-align: right;">Total Retur</th>
                <th style="padding: 1rem; color: var(--text-secondary); font-size: 0.8rem; text-transform: uppercase; text-align: right;">Rasio Retur</th>
                <th style="padding: 1rem; color: var(--text-secondary); font-size: 0.8rem; text-transform: uppercase; text-align: center;">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $r)
                <tr style="border-bottom: 1px solid var(--border); {{ request('salesman') == $r->salesman ? 'background: rgba(239, 68, 68, 0.05);' : '' }}" id="salesman-{{ Str::slug($r->salesman) }}">
                    <td style="padding: 1rem;">
                        <span style="color: var(--text-primary); font-weight: 700; font-size: 0.95rem;">{{ $r->salesman }}</span>
                    </td>
                    <td style="padding: 1rem; text-align: right;">Rp {{ number_format($r->gross_value, 0, ',', '.') }}</td>
                    <td style="padding: 1rem; text-align: right; color: var(--danger);">Rp {{ number_format($r->return_value, 0, ',', '.') }}</td>
                    <td style="padding: 1rem; text-align: right; font-weight: 800; color: {{ $r->return_rate > 5 ? 'var(--danger)' : 'var(--warning)' }}">
                        {{ number_format($r->return_rate, 1) }}%
                    </td>
                    <td style="padding: 1rem; text-align: center;">
                        @if($r->return_rate > 5)
                            <span class="badge badge-danger">🚩 High Risk</span>
                        @else
                            <span class="badge badge-warning">⚠️ Waspada</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="mt-4" style="background: rgba(239, 68, 68, 0.05); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 12px; padding: 1.25rem;">
    <h4 style="color: var(--danger); font-size: 0.9rem; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">💡 Rekomendasi Audit</h4>
    <p style="color: var(--text-secondary); font-size: 0.85rem; margin: 0; line-height: 1.5;">
        Rasio retur di atas 5% dianggap tidak wajar. Disarankan untuk memverifikasi nota fisik retur dan melakukan kunjungan langsung (*Random Check*) ke outlet terkait untuk memastikan barang benar-benar rusak atau hanya manipulasi nota.
    </p>
</div>

@endsection
