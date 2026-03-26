@extends('layouts.app')

@section('title', 'Rekomendasi Order Pabrik')

@section('content')
<div class="mb-4">
    <a href="{{ route('insights.index') }}" class="btn-back" style="text-decoration: none; color: var(--accent); font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
        ← Kembali ke Pusat Kendali
    </a>
    <div class="d-flex justify-content-between align-items-center" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 700;">🛒 Rekomendasi Order Pabrik</h1>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Hitung kuantitas pesanan berdasarkan target hari stok (Buffer Days).</p>
        </div>
        
        <form method="GET" action="{{ route('insights.purchase-order') }}" style="display: flex; gap: 1rem; background: var(--bg-card); padding: 0.5rem 1rem; border-radius: 12px; border: 1px solid var(--border);">
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
<div class="main-card mb-4" style="background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border); padding: 1.5rem; border-left: 4px solid var(--accent);">
    <h3 style="font-size: 1rem; margin-bottom: 0.5rem; color: var(--text-primary); display: flex; align-items: center; gap: 0.5rem;">📖 Instruksi Order Barang</h3>
    <p style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.5;">
        Menu ini membantu Anda menghitung berapa banyak barang yang harus dipesan ke pabrik. Masukkan <strong>"Target Hari (Buffer)"</strong> 
        untuk menentukan berapa hari stok tersebut harus bertahan (contoh: 30 hari). Tambahkan <strong>"Lonjakan (%)"</strong> jika diprediksi ada kenaikan permintaan.
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
</div>

<div class="main-card" style="background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border); padding: 1.5rem;">
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;" id="orderTable">
            <thead>
                <tr style="border-bottom: 1px solid var(--border); text-align: left;">
                    <th style="padding: 1rem 0.5rem; color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase;">Prinsipel / Produk</th>
                    <th style="padding: 1rem 0.5rem; color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase; text-align: right;">Stok Fisik</th>
                    <th style="padding: 1rem 0.5rem; color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase; text-align: right;">ADS (90 Hari)</th>
                    <th style="padding: 1rem 0.5rem; color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase; text-align: center; width: 120px;">Lonjakan (%)</th>
                    <th style="padding: 1rem 0.5rem; color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase; text-align: right;">Ctn Size</th>
                    <th style="padding: 1rem 0.5rem; color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase; text-align: right;">Butuh (Hari)</th>
                    <th style="padding: 1rem 0.5rem; color: var(--accent); font-size: 0.85rem; text-transform: uppercase; text-align: right; font-weight: 800;">Rekomendasi Order</th>
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
                        <td style="padding: 1rem 0.5rem; text-align: center;">
                            <input type="number" class="surge-input" value="0" min="0" step="5"
                                   style="width: 60px; background: var(--bg-primary); border: 1px solid var(--border); color: var(--text-primary); border-radius: 6px; padding: 0.2rem; text-align: center; outline: none;">
                        </td>
                        <td style="padding: 1rem 0.5rem; text-align: right; color: var(--text-muted); font-size: 0.85rem;">
                             x{{ $ctnSize }}
                        </td>
                        <td style="padding: 1rem 0.5rem; text-align: right; color: var(--text-muted); font-size: 0.85rem;" class="target-days-cell">

                            30 Hari
                        </td>
                        <td style="padding: 1rem 0.5rem; text-align: right;">
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
    const surgeInputs = document.querySelectorAll('.surge-input');
    
    function calculateOrders() {
        const targetDays = parseInt(globalTargetInput.value);
        targetDisplay.innerText = targetDays + ' Hari';
        
        document.querySelectorAll('.order-row').forEach(row => {
            const stock = parseFloat(row.dataset.stock);
            const ads = parseFloat(row.dataset.ads);
            const ctnSize = parseInt(row.dataset.ctnSize) || 1;
            const surge = parseFloat(row.querySelector('.surge-input').value) || 0;
            
            // Re-calc logic: (ADS * (1 + surge/100) * targetDays) - currentStock
            const adjustedVelocity = ads * (1 + (surge / 100));
            const totalNeed = adjustedVelocity * targetDays;
            const recommend = Math.max(0, Math.ceil(totalNeed - stock));
            
            row.querySelector('.target-days-cell').innerText = targetDays + ' Hari';
            const orderText = row.querySelector('.order-qty-text');
            orderText.innerText = recommend.toLocaleString('id-ID');
            
            const ctnText = row.querySelector('.order-ctn-text');
            const ctnValue = (recommend / ctnSize).toFixed(1);
            ctnText.innerText = ctnValue + ' Ctn';
            
            if (recommend > 0) {

                orderText.parentElement.parentElement.style.background = 'rgba(0, 153, 255, 0.05)';
            } else {
                orderText.parentElement.parentElement.style.background = 'transparent';
            }
        });
    }
    
    globalTargetInput.addEventListener('input', calculateOrders);
    surgeInputs.forEach(input => input.addEventListener('input', calculateOrders));
    
    window.exportToExcel = function() {
        const targetDays = globalTargetInput.value;
        const branch = "{{ $selected_branch }}";
        const principle = "{{ $selected_principle }}";
        const fileName = `Rekomendasi_Order_${principle}_${branch}_${targetDays}Hari.xlsx`;
        
        // Prepare data for Excel
        const data = [
            ["LAPORAN REKOMENDASI ORDER PABRIK"],
            ["Wilayah", branch],
            ["Prinsipel", principle],
            ["Target Stok", targetDays + " Hari"],
            ["Tanggal Export", new Date().toLocaleString()],
            [],
            ["PRINSIPEL", "NAMA PRODUK", "STOK FISIK", "ADS (90 HARI)", "LONJAKAN (%)", "ISI/CTN", "BUTUH (HARI)", "ORDER (PCS)", "ORDER (CTN)"]
        ];
        
        document.querySelectorAll('.order-row').forEach(row => {
            const cells = row.querySelectorAll('td');
            const principleName = row.querySelector('div').innerText;
            const productName = row.querySelector('strong').innerText;
            const stock = row.dataset.stock;
            const ads = row.dataset.ads;
            const ctnSize = row.dataset.ctnSize;
            const surge = row.querySelector('.surge-input').value;
            const recommend = row.querySelector('.order-qty-text').innerText.replace(/\./g, '');
            const ctnVal = (parseInt(recommend) / parseInt(ctnSize)).toFixed(1);
            
            if (parseInt(recommend) > 0) {
              data.push([
                  principleName,
                  productName,
                  parseFloat(stock),
                  parseFloat(ads),
                  parseFloat(surge) + "%",
                  parseInt(ctnSize),
                  targetDays + " Hari",
                  parseInt(recommend),
                  parseFloat(ctnVal)
              ]);
            }
        });

        
        const ws = XLSX.utils.aoa_to_sheet(data);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Order List");
        
        // Auto-size columns
        const colWidths = [20, 40, 15, 15, 15, 15, 20];
        ws['!cols'] = colWidths.map(w => ({ wch: w }));
        
        XLSX.writeFile(wb, fileName);
    };

    // Initial calc
    calculateOrders();
});
</script>

@endsection
