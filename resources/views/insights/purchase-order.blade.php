@extends('layouts.app')

@section('title', 'Rekomendasi Order Pabrik')

@section('content')
<div class="mb-4">
    <div class="mb-4 d-flex justify-content-between align-items-center" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <a href="{{ route('insights.index', ['branch' => $selected_branch, 'period_id' => $activePeriod->id]) }}" class="btn-back" style="text-decoration: none; color: var(--accent); font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">← Kembali</a>
            <h1 style="font-size: 1.5rem; font-weight: 700;">🛒 Rekomendasi Order Pabrik</h1>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Estimasi kebutuhan stok periode <strong>{{ $activePeriod->name }}</strong>.</p>
        </div>

        <form method="GET" action="{{ route('insights.purchase-order') }}" style="display: flex; gap: 1rem; align-items: center; background: var(--bg-card); padding: 0.5rem 1rem; border-radius: 12px; border: 1px solid var(--border); flex-wrap: wrap;">
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

            <div style="width: 1px; height: 30px; background: var(--border);"></div>

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

            <div style="width: 1px; height: 30px; background: var(--border);"></div>

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
            @if($isAiMode)
            <input type="hidden" name="mode" value="ai">
            @endif
        </form>
    </div>
</div>

<!-- Cara Membaca Card -->
<div class="main-card mb-4" style="background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border); padding: 1.5rem; border-left: 4px solid var(--accent);">
    <h3 style="font-size: 1rem; margin-bottom: 0.5rem; color: var(--text-primary); display: flex; align-items: center; gap: 0.5rem;">📖 Instruksi Order Barang</h3>
    <p style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.5;">
        Menu ini membantu Anda menghitung berapa banyak barang yang harus dipesan ke pabrik. 
        Sistem menggunakan **Rata-rata Penjualan 3 Bulan (Total ÷ 90 hari)** untuk hasil yang lebih stabil.
        Masukkan <strong>"Target Hari (Buffer)"</strong> untuk menentukan berapa hari stok tersebut harus bertahan (contoh: 30 hari). 
        Tambahkan <strong>"Lonjakan (%)"</strong> jika diprediksi ada kenaikan permintaan.
    </p>
</div>

<!-- Global Target Days Control -->
<div class="mb-3 d-flex align-items-center gap-3" style="display: flex; align-items: center; gap: 1rem; background: var(--bg-card); padding: 1rem; border-radius: 12px; border: 1px solid var(--border);">
    <div style="flex: 1;">
        <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-secondary); margin-bottom: 0.5rem;">⚙️ SET TARGET HARI (PROSES GLOBAL)</label>
        <div style="display: flex; align-items: center; gap: 1rem;">
             <input type="range" id="globalTargetDays" min="7" max="90" value="30" style="flex: 1; accent-color: var(--accent);">
             <span id="targetValueDisplay" style="font-weight: 800; color: var(--accent); font-size: 1.2rem; min-width: 80px;">30 Hari</span>
        </div>
    </div>
    @if($isAiMode)
    <div style="width: 1px; height: 50px; background: var(--border); margin: 0 1rem;"></div>
    <div style="flex: 0 0 auto; display: flex; align-items: center; gap: 0.75rem; background: rgba(99, 102, 241, 0.1); padding: 0.75rem 1rem; border-radius: 12px; border: 1px solid rgba(99, 102, 241, 0.2);">
        <label for="aiModeToggle" class="switch" style="position: relative; display: inline-block; width: 44px; height: 22px; margin: 0; padding: 0; cursor: pointer;">
            <input type="checkbox" id="aiModeToggle" style="position: absolute; opacity: 0; cursor: pointer; height: 0; width: 0;">
            <span class="slider round" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 22px;"></span>
        </label>
        <div style="display: flex; flex-direction: column;">
            <span style="font-size: 0.8rem; font-weight: 800; color: #6366f1;">🧠 SMART AI MODE</span>
            <span style="font-size: 0.65rem; color: var(--text-muted);">Gunakan prediksi ML dibanding rata-rata</span>
        </div>
    </div>
    @endif
</div>

