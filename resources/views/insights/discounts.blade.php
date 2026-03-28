@extends('layouts.app')
@section('title', 'Efektivitas Diskon')
@section('content')
@php
    $isDetail = $mode === 'detail';
@endphp

<style>
    /* Tooltip System */
    .has-tooltip {
        position: relative;
        cursor: help;
    }
    .tooltip-content {
        visibility: hidden;
        width: 220px;
        background-color: #1e1e2d;
        color: #fff;
        text-align: left;
        border-radius: 8px;
        padding: 10px;
        position: absolute;
        z-index: 100;
        bottom: 125%;
        left: 50%;
        margin-left: -110px;
        opacity: 0;
        transition: opacity 0.3s;
        font-size: 0.75rem;
        line-height: 1.4;
        font-weight: 400;
        border: 1px solid var(--border);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.5);
        pointer-events: none;
    }
    .has-tooltip:hover .tooltip-content {
        visibility: visible;
        opacity: 1;
    }
    .tooltip-content::after {
        content: "";
        position: absolute;
        top: 100%;
        left: 50%;
        margin-left: -5px;
        border-width: 5px;
        border-style: solid;
        border-color: #1e1e2d transparent transparent transparent;
    }
    .info-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 16px;
        height: 16px;
        background: var(--border);
        color: var(--text-muted);
        border-radius: 50%;
        font-size: 10px;
        font-weight: 800;
        margin-left: 5px;
        font-style: normal;
    }
    .decision-badge {
        font-size: 0.65rem;
        padding: 2px 6px;
        border-radius: 4px;
        text-transform: uppercase;
        font-weight: 800;
        margin-bottom: 5px;
        display: inline-block;
    }
</style>

<style>
    /* Supervisor Action Center Styling */
    .supervisor-box {
        background: linear-gradient(145deg, #1e1e2d 0%, #161625 100%);
        border: 1px solid rgba(99, 102, 241, 0.3);
        border-radius: 16px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
        box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.3), 0 0 15px rgba(99, 102, 241, 0.1);
    }
    .supervisor-box::before {
        content: "";
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(99, 102, 241, 0.05) 0%, transparent 70%);
        pointer-events: none;
    }
    .action-badge {
        background: rgba(99, 102, 241, 0.15);
        color: var(--accent);
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 800;
        text-transform: uppercase;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        margin-bottom: 1rem;
        border: 1px solid rgba(99, 102, 241, 0.3);
    }
    .action-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .action-item {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        padding: 0.75rem 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        color: var(--text-primary);
        font-size: 0.9rem;
    }
    .action-item:last-child {
        border-bottom: none;
    }
    .action-bullet {
        margin-top: 4px;
        min-width: 8px;
        height: 8px;
        background: var(--accent);
        border-radius: 50%;
        box-shadow: 0 0 8px var(--accent);
    }
</style>

<div class="mb-4 d-flex justify-content-between align-items-center" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        @if($isDetail)
            <a href="{{ route('insights.discounts', ['branch' => $selected_branch, 'period_id' => $activePeriod->id, 'range' => $selected_range]) }}" class="btn-back" style="text-decoration: none; color: var(--accent); font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">← Kembali ke Overview</a>
            <h1 style="font-size: 1.5rem; font-weight: 700;">📦 Detail Item: {{ $selected_principle }}</h1>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Rincian efisiensi promo per produk untuk <strong>{{ $selected_range }} bulan terakhir</strong>.</p>
        @else
            <a href="{{ route('insights.index', ['branch' => $selected_branch, 'period_id' => $activePeriod->id]) }}" class="btn-back" style="text-decoration: none; color: var(--accent); font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">← Kembali</a>
            <h1 style="font-size: 1.5rem; font-weight: 700;">💸 Evaluasi Efektivitas Diskon</h1>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Analisis rasio biaya promo vs pendapatan bersih untuk <strong>{{ $selected_range }} bulan terakhir</strong>.</p>
        @endif
    </div>

    <div style="display: flex; gap: 1rem; align-items: center;">
        <form method="GET" action="{{ route('insights.discounts') }}" style="display: flex; gap: 0.75rem; align-items: center; background: var(--bg-card); padding: 0.5rem 1rem; border-radius: 12px; border: 1px solid var(--border);">
            @if($isDetail) <input type="hidden" name="principle_detail" value="{{ $selected_principle }}"> @endif
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

            <div style="width: 1px; height: 20px; background: var(--border);"></div>

            <label for="range" style="font-size: 0.85rem; color: var(--text-secondary); font-weight: 600;">Range:</label>
            <select name="range" id="range" onchange="this.form.submit()" style="padding: 0.4rem; border: none; background: transparent; color: var(--accent); font-weight: 800; outline: none; cursor: pointer;">
                <option value="1" {{ $selected_range == '1' ? 'selected' : '' }}>Per Bulan</option>
                <option value="3" {{ $selected_range == '3' ? 'selected' : '' }}>3 Bulan</option>
            </select>
        </form>
    </div>
