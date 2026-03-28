@extends('layouts.app')

@section('title', 'AI Decision Advisor')

@section('content')
<div class="mb-4">
    <div class="breadcrumb">
        <a href="{{ route('insights.index') }}">Pusat Kendali</a>
        <span class="sep">/</span>
        <span class="current">🧠 AI Advisor</span>
    </div>

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 style="font-size: 2rem; font-weight: 800; background: linear-gradient(135deg, var(--accent), #a855f7); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin: 0;">
                🧠 AI Decision Advisor
            </h1>
            <p style="color: var(--text-muted); margin-top: 0.5rem; font-size: 1rem;">
                Asisten pintar Anda yang merangkum risiko dan peluang bisnis hari ini.
            </p>
        </div>

        <form method="GET" action="{{ route('insights.ai-advisor') }}" style="display: flex; gap: 0.75rem; align-items: center; background: var(--bg-card); padding: 0.5rem 1rem; border-radius: 12px; border: 1px solid var(--border);">
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

<style>
    .advisor-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
        gap: 1.5rem;
        margin-top: 2rem;
    }
    .advisor-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 20px;
        padding: 2rem;
        position: relative;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    .advisor-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px -15px rgba(0,0,0,0.4);
    }
    .card-danger { border-left: 6px solid var(--danger); }
    .card-warning { border-left: 6px solid var(--warning); }
    .card-info { border-left: 6px solid var(--accent); }

    .card-icon-pkg {
        width: 56px;
        height: 56px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        margin-bottom: 0.5rem;
    }
    .bg-danger-soft { background: rgba(239, 68, 68, 0.1); color: var(--danger); }
    .bg-warning-soft { background: rgba(245, 158, 11, 0.1); color: var(--warning); }
    .bg-info-soft { background: rgba(99, 102, 241, 0.1); color: var(--accent); }

    .card-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--text-primary);
    }
    .card-desc {
        font-size: 0.95rem;
        color: var(--text-secondary);
        line-height: 1.6;
    }
    .card-action {
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid var(--border);
        display: flex;
        justify-content: flex-end;
    }
    
    .empty-state {
        text-align: center;
        padding: 5rem 2rem;
        background: var(--bg-card);
        border-radius: 24px;
        border: 2px dashed var(--border);
        margin-top: 2rem;
    }
</style>

@if(count($cards) > 0)
    <div class="advisor-grid">
        @foreach($cards as $card)
            <div class="advisor-card card-{{ $card['type'] }}">
                <div class="card-icon-pkg bg-{{ $card['type'] }}-soft">
                    {{ $card['icon'] }}
                </div>
                <div>
                    <div class="card-title">{{ $card['title'] }}</div>
                    <div class="card-desc">
                        {!! $card['desc'] !!}
                    </div>
                </div>
                <div class="card-action">
                    <a href="{{ $card['link'] }}" class="btn btn-primary" style="background: {{ $card['type'] == 'danger' ? 'var(--danger)' : ($card['type'] == 'warning' ? 'var(--warning)' : 'var(--accent)') }}; border: none;">
                        {{ $card['action'] }} &rarr;
                    </a>
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="empty-state">
        <div style="font-size: 4rem; margin-bottom: 1.5rem;">✅</div>
        <h2 style="font-weight: 700; color: var(--text-primary);">Semua Lancar!</h2>
        <p style="color: var(--text-muted); max-width: 400px; margin: 0 auto; margin-top: 0.5rem;">
            AI tidak menemukan anomali atau risiko kritis saat ini. Terus pantau data transaksi Anda.
        </p>
    </div>
@endif

@endsection
