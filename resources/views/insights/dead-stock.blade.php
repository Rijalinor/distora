@extends('layouts.app')
@section('title', 'Analisis Produk Mati')
@section('content')
<div class="mb-4 d-flex justify-content-between align-items-center" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <a href="{{ route('insights.index', ['branch' => $selected_branch, 'period_id' => $activePeriod->id]) }}" class="btn-back" style="text-decoration: none; color: var(--accent); font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">← Kembali</a>
        <h1 style="font-size: 1.5rem; font-weight: 700;">❄️ Analisis Produk "Mati" (Dead Stock)</h1>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Produk yang mengendap di gudang tanpa penjualan sama sekali periode <strong>{{ $activePeriod->name }}</strong>.</p>
    </div>

    <form method="GET" action="{{ route('insights.dead-stock') }}" style="display: flex; gap: 0.75rem; align-items: center; background: var(--bg-card); padding: 0.5rem 1rem; border-radius: 12px; border: 1px solid var(--border);">
        <label for="period_id" style="font-size: 0.85rem; color: var(--text-secondary); font-weight: 600;">Periode:</label>
        <select name="period_id" onchange="this.form.submit()" style="padding: 0.4rem; border: none; background: transparent; color: var(--accent-hover); font-weight: 800; outline: none; cursor: pointer;">
            @foreach($allPeriods as $p)
                <option value="{{ $p->id }}" {{ $p->id === $activePeriod->id ? 'selected' : '' }}>
                    {{ $p->name }} {{ $p->status === 'closed' ? '(Closed)' : '' }}
                </option>
            @endforeach
        </select>
        
        <div style="width: 1px; height: 20px; background: var(--border);"></div>

        <label for="principle" style="font-size: 0.85rem; color: var(--text-secondary); font-weight: 600;">Prinsipel:</label>
        <select name="principle" id="principle" onchange="this.form.submit()" style="padding: 0.4rem; border: none; background: transparent; color: var(--text-primary); font-weight: 700; outline: none; cursor: pointer; max-width: 150px;">
            <option value="all" {{ $selected_principle === 'all' ? 'selected' : '' }}>Semua Prinsipel</option>
            @foreach($allPrinciples as $p)
                <option value="{{ $p }}" {{ $selected_principle === $p ? 'selected' : '' }}>{{ $p }}</option>
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
    </form>
</div>

<!-- Cara Membaca Card -->
<div class="main-card mb-4" style="background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border); padding: 1.5rem; margin-bottom: 1.5rem; border-left: 4px solid #f472b6;">
    <h3 style="font-size: 1rem; margin-bottom: 0.5rem; color: var(--text-primary); display: flex; align-items: center; gap: 0.5rem;">📖 Cara Membaca & Bertindak</h3>
    <p style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.5;">
        Daftar ini adalah "Uang Mati" Anda. Segera lakukan <strong>Cuci Gudang</strong> atau retur ke supplier. 
        Cairkan aset ini kembali menjadi modal berputar.
    </p>
</div>

<div class="grid-layout" style="display: grid; grid-template-columns: 1fr 350px; gap: 1.5rem; align-items: start;">
    <div class="main-card" style="background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border); padding: 1.5rem;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border); text-align: left;">
                    <th style="padding: 1rem 0.5rem; color: var(--text-secondary); font-size: 0.8rem; text-transform: uppercase;">Produk</th>
                    <th style="padding: 1rem 0.5rem; color: var(--text-secondary); font-size: 0.8rem; text-transform: uppercase; text-align: right;">Sisa Stok</th>
                    <th style="padding: 1rem 0.5rem; color: var(--text-secondary); font-size: 0.8rem; text-transform: uppercase; text-align: right;">Nilai Aset</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $item)
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 1rem 0.5rem;"><strong style="color: var(--text-primary); font-size: 0.8rem;">{{ $item->name }}</strong></td>
                        <td style="padding: 1rem 0.5rem; text-align: right;">{{ number_format($item->stock, 0, ',', '.') }} pcs</td>
                        <td style="padding: 1rem 0.5rem; text-align: right; color: var(--warning);">Rp {{ number_format($item->value, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="sidebar">
        <div class="impact-box" style="background: linear-gradient(135deg, rgba(244, 114, 182, 0.1), rgba(99, 102, 241, 0.1)); border: 1px solid #f472b6; border-radius: 16px; padding: 1.5rem;">
            <h3 style="font-size: 1rem; margin-bottom: 1rem; color: #f472b6; display: flex; align-items: center; gap: 0.5rem;">💡 Pencairan Modal</h3>
            <ul style="list-style: none; color: var(--text-primary); font-size: 0.875rem; display: flex; flex-direction: column; gap: 1rem;">
                <li>🏷️ <strong>Cuci Gudang:</strong> Berikan diskon agresif atau jadikan barang hadiah (*Bonus*) untuk pembelanjaan produk lain yang sejenis.</li>
                <li>🔄 <strong>Retur Supplier:</strong> Jika memungkinkan secara kontrak, lakukan retur atau tukar guling dengan produk yang lebih laku (*Fast Moving*).</li>
            </ul>
        </div>
    </div>
</div>
@endsection