</div>


<!-- Supervisor Action Center -->
<div class="supervisor-box">
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
        <div>
            <div class="action-badge">✨ Pusat Tindakan Supervisor</div>
            <h2 style="font-size: 1.1rem; color: #fff; font-weight: 700; margin-bottom: 1.25rem;">Tindakan yang Disarankan:</h2>
        </div>
        <div style="font-size: 0.7rem; color: var(--text-muted); background: rgba(0,0,0,0.2); padding: 4px 8px; border-radius: 4px;">
            Dianalisis: {{ date('H:i') }} WIB
        </div>
    </div>
    
    <div class="action-list">
        @foreach($supervisor_actions as $action)
            <div class="action-item">
                <div class="action-bullet"></div>
                <div style="line-height: 1.5;">{!! $action !!}</div>
            </div>
        @endforeach
    </div>
</div>
<div style="margin-bottom: 2rem; background: #1e1e2d; border: 1px solid var(--border); border-radius: 16px; padding: 2rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
    <h3 style="font-size: 1.25rem; margin-bottom: 1.5rem; color: #fff; display: flex; align-items: center; gap: 0.75rem;">
        🧠 Matriks Keputusan Strategis
    </h3>
    
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem;">
        <!-- Card 1: High Burner -->
        <div style="background: rgba(239, 68, 68, 0.05); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 12px; padding: 1.25rem;">
            <span class="decision-badge" style="background: var(--danger); color: #fff;">SITUASI: HIGH BURNER</span>
            <div style="font-weight: 700; color: #fff; margin-bottom: 0.75rem; font-size: 0.95rem;">Rasio Diskon > 12% & Omzet Stagnan</div>
            <p style="font-size: 0.8rem; color: var(--text-secondary); line-height: 1.6; margin-bottom: 1rem;">
                Promo terlalu agresif tetapi tidak memberikan daya tarik (leverage) pada volume penjualan.
            </p>
            <div style="border-top: 1px solid rgba(239, 68, 68, 0.1); pt: 0.75rem;">
                <strong style="font-size: 0.75rem; color: var(--danger); text-transform: uppercase;">Aksi Cerdas:</strong>
                <ul style="font-size: 0.75rem; color: var(--text-primary); padding-left: 1.25rem; margin-top: 0.5rem;">
                    <li>Hentikan diskon invoice tambahan.</li>
                    <li>Alihkan budget ke item dengan margin lebih tinggi.</li>
                    <li>Audit performa salesman di wilayah tersebut.</li>
                </ul>
            </div>
        </div>

        <!-- Card 2: Strategic Growth -->
        <div style="background: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 12px; padding: 1.25rem;">
            <span class="decision-badge" style="background: var(--success); color: #fff;">SITUASI: STAR PRODUCT</span>
            <div style="font-weight: 700; color: #fff; margin-bottom: 0.75rem; font-size: 0.95rem;">Rasio Diskon < 5% & Omzet Tinggi</div>
            <p style="font-size: 0.8rem; color: var(--text-secondary); line-height: 1.6; margin-bottom: 1rem;">
                Produk/Prinsipel ini sehat secara organik dan memiliki loyalitas pelanggan yang kuat.
            </p>
            <div style="border-top: 1px solid rgba(16, 185, 129, 0.1); pt: 0.75rem;">
                <strong style="font-size: 0.75rem; color: var(--success); text-transform: uppercase;">Aksi Cerdas:</strong>
                <ul style="font-size: 0.75rem; color: var(--text-primary); padding-left: 1.25rem; margin-top: 0.5rem;">
                    <li>Pertahankan stok (Safety Stock +20%).</li>
                    <li>Jangan berikan diskon tambahan (over-discounting).</li>
                    <li>Jadikan sebagai "bundling pair" untuk produk lambat.</li>
                </ul>
            </div>
        </div>

        <!-- Card 3: Efficient Promotional -->
        <div style="background: rgba(245, 158, 11, 0.05); border: 1px solid rgba(245, 158, 11, 0.2); border-radius: 12px; padding: 1.25rem;">
            <span class="decision-badge" style="background: var(--warning); color: #fff;">SITUASI: OPTIMASI BUDGET</span>
            <div style="font-weight: 700; color: #fff; margin-bottom: 0.75rem; font-size: 0.95rem;">Rasio Diskon 5-10% & Omzet Naik</div>
            <p style="font-size: 0.8rem; color: var(--text-secondary); line-height: 1.6; margin-bottom: 1rem;">
                Budgetting promo sudah sesuai, pertahankan namun jaga agar tidak "over-claim".
            </p>
            <div style="border-top: 1px solid rgba(245, 158, 11, 0.1); pt: 0.75rem;">
                <strong style="font-size: 0.75rem; color: var(--warning); text-transform: uppercase;">Aksi Cerdas:</strong>
                <ul style="font-size: 0.75rem; color: var(--text-primary); padding-left: 1.25rem; margin-top: 0.5rem;">
                    <li>Analisis regional SKU mana yang paling responsif.</li>
                    <li>Buat program loyalty per kuartal.</li>
                    <li>Monitoring stok untuk antisipasi lonjakan permintaan.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 2rem;">
    <div class="has-tooltip" style="background: var(--bg-card); padding: 1.25rem; border-radius: 16px; border: 1px solid var(--border); border-top: 4px solid var(--accent);">
        <div style="color: var(--text-muted); font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">{{ $isDetail ? 'Rasio Produk' : 'Avg Discount Ratio' }} <i class="info-icon">i</i></div>
        <div style="font-size: 1.5rem; font-weight: 800; color: {{ $summary->avg_ratio > 10 ? 'var(--danger)' : 'var(--success)' }};">{{ number_format($summary->avg_ratio, 2) }}%</div>
        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">{{ $isDetail ? 'Efisiensi brand ini' : 'Target sehat: < 7%' }}</div>
        <div class="tooltip-content">
            <strong>Cara Baca:</strong> Semakin tinggi angkanya, semakin besar omzet yang "terbakar" untuk promo. <br><br>
            <strong>Keputusan:</strong> Jika > 10%, evaluasi efektivitas promo. Jika < 5%, ada ruang untuk menambah promo penetrasi.
        </div>
    </div>
    <div class="has-tooltip" style="background: var(--bg-card); padding: 1.25rem; border-radius: 16px; border: 1px solid var(--border); border-top: 4px solid var(--warning);">
        <div style="color: var(--text-muted); font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">Budget Promo <i class="info-icon">i</i></div>
        <div style="font-size: 1.5rem; font-weight: 800; color: var(--text-primary);">Rp {{ number_format($summary->total_discount/1000000, 1) }}jt</div>
        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">Total potongan harga</div>
        <div class="tooltip-content">
            <strong>Cara Baca:</strong> Akumulasi nilai nominal dari seluruh promo produk (Disc Item). <br><br>
            <strong>Keputusan:</strong> Pastikan budget ini sebanding dengan pertumbuhan omzet di trend chart bawah.
        </div>
    </div>
    <div class="has-tooltip" style="background: var(--bg-card); padding: 1.25rem; border-radius: 16px; border: 1px solid var(--border); border-top: 4px solid var(--success);">
        <div style="color: var(--text-muted); font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">Pendapatan Bersih <i class="info-icon">i</i></div>
        <div style="font-size: 1.5rem; font-weight: 800; color: var(--success);">Rp {{ number_format($summary->total_net/1000000, 1) }}jt</div>
        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">Final revenue</div>
        <div class="tooltip-content">
            <strong>Cara Baca:</strong> Omzet kotor dikurangi biaya promo. Ini adalah uang riil yang masuk. <br><br>
            <strong>Keputusan:</strong> Bandingkan dengan COGS/HPP untuk menghitung profitabilitas murni.
        </div>
    </div>
    <div class="has-tooltip" style="background: var(--bg-card); padding: 1.25rem; border-radius: 16px; border: 1px solid var(--border); border-top: 4px solid #a855f7;">
        @if($isDetail)
            <div style="color: var(--text-muted); font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">Item Terlaris</div>
            @php $topItem = $data->first(); @endphp
            <div style="font-size: 1.1rem; font-weight: 800; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $topItem->name ?? '-' }}">
                {{ $topItem->name ?? '-' }}
            </div>
            <div style="font-size: 0.85rem; color: var(--accent); font-weight: 700;">Omzet: Rp {{ number_format(($topItem->net_sales ?? 0)/1000000, 1) }}jt</div>
        @else
            <div style="color: var(--text-muted); font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">Efisiensi Tertinggi</div>
            @php $best = $data->sortBy('discount_ratio')->first(); @endphp
            <div style="font-size: 1.1rem; font-weight: 800; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $best->principle ?? '-' }}">
                {{ $best->principle ?? '-' }}
            </div>
            <div style="font-size: 0.85rem; color: var(--success); font-weight: 700;">Rasio: {{ number_format($best->discount_ratio ?? 0, 2) }}%</div>
        @endif
        <div class="tooltip-content">
            <strong>Cara Baca:</strong> Mengambil performa terbaik (rasio terkecil) untuk dijadikan benchmark efisiensi.
        </div>
    </div>
