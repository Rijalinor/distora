@extends('layouts.app')

@section('title', 'Stock Redistribution Advisor')

@section('content')
<div class="mb-4 d-flex justify-content-between align-items-center" style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;">
    <div>
        <a href="{{ route('insights.index', ['branch' => $selected_branch, 'period_id' => $activePeriod->id]) }}" class="btn-back" style="text-decoration:none;color:var(--accent);font-weight:600;display:inline-flex;align-items:center;gap:.5rem;margin-bottom:.4rem;">&larr; Kembali</a>
        <h1 style="font-size:1.5rem;font-weight:800;">Stock Redistribution Advisor</h1>
        <p style="color:var(--text-muted);font-size:.9rem;">Prioritaskan mutasi antar gudang sebelum order pabrik berdasarkan SWC.</p>
    </div>

    <form method="GET" action="{{ route('insights.stock-redistribution') }}" style="display:flex;gap:.75rem;align-items:center;background:var(--bg-card);padding:.5rem 1rem;border-radius:12px;border:1px solid var(--border);flex-wrap:wrap;">
        <select name="period_id" onchange="this.form.submit()" style="padding:.35rem;border:none;background:transparent;color:var(--text-primary);font-weight:700;outline:none;cursor:pointer;">
            @foreach($allPeriods as $p)
                <option value="{{ $p->id }}" {{ $p->id === $activePeriod->id ? 'selected' : '' }}>{{ $p->name }}</option>
            @endforeach
        </select>
        <select name="branch" onchange="this.form.submit()" style="padding:.35rem;border:none;background:transparent;color:var(--text-primary);font-weight:700;outline:none;cursor:pointer;">
            <option value="all" {{ $selected_branch === 'all' ? 'selected' : '' }}>Semua Cabang</option>
            <option value="OBM_01" {{ $selected_branch === 'OBM_01' ? 'selected' : '' }}>Banjarmasin</option>
            <option value="OBM_02" {{ $selected_branch === 'OBM_02' ? 'selected' : '' }}>Barabai</option>
            <option value="OBM_03" {{ $selected_branch === 'OBM_03' ? 'selected' : '' }}>Batulicin</option>
        </select>
        <select name="principle" onchange="this.form.submit()" style="padding:.35rem;border:none;background:transparent;color:var(--text-primary);font-weight:700;outline:none;cursor:pointer;">
            <option value="all" {{ $selected_principle === 'all' ? 'selected' : '' }}>Semua Prinsipel</option>
            @foreach($principles as $p)
                <option value="{{ $p }}" {{ $selected_principle === $p ? 'selected' : '' }}>{{ $p }}</option>
            @endforeach
        </select>
        <label style="font-size:.75rem;color:var(--text-muted);">Needy SWC <=</label>
        <input type="number" step="0.5" name="needy_max_swc" value="{{ $needy_max_swc }}" style="width:70px;background:var(--bg-primary);color:var(--text-primary);border:1px solid var(--border);border-radius:6px;padding:.3rem .4rem;">
        <label style="font-size:.75rem;color:var(--text-muted);">Donor SWC ></label>
        <input type="number" step="0.5" name="donor_min_swc" value="{{ $donor_min_swc }}" style="width:70px;background:var(--bg-primary);color:var(--text-primary);border:1px solid var(--border);border-radius:6px;padding:.3rem .4rem;">
        <label style="font-size:.75rem;color:var(--text-muted);">Target SWC</label>
        <input type="number" step="0.5" name="target_swc" value="{{ $target_swc }}" style="width:70px;background:var(--bg-primary);color:var(--text-primary);border:1px solid var(--border);border-radius:6px;padding:.3rem .4rem;">
        <button type="submit" style="border:none;background:var(--accent);color:#fff;padding:.45rem .8rem;border-radius:8px;font-weight:700;cursor:pointer;">Apply</button>
    </form>
</div>

<div class="main-card" style="background:var(--bg-card);border:1px solid var(--border);border-radius:14px;padding:1rem;">
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="border-bottom:1px solid var(--border);">
                    <th style="padding:.8rem .4rem;text-align:left;color:var(--text-muted);font-size:.75rem;">Prinsipel / Produk</th>
                    <th style="padding:.8rem .4rem;text-align:left;color:var(--text-muted);font-size:.75rem;">Cabang Butuh</th>
                    <th style="padding:.8rem .4rem;text-align:right;color:var(--text-muted);font-size:.75rem;">SWC Butuh</th>
                    <th style="padding:.8rem .4rem;text-align:right;color:var(--text-muted);font-size:.75rem;">Defisit</th>
                    <th style="padding:.8rem .4rem;text-align:left;color:var(--text-muted);font-size:.75rem;">Saran</th>
                    <th style="padding:.8rem .4rem;text-align:left;color:var(--text-muted);font-size:.75rem;">Donor</th>
                    <th style="padding:.8rem .4rem;text-align:right;color:var(--text-muted);font-size:.75rem;">Qty Mutasi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data as $row)
                    <tr style="border-bottom:1px solid var(--border);background:{{ $row->recommendation === 'MUTASI_STOK' ? 'rgba(16,185,129,.06)' : 'rgba(245,158,11,.06)' }};">
                        <td style="padding:.8rem .4rem;">
                            <div style="font-size:.68rem;color:var(--text-muted);text-transform:uppercase;">{{ $row->principle_name }}</div>
                            <strong style="font-size:.84rem;color:var(--text-primary);">{{ $row->product_name }}</strong>
                        </td>
                        <td style="padding:.8rem .4rem;color:var(--text-primary);font-weight:700;">{{ $row->need_branch }}</td>
                        <td style="padding:.8rem .4rem;text-align:right;color:var(--danger);font-weight:800;">{{ number_format($row->need_swc, 1) }}</td>
                        <td style="padding:.8rem .4rem;text-align:right;color:var(--text-primary);">{{ number_format($row->deficit_qty, 0, ',', '.') }}</td>
                        <td style="padding:.8rem .4rem;">
                            <span style="font-size:.72rem;font-weight:800;padding:.25rem .5rem;border-radius:999px;color:#fff;background:{{ $row->recommendation === 'MUTASI_STOK' ? '#10b981' : '#f59e0b' }};">
                                {{ $row->recommendation === 'MUTASI_STOK' ? 'Mutasi Stok' : 'Order Pabrik' }}
                            </span>
                        </td>
                        <td style="padding:.8rem .4rem;color:var(--text-primary);">
                            {{ $row->donor_branch ?? '-' }}
                            @if($row->donor_swc)
                                <div style="font-size:.68rem;color:var(--text-muted);">SWC {{ number_format($row->donor_swc, 1) }}</div>
                            @endif
                        </td>
                        <td style="padding:.8rem .4rem;text-align:right;color:var(--accent);font-weight:800;">{{ number_format($row->transfer_qty, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="padding:1rem;text-align:center;color:var(--text-muted);">Tidak ada kandidat redistribusi untuk parameter saat ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

