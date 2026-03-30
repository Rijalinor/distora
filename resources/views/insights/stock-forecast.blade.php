@extends('layouts.app')

@section('title', 'Forecasting Stok')

@section('content')
<div class="mb-4">
    <a href="{{ route('insights.index', ['branch' => $selected_branch, 'period_id' => $activePeriod->id]) }}" class="btn-back" style="text-decoration: none; color: var(--accent); font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
        ← Kembali ke Pusat Kendali
    </a>
    <div class="d-flex justify-content-between align-items-center" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 700;">🚨 Monitoring Stok Kritis</h1>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Analisis <strong>3 bulan terakhir</strong> (hingga {{ $activePeriod->name }}) untuk memantau barang yang segera habis.</p>
        </div>
        
        <form method="GET" action="{{ route('insights.stock-forecast') }}" style="display: flex; gap: 1rem; background: var(--bg-card); padding: 0.5rem 1rem; border-radius: 12px; border: 1px solid var(--border); flex-wrap: wrap;">
            <!-- Filter Periode -->
            <div style="display: flex; flex-direction: column;">
                <label style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Periode</label>
                <select name="period_id" onchange="this.form.submit()" style="padding: 0.2rem; border: none; background: transparent; color: var(--accent-hover); font-weight: 800; outline: none; cursor: pointer;">
                    @foreach($allPeriods as $p)
                        <option value="{{ $p->id }}" {{ $p->id === $activePeriod->id ? 'selected' : '' }}>
                            {{ $p->name }} {{ $p->status === 'closed' ? '(Closed)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div style="width: 1px; background: var(--border); margin: 0.3rem 0;"></div>

            <!-- Filter Cabang -->
            <div style="display: flex; flex-direction: column;">
                <label style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Wilayah</label>
                <select name="branch" onchange="this.form.submit()" style="padding: 0.2rem; border: none; background: transparent; color: var(--text-primary); font-weight: 600; outline: none; cursor: pointer;">
                    <option value="all" {{ $selected_branch === 'all' ? 'selected' : '' }}>Semua Cabang</option>
                    <option value="OBM_01" {{ $selected_branch === 'OBM_01' ? 'selected' : '' }}>Banjarmasin</option>
                    <option value="OBM_02" {{ $selected_branch === 'OBM_02' ? 'selected' : '' }}>Barabai</option>
                    <option value="OBM_03" {{ $selected_branch === 'OBM_03' ? 'selected' : '' }}>Batulicin</option>
                </select>
            </div>
            
            <div style="width: 1px; background: var(--border); margin: 0.3rem 0;"></div>

            <!-- Filter Principal -->
            <div style="display: flex; flex-direction: column;">
                <label style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Prinsipel</label>
                <select name="principle" onchange="this.form.submit()" style="padding: 0.2rem; border: none; background: transparent; color: var(--text-primary); font-weight: 600; outline: none; cursor: pointer; max-width: 150px;">
                    <option value="all" {{ $selected_principle === 'all' ? 'selected' : '' }}>Semua Prinsipel</option>
                    @foreach($principles as $p)
                        <option value="{{ $p }}" {{ $selected_principle === $p ? 'selected' : '' }}>{{ $p }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
</div>

<!-- Cara Membaca Card -->
<div class="main-card mb-4" style="background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border); padding: 1.5rem; border-left: 4px solid var(--danger);">
    <h3 style="font-size: 1rem; margin-bottom: 0.5rem; color: var(--text-primary); display: flex; align-items: center; gap: 0.5rem;">📖 Cara Membaca & Simulasi Lonjakan</h3>
    <p style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.5;">
        Gunakan kolom <strong>"Simulasi Lonjakan (%)"</strong> jika Anda memprediksi permintaan barang akan naik (misal: karena mau Puasa atau Akhir Promo). 
        Sistem akan menghitung ulang sisa hari secara otomatis di layar Anda. 
    </p>
</div>

<div class="main-card" style="background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border); padding: 1.5rem;">
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;" id="forecastTable">
            <thead>
                <tr style="border-bottom: 1px solid var(--border); text-align: left;">
                    <th style="padding: 1rem 0.5rem; color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase;">Prinsipel / Produk</th>
                    <th style="padding: 1rem 0.5rem; color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase; text-align: right;">Stok Fisik</th>
                    <th style="padding: 1rem 0.5rem; color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase; text-align: right;">ADS (Normal)</th>
                    <th style="padding: 1rem 0.5rem; color: #6366f1; font-size: 0.75rem; text-transform: uppercase; text-align: right;">AI Demand Bulan Depan</th>
                    <th style="padding: 1rem 0.5rem; color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase; text-align: right;">AI Range</th>
                    <th style="padding: 1rem 0.5rem; color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase; text-align: center; width: 140px;">Simulasi Lonjakan (%)</th>
                    <th style="padding: 1rem 0.5rem; color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase; text-align: right;">Ketahanan</th>
                    <th style="padding: 1rem 0.5rem; color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase; text-align: center;">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $item)
                    <tr style="border-bottom: 1px solid var(--border);" class="forecast-row" 
                        data-stock="{{ $item->current_stock }}" 
                        data-ads="{{ $item->avg_daily }}">
                        <td style="padding: 1rem 0.5rem;">
                            <div style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">{{ $item->principle_name }}</div>
                            <strong style="color: var(--text-primary); font-size: 0.85rem;">{{ $item->product_name }}</strong>
                        </td>
                        <td style="padding: 1rem 0.5rem; text-align: right; color: var(--text-primary); font-weight: 500;">
                            {{ number_format($item->current_stock, 0, ',', '.') }}
                        </td>
                        <td style="padding: 1rem 0.5rem; text-align: right; color: var(--text-secondary); font-size: 0.85rem;">
                            {{ number_format($item->avg_daily, 1, ',', '.') }}/hari
                        </td>
                        <td style="padding: 1rem 0.5rem; text-align: right; background: rgba(99, 102, 241, 0.03);">
                            <div style="font-weight: 800; color: #6366f1; font-size: 0.9rem;">
                                {{ number_format($item->ai_prediction, 1, ',', '.') }}
                            </div>
                            <div style="font-size: 0.68rem; color: var(--text-muted);">
                                {{ strtoupper($item->ai_model ?? 'fallback') }} | {{ number_format($item->ai_confidence ?? 0, 1, ',', '.') }}%
                            </div>
                        </td>
                        <td style="padding: 1rem 0.5rem; text-align: right; color: var(--text-secondary); font-size: 0.78rem;">
                            @if(!is_null($item->ai_low) && !is_null($item->ai_high))
                                {{ number_format($item->ai_low, 1, ',', '.') }} - {{ number_format($item->ai_high, 1, ',', '.') }}
                                @if(!is_null($item->ai_wape))
                                    <div style="font-size: 0.65rem; color: var(--text-muted);">WAPE {{ number_format($item->ai_wape, 1, ',', '.') }}%</div>
                                @endif
                            @else
                                -
                            @endif
                        </td>
                        <td style="padding: 1rem 0.5rem; text-align: center;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; background: var(--bg-primary); border: 1px solid var(--border); border-radius: 8px; padding: 0.25rem 0.5rem;">
                                <span style="color: var(--text-muted); font-size: 0.75rem;">+</span>
                                <input type="number" class="surge-input" value="0" min="0" max="1000" step="5"
                                       style="width: 50px; background: transparent; border: none; color: var(--accent); font-weight: 700; outline: none; text-align: center;">
                                <span style="color: var(--text-muted); font-size: 0.75rem;">%</span>
                            </div>
                        </td>
                        <td style="padding: 1rem 0.5rem; text-align: right;">
                             <strong class="days-text" style="color: var(--text-primary);">{{ $item->days_to_oos }} Hari</strong>
                        </td>
                        <td style="padding: 1rem 0.5rem; text-align: center;">
                             <span class="badge badge-{{ $item->urgency }} status-badge" style="padding: 0.3rem 0.6rem; border-radius: 6px; font-size: 0.7rem; font-weight: 700; min-width: 80px; display: inline-block;">
                                 {{ $item->urgency === 'danger' ? 'KRITIS' : 'WASPADA' }}
                             </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.surge-input');
    
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            const row = this.closest('.forecast-row');
            const stock = parseFloat(row.dataset.stock);
            const ads = parseFloat(row.dataset.ads);
            const surgePercent = parseFloat(this.value) || 0;
            
            // Calculate new velocity: ADS * (1 + surge/100)
            const newVelocity = ads * (1 + (surgePercent / 100));
            
            // Calculate new days
            let newDays = stock / newVelocity;
            if (newDays > 999) newDays = 999;
            if (newDays < 0) newDays = 0;
            
            const roundedDays = Math.round(newDays);
            
            // Update UI
            const daysText = row.querySelector('.days-text');
            const badge = row.querySelector('.status-badge');
            
            daysText.innerText = roundedDays + ' Hari';
            
            // Update badge color based on new threshold
            badge.classList.remove('badge-danger', 'badge-warning', 'badge-success');
            if (roundedDays <= 3) {
                badge.classList.add('badge-danger');
                badge.innerText = 'KRITIS';
                daysText.style.color = 'var(--danger)';
            } else if (roundedDays <= 14) {
                badge.classList.add('badge-warning');
                badge.innerText = 'WASPADA';
                daysText.style.color = 'var(--warning)';
            } else {
                badge.classList.add('badge-success');
                badge.innerText = 'AMAN';
                daysText.style.color = 'var(--success)';
            }
        });
    });
});
</script>
@endsection