<style>
.switch input:checked + .slider { background-color: #6366f1 !important; }
.switch input:checked + .slider:before { transform: translateX(22px); }
.slider:before {
  position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px;
  background-color: white; transition: .4s; border-radius: 50%;
}
</style>

<div class="main-card" style="background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border); padding: 1.5rem;">
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;" id="orderTable">
            <thead>
                <tr style="border-bottom: 1px solid var(--border); text-align: left;">
                    <th style="padding: 1rem 0.5rem; color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase;">Prinsipel / Produk</th>
                    <th style="padding: 1rem 0.5rem; color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase; text-align: right;">Stok</th>
                    
                    @if(count($data) > 0 && isset($data[0]->m1_name))
                        <th style="padding: 1rem 0.5rem; color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; text-align: right;">{{ $data[0]->m1_name }}</th>
                        <th style="padding: 1rem 0.5rem; color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; text-align: right;">{{ $data[0]->m2_name }}</th>
                        <th style="padding: 1rem 0.5rem; color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; text-align: right;">{{ $data[0]->m3_name }}</th>
                    @endif

                    <th style="padding: 1rem 0.5rem; color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase; text-align: right;" title="Total Penjualan 3 Bulan / 3">AMS</th>
                    @if($isAiMode)
                    <th style="padding: 1rem 0.5rem; color: #6366f1; font-size: 0.75rem; text-transform: uppercase; text-align: right; background: rgba(99, 102, 241, 0.05); border-radius: 8px 8px 0 0;" title="Prediksi Machine Learning">🧠 AI Forecast</th>
                    @endif
                    <th style="padding: 1rem 0.5rem; color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase; text-align: center; width: 100px;">Surge</th>
                    <th style="padding: 1rem 0.5rem; color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase; text-align: right;">Ctn Size</th>
                    <th style="padding: 1rem 0.5rem; color: var(--accent); font-size: 0.85rem; text-transform: uppercase; text-align: right; font-weight: 800;">Order Recommendation</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $item)
                    @php
                        // Robust Carton Size Extraction: (1X30) or (1X8X12)
                        preg_match('/\(([\dX]+)\)/', $item->product_name, $matches);
                        $ctnSize = 1;
                        if (isset($matches[1])) {
                            $parts = explode('X', $matches[1]);
                            if ($parts[0] == '1' && count($parts) > 1) {
                                array_shift($parts);
                            }
                            $ctnSize = array_product($parts) ?: 1;
                        }
                    @endphp
                    <tr style="border-bottom: 1px solid var(--border);" class="order-row" 
                        data-stock="{{ $item->current_stock }}" 
                        data-ctn-size="{{ $ctnSize }}"
                        data-ams="{{ $item->avg_monthly }}"
                        data-ads="{{ $item->avg_daily }}"
                        data-ai-forecast="{{ $item->ai_prediction }}"
                        data-qty-m1="{{ $item->qty_m1 }}"
                        data-qty-m2="{{ $item->qty_m2 }}"
                        data-qty-m3="{{ $item->qty_m3 }}"
                        data-m1-name="{{ $item->m1_name }}"
                        data-m2-name="{{ $item->m2_name }}"
                        data-m3-name="{{ $item->m3_name }}"
                    >

                        <td style="padding: 1rem 0.5rem;">
                            <div style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">{{ $item->principle_name }}</div>
                            <strong style="color: var(--text-primary); font-size: 0.85rem;">{{ $item->product_name }}</strong>
                        </td>
                        <td style="padding: 1rem 0.5rem; text-align: right; color: var(--text-primary); font-weight: 500;">
                            {{ number_format($item->current_stock, 0, ',', '.') }}
                        </td>
                        
                        <!-- Monthly Sales Breakdown with Cartoon Conversions -->
                        <td style="padding: 1rem 0.5rem; text-align: right; color: var(--text-secondary); font-size: 0.75rem;">
                            <div style="font-weight: 600;">{{ number_format($item->qty_m1, 0, ',', '.') }}</div>
                            <div style="font-size: 0.65rem; color: var(--text-muted);">{{ round($item->qty_m1 / $ctnSize, 1) }} Ctn</div>
                        </td>
                        <td style="padding: 1rem 0.5rem; text-align: right; color: var(--text-secondary); font-size: 0.75rem;">
                            <div style="font-weight: 600;">{{ number_format($item->qty_m2, 0, ',', '.') }}</div>
                            <div style="font-size: 0.65rem; color: var(--text-muted);">{{ round($item->qty_m2 / $ctnSize, 1) }} Ctn</div>
                        </td>
                        <td style="padding: 1rem 0.5rem; text-align: right; color: var(--text-secondary); font-size: 0.75rem;">
                            <div style="font-weight: 600;">{{ number_format($item->qty_m3, 0, ',', '.') }}</div>
                            <div style="font-size: 0.65rem; color: var(--text-muted);">{{ round($item->qty_m3 / $ctnSize, 1) }} Ctn</div>
                        </td>
                        <td style="padding: 1rem 0.5rem; text-align: right; color: var(--accent-hover); font-size: 0.85rem; font-weight: 700;">
                            {{ number_format($item->avg_monthly, 0, ',', '.') }}
                        </td>

                        @if($isAiMode)
                        <!-- AI SMART FORECAST -->
                        <td style="padding: 1rem 0.5rem; text-align: right; background: rgba(99, 102, 241, 0.05);">
                            <div style="font-weight: 800; color: #6366f1; font-size: 0.85rem;" class="ai-forecast-val">
                                {{ number_format($item->ai_prediction, 0, ',', '.') }}
                            </div>
                            <div style="font-size: 0.65rem; color: {{ $item->ai_trend == 'growing' ? '#10b981' : ($item->ai_trend == 'declining' ? '#ef4444' : 'var(--text-muted)') }}; font-weight: 600;">
                                {{ $item->ai_trend == 'growing' ? '↑ Trend Naik' : ($item->ai_trend == 'declining' ? '↓ Trend Turun' : '→ Stabil') }}
                            </div>
                        </td>
                        @endif
                        
                        <td style="padding: 1rem 0.5rem; text-align: center;">
                            <input type="number" class="surge-input" value="0" min="0" step="5"
                                   style="width: 50px; background: var(--bg-primary); border: 1px solid var(--border); color: var(--text-primary); border-radius: 6px; padding: 0.1rem; text-align: center; outline: none; font-size: 0.8rem;">
                        </td>
                        <td style="padding: 1rem 0.5rem; text-align: right; color: var(--text-muted); font-size: 0.8rem;">
                             x{{ $ctnSize }}
                        </td>
                        <td style="padding: 1rem 0.5rem; text-align: right;" class="order-result-cell" title="Menghitung...">
                             <div class="order-ctn-text" style="color: var(--text-primary); font-size: 0.8rem; font-weight: 600;">0 Ctn</div>
                             <strong class="order-qty-text" style="color: var(--accent); font-size: 1.1rem;">0</strong>
                             <span style="font-size: 0.7rem; color: var(--text-muted);">Pcs</span>
                        </td>

                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4 d-flex justify-content-end gap-3" style="display: flex; justify-content: flex-end; gap: 1rem;">
    <button onclick="exportToExcel()" class="btn-secondary" style="background: #10b981; color: white; border: none; padding: 0.8rem 2rem; border-radius: 12px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
        📊 Export ke Excel
    </button>
    <button onclick="window.print()" class="btn-primary" style="background: var(--accent); color: white; border: none; padding: 0.8rem 2rem; border-radius: 12px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
        🖨️ Cetak Daftar Pesanan
    </button>