</div>

<div style="margin-bottom: 2rem;">
    <div style="background: var(--bg-card); padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border);">
        <h3 class="has-tooltip" style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
            📊 {{ $isDetail ? 'Rasio Diskon per Produk (%)' : 'Rasio Diskon per Prinsipel (%)' }} <i class="info-icon">i</i>
            <div class="tooltip-content">
                <strong>Cara Baca:</strong> Batang yang berwarna Merah menunjukkan rasio di atas 10%, yang berarti penggunaan promo sangat agresif.
            </div>
        </h3>
        <div style="height: 350px;">
            <canvas id="ratioChart"></canvas>
        </div>
    </div>
</div>

<!-- Trend Line -->
<div style="background: var(--bg-card); padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border); margin-bottom: 2rem;">
    <h3 class="has-tooltip" style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
        📈 Analisis Tren & Burn Rate <i class="info-icon">i</i>
        <div class="tooltip-content">
            <strong>Cara Baca:</strong> Garis Ungu (%) harus stabil atau turun saat area Biru (Biaya) naik untuk menunjukkan pertumbuhan yang efisien.
        </div>
    </h3>
    <div style="height: 250px;">
        <canvas id="trendChart"></canvas>
    </div>
</div>

<div class="main-card" style="background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border); padding: 1.5rem; overflow-x: auto;">
    <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1.5rem;">📋 {{ $isDetail ? "Daftar Produk: $selected_principle" : "Rincian Data Prinsipel" }}</h3>
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="border-bottom: 2px solid var(--border); text-align: left;">
                <th style="padding: 1rem 0.5rem; color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase;">{{ $isDetail ? 'Nama Produk' : 'Prinsipel' }}</th>
                <th style="padding: 1rem 0.5rem; color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase; text-align: right;" class="has-tooltip">
                    {{ $isDetail ? 'Disc Item' : 'Disc. Item' }} <i class="info-icon">i</i>
                    <div class="tooltip-content" style="left: auto; right: 0; margin-left: 0; transform: translateX(0);">Potongan harga murni yang diberikan pada setiap produk.</div>
                </th>
                <th style="padding: 1rem 0.5rem; color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase; text-align: right;" class="has-tooltip">
                    Total Biaya <i class="info-icon">i</i>
                    <div class="tooltip-content" style="left: auto; right: 0; margin-left: 0; transform: translateX(0);">Nilai diskon murni dari promo item (Disc Item).</div>
                </th>
                <th style="padding: 1rem 0.5rem; color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase; text-align: right;" class="has-tooltip">
                    Net Revenue <i class="info-icon">i</i>
                    <div class="tooltip-content" style="left: auto; right: 0; margin-left: 0; transform: translateX(0);">Pendapatan bersih setelah dikurangi subsidi promo.</div>
                </th>
                <th style="padding: 1rem 0.5rem; color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase; text-align: right;" class="has-tooltip">
                    Rasio (%) <i class="info-icon">i</i>
                    <div class="tooltip-content" style="left: auto; right: 0; margin-left: 0; transform: translateX(0);">Persentase biaya promo terhadap omzet kotor. Target: < 10%.</div>
                </th>
                @if(!$isDetail) <th style="padding: 1rem 0.5rem; text-align: center;">Aksi</th> @endif
            </tr>
        </thead>
        <tbody>
            @foreach($data as $item)
                <tr style="border-bottom: 1px solid var(--border); transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                    <td style="padding: 1rem 0.5rem;">
                        <strong style="color: var(--text-primary); font-size: 0.85rem;">{{ $isDetail ? $item->name : $item->principle }}</strong>
                    </td>
                    <td style="padding: 1rem 0.5rem; text-align: right; color: var(--text-secondary); font-size: 0.85rem;">{{ number_format($item->disc_item, 0, ',', '.') }}</td>
                    <td style="padding: 1rem 0.5rem; text-align: right; color: var(--warning); font-weight: 600;">Rp {{ number_format($item->total_discount, 0, ',', '.') }}</td>
                    <td style="padding: 1rem 0.5rem; text-align: right; color: var(--success); font-weight: 600;">Rp {{ number_format($item->net_sales, 0, ',', '.') }}</td>
                    <td style="padding: 1rem 0.5rem; text-align: right;">
                         <span style="padding: 0.25rem 0.6rem; border-radius: 6px; font-weight: 800; font-size: 0.75rem; background: {{ $item->discount_ratio > 10 ? 'rgba(239, 68, 68, 0.1)' : 'rgba(16, 185, 129, 0.1)' }}; color: {{ $item->discount_ratio > 10 ? '#ef4444' : '#10b981' }}; border: 1px solid {{ $item->discount_ratio > 10 ? 'rgba(239, 68, 68, 0.2)' : 'rgba(16, 185, 129, 0.2)' }};">
                            {{ number_format($item->discount_ratio, 2) }}%
                         </span>
                    </td>
                    @if(!$isDetail)
                    <td style="padding: 1rem 0.5rem; text-align: center;">
                        <a href="{{ route('insights.discounts', ['branch' => $selected_branch, 'period_id' => $activePeriod->id, 'principle_detail' => $item->principle]) }}" style="color: var(--accent); text-decoration: none; font-size: 0.75rem; font-weight: 700; background: rgba(99, 102, 241, 0.1); padding: 0.4rem 0.8rem; border-radius: 6px; border: 1px solid rgba(99, 102, 241, 0.2);">Lihat Item Detail</a>
                    </td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>
</div>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctxRatio = document.getElementById('ratioChart').getContext('2d');
    const ratioData = {!! json_encode($data->take(15)->values()) !!};
    
    new Chart(ctxRatio, {
        type: 'bar',
        data: {
            labels: ratioData.map(d => {{ $isDetail ? 'd.name' : 'd.principle' }}),
            datasets: [{
                label: 'Rasio Diskon (%)',
                data: ratioData.map(d => d.discount_ratio.toFixed(2)),
                backgroundColor: ratioData.map(d => d.discount_ratio > 10 ? 'rgba(239, 68, 68, 0.7)' : 'rgba(99, 102, 241, 0.7)'),
                borderRadius: 5,
                borderWidth: 0
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { 
                    grid: { color: 'rgba(255,255,255,0.05)' },
                    ticks: { color: '#8888a0', callback: v => v + '%' } 
                },
                y: { 
                    grid: { display: false },
                    ticks: { color: '#8888a0', font: { size: 9 } } 
                }
            }
        }
    });

    const ctxTrend = document.getElementById('trendChart').getContext('2d');
    const trendData = {!! json_encode($trend) !!};
    new Chart(ctxTrend, {
        type: 'line',
        data: {
            labels: trendData.map(t => t.month),
            datasets: [
                {
                    label: 'Rasio Diskon (%)',
                    data: trendData.map(t => t.ratio.toFixed(2)),
                    borderColor: '#a855f7',
                    borderWidth: 3,
                    fill: false,
                    tension: 0.4,
                    yAxisID: 'y1'
                },
                {
                    label: 'Biaya Diskon',
                    data: trendData.map(t => t.discount),
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    borderColor: '#6366f1',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    yAxisID: 'y'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    grid: { color: 'rgba(255,255,255,0.05)' },
                    ticks: { color: '#8888a0', callback: v => 'Rp ' + (v/1000000).toFixed(1) + 'jt' }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    grid: { drawOnChartArea: false },
                    ticks: { color: '#a855f7', callback: v => v + '%' }
                },
                x: { grid: { display: false }, ticks: { color: '#8888a0' } }
            },
            plugins: {
                legend: { position: 'top', labels: { color: '#8888a0' } }
            }
        }
    });
});
</script>
@endsection