</div>

<!-- Load SheetJS for Excel Export -->
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const globalTargetInput = document.getElementById('globalTargetDays');
    const targetDisplay = document.getElementById('targetValueDisplay');
    const aiModeToggle = document.getElementById('aiModeToggle');
    
    function calculateOrders() {
        const isAiMode = aiModeToggle ? aiModeToggle.checked : false;
        const targetDays = parseInt(globalTargetInput.value);
        targetDisplay.innerText = targetDays + ' Hari';
        
        document.querySelectorAll('.order-row').forEach(row => {
            const stock = parseFloat(row.dataset.stock) || 0;
            const ads = parseFloat(row.dataset.ads) || 0;
            const ams = parseFloat(row.dataset.ams) || 0;
            const aiForecast = parseFloat(row.dataset.aiForecast) || ams;
            const ctnSize = parseInt(row.dataset.ctnSize) || 1;
            const surge = parseFloat(row.querySelector('.surge-input').value) || 0;
            
            // BASE CALCULATION MODE
            const baseMonthly = isAiMode ? aiForecast : ams;
            const baseDaily = isAiMode ? (aiForecast / 30) : ads;

            // Re-calc logic: (BaseDaily * (1 + surge/100) * targetDays) - currentStock
            const adjustedVelocity = baseDaily * (1 + (surge / 100));
            const totalNeed = adjustedVelocity * targetDays;
            const recommend = Math.max(0, Math.ceil(totalNeed - stock));
            
            const m1 = parseFloat(row.dataset.qtyM1);
            const m2 = parseFloat(row.dataset.qtyM2);
            const m3 = parseFloat(row.dataset.qtyM3);
            const n1 = row.dataset.m1Name;
            const n2 = row.dataset.m2Name;
            const n3 = row.dataset.m3Name;

            const orderText = row.querySelector('.order-qty-text');
            orderText.innerText = recommend.toLocaleString('id-ID');
            
            const ctnText = row.querySelector('.order-ctn-text');
            const ctnValue = (recommend / ctnSize).toFixed(1);
            ctnText.innerText = ctnValue + ' Ctn';

            // Detailed Tooltip with Monthly Breakdown
            const resultCell = row.querySelector('.order-result-cell');
            const formulaDesc = `HISTORI PENJUALAN:\n` +
                                `- ${n1}: ${m1.toLocaleString('id-ID')}\n` +
                                `- ${n2}: ${m2.toLocaleString('id-ID')}\n` +
                                `- ${n3}: ${m3.toLocaleString('id-ID')}\n` +
                                `-----------------------------------\n` +
                                `${isAiMode ? '🤖 AI PREDICTION' : 'RATA-RATA (AMS)'}: ${baseMonthly.toLocaleString('id-ID', {maximumFractionDigits:0})} /bln\n\n` +
                                `PERHITUNGAN ORDER:\n` +
                                `- Target (${targetDays} hari): ${(baseDaily * targetDays).toLocaleString('id-ID', {maximumFractionDigits:0})} pcs\n` +
                                `- Lonjakan (${surge}%): +${((baseDaily * surge/100) * targetDays).toLocaleString('id-ID', {maximumFractionDigits:0})} pcs\n` +
                                `- Stok Saat Ini: -${stock.toLocaleString('id-ID')} pcs\n` +
                                `=========================\n` +
                                `REKOMENDASI: ${recommend.toLocaleString('id-ID')} pcs (${ctnValue} ctn)`;
            resultCell.title = formulaDesc;
            
            if (recommend > 0) {
                orderText.parentElement.parentElement.style.background = isAiMode ? 'rgba(99, 102, 241, 0.05)' : 'rgba(0, 153, 255, 0.05)';
            } else {
                orderText.parentElement.parentElement.style.background = 'transparent';
            }
        });
    }
    
    globalTargetInput.addEventListener('input', calculateOrders);
    aiModeToggle.addEventListener('change', calculateOrders);
    const surgeInputs = document.querySelectorAll('.surge-input');
    surgeInputs.forEach(input => input.addEventListener('input', calculateOrders));
    
    window.exportToExcel = function() {
        const targetDays = globalTargetInput.value;
        const branch = "{{ $selected_branch }}";
        const principle = "{{ $selected_principle }}";
        const fileName = `Rekomendasi_Order_${principle}_${branch}_${targetDays}Hari.xlsx`;
        
        const firstRow = document.querySelector('.order-row');
        const n1 = firstRow?.dataset.m1Name || 'M-2';
        const n2 = firstRow?.dataset.m2Name || 'M-1';
        const n3 = firstRow?.dataset.m3Name || 'M-0';

        // Prepare data for Excel
        const data = [
            ["LAPORAN REKOMENDASI ORDER PABRIK (SANGAT DETAIL)"],
            ["Wilayah", branch],
            ["Prinsipel", principle],
            ["Target Stok", targetDays + " Hari"],
            ["Tanggal Export", new Date().toLocaleString()],
            [],
            ["PRINSIPEL", "NAMA PRODUK", "STOK", n1, n2, n3, "AMS (BLN)", "SURGE", "ISI/CTN", "ORDER (PCS)", "ORDER (CTN)"]
        ];
        
        document.querySelectorAll('.order-row').forEach(row => {
            const principleName = row.querySelector('div').innerText;
            const productName = row.querySelector('strong').innerText;
            const stock = row.dataset.stock;
            const ams = row.dataset.ams;
            const ctnSize = row.dataset.ctnSize;
            const surge = row.querySelector('.surge-input').value;
            const recommend = row.querySelector('.order-qty-text').innerText.replace(/\./g, '');
            const ctnVal = (parseInt(recommend) / parseInt(ctnSize)).toFixed(1);
            
            const m1 = row.dataset.qtyM1;
            const m2 = row.dataset.qtyM2;
            const m3 = row.dataset.qtyM3;

            if (parseInt(recommend) > 0 || parseFloat(ams) > 0) {
              data.push([
                  principleName,
                  productName,
                  parseFloat(stock),
                  parseFloat(m1),
                  parseFloat(m2),
                  parseFloat(m3),
                  parseFloat(ams),
                  parseFloat(surge) + "%",
                  parseInt(ctnSize),
                  parseInt(recommend),
                  parseFloat(ctnVal)
              ]);
            }
        });

        const ws = XLSX.utils.aoa_to_sheet(data);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Order List Detail");
        XLSX.writeFile(wb, fileName);
    };

    // Initial calc
    calculateOrders();
});
</script>

@endsection
