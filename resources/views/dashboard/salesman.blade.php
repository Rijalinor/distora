@extends('layouts.app')
@section('title', 'Dashboard Saya')

@section('content')
    <!-- Greeting -->
    <div style="margin-bottom: 1.5rem;">
        <h1 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;">👋 Halo, {{ $salesName }}</h1>
        <span style="color: var(--text-muted); font-size: 0.875rem;">Periode aktif: <strong style="color: var(--accent-hover);">{{ $activePeriod->name }}</strong></span>
    </div>

    <!-- Personal Stats -->
    <div class="stats-grid">
        <div class="stat-card" style="border-left: 3px solid var(--accent);">
            <div class="stat-label">Total Omzet</div>
            <div class="stat-value" style="font-size: 1.4rem;">Rp {{ number_format($totalOmzet, 0, ',', '.') }}</div>
        </div>
        <div class="stat-card" style="border-left: 3px solid var(--success);">
            <div class="stat-label">Transaksi</div>
            <div class="stat-value">{{ number_format($totalTransaksi) }}</div>
        </div>
        <div class="stat-card" style="border-left: 3px solid var(--info);">
            <div class="stat-label">Outlet Dikunjungi</div>
            <div class="stat-value">{{ number_format($totalOutlets) }}</div>
        </div>
        <div class="stat-card" style="border-left: 3px solid var(--warning);">
            <div class="stat-label">Item Terjual</div>
            <div class="stat-value">{{ number_format($totalItems) }}</div>
        </div>
        <div class="stat-card" style="border-left: 3px solid var(--danger);">
            <div class="stat-label">Retur</div>
            <div class="stat-value" style="font-size: 1.1rem; color: var(--danger);">{{ $totalReturCount }} SP<br><small style="font-size: 0.75rem;">Rp {{ number_format(abs($totalRetur), 0, ',', '.') }}</small></div>
        </div>
    </div>

    <!-- KPI Progress -->
    <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem;">
        <h2 style="font-size: 1rem; font-weight: 600; margin-bottom: 1rem;">🎯 Target KPI Bulan Ini</h2>
        @if(($totalTargetAmount ?? 0) > 0)
            @php
                $color = $kpiProgress >= 100 ? 'var(--success)' : ($kpiProgress >= 70 ? 'var(--warning)' : 'var(--danger)');
            @endphp
            <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-end; margin-bottom: 0.5rem; gap: 0.5rem;">
                <div>
                    <div style="font-size: 0.85rem; color: var(--text-muted);">
                        Aktual: <strong style="color: var(--text-primary);">Rp {{ number_format($totalOmzet, 0, ',', '.') }}</strong> /
                        Target: <strong style="color: var(--text-primary);">Rp {{ number_format($totalTargetAmount, 0, ',', '.') }}</strong>
                    </div>
                </div>
                <div style="font-size: 1.75rem; font-weight: 800; color: {{ $color }};">
                    {{ $kpiProgress }}%
                </div>
            </div>
            <div style="height: 14px; background: rgba(255,255,255,0.05); border-radius: 7px; overflow: hidden;">
                <div style="width: {{ min($kpiProgress, 100) }}%; height: 100%; background: {{ $color }}; border-radius: 7px; transition: width 0.6s ease;"></div>
            </div>
            @if($kpiProgress >= 100)
                <p style="text-align: center; margin-top: 0.75rem; color: var(--success); font-weight: 600;">🏆 TARGET TERCAPAI! Luar biasa!</p>
            @endif
        @else
            <div style="text-align: center; color: var(--text-muted); padding: 1rem 0;">
                Belum ada target KPI yang di-set untuk Anda di bulan ini.
            </div>
        @endif
    </div>

    <!-- Outlets Served -->
    <h2 class="section-title">🏪 Daftar Toko Saya ({{ $outlets->count() }} Outlet)</h2>
    <div class="table-wrap" style="margin-bottom: 2rem;">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Kode</th>
                    <th>Nama Outlet</th>
                    <th>Kota</th>
                    <th>Jumlah SP</th>
                </tr>
            </thead>
            <tbody>
                @forelse($outlets as $i => $outlet)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td style="color: var(--accent-hover); font-weight: 500;">{{ $outlet->code }}</td>
                        <td style="color: var(--text-primary); font-weight: 500;">{{ $outlet->name }}</td>
                        <td>{{ $outlet->city ?? '-' }}</td>
                        <td>{{ $outlet->transactions_count }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">Belum ada data outlet.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($outlets->hasPages())
            <div style="padding: 1rem; border-top: 1px solid var(--border); display: flex; justify-content: center;">
                {{ $outlets->appends(request()->except('outlets_page'))->links('pagination::bootstrap-4') }}
            </div>
            <style>
                .pagination { display: flex; list-style: none; gap: 0.25rem; font-size: 0.85rem; margin: 0; padding: 0; }
                .page-item .page-link { padding: 0.4rem 0.75rem; border-radius: 6px; background: var(--bg-secondary); color: var(--text-primary); text-decoration: none; border: 1px solid var(--border); }
                .page-item.active .page-link { background: var(--accent); color: white; border-color: var(--accent); }
                .page-item.disabled .page-link { opacity: 0.5; pointer-events: none; }
            </style>
        @endif
    </div>

    <!-- Recent Sales Transactions -->
    <h2 class="section-title">📄 Riwayat Penjualan ({{ $recentSales->count() }} SP Terbaru)</h2>
    <div class="table-wrap" style="margin-bottom: 2rem;">
        <table>
            <thead>
                <tr>
                    <th>No. SP</th>
                    <th>Tanggal</th>
                    <th>Outlet</th>
                    <th>Items</th>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentSales as $trx)
                    <tr style="cursor: pointer;" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'none' ? 'table-row' : 'none';">
                        <td style="color: var(--accent-hover); font-weight: 600;">{{ $trx->invoice_number }}</td>
                        <td>{{ $trx->transaction_date?->format('d M Y') ?? '-' }}</td>
                        <td style="color: var(--text-primary);">{{ $trx->outlet?->name ?? '-' }}</td>
                        <td>{{ $trx->sales->count() }} item</td>
                        <td style="text-align: right; color: var(--success); font-weight: 600;">Rp {{ number_format($trx->sales->sum('total'), 0, ',', '.') }}</td>
                    </tr>
                    <!-- Expandable Item Detail -->
                    <tr style="display: none; background: var(--bg-secondary);">
                        <td colspan="5" style="padding: 0.75rem 1rem;">
                            <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.5rem;">📦 Detail Item:</div>
                            <table style="width: 100%; font-size: 0.8rem;">
                                <thead>
                                    <tr>
                                        <th style="padding: 0.35rem 0.5rem; font-size: 0.7rem;">Produk</th>
                                        <th style="padding: 0.35rem 0.5rem; font-size: 0.7rem;">Qty</th>
                                        <th style="padding: 0.35rem 0.5rem; font-size: 0.7rem; text-align: right;">Harga</th>
                                        <th style="padding: 0.35rem 0.5rem; font-size: 0.7rem; text-align: right;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($trx->sales as $sale)
                                        <tr>
                                            <td style="padding: 0.3rem 0.5rem; color: var(--text-primary);">{{ $sale->product?->name ?? 'N/A' }}</td>
                                            <td style="padding: 0.3rem 0.5rem;">{{ number_format($sale->qty) }}</td>
                                            <td style="padding: 0.3rem 0.5rem; text-align: right;">Rp {{ number_format($sale->price, 0, ',', '.') }}</td>
                                            <td style="padding: 0.3rem 0.5rem; text-align: right; color: var(--success);">Rp {{ number_format($sale->total, 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">Belum ada data penjualan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Returns -->
    <h2 class="section-title" style="color: var(--danger);">🔄 Riwayat Retur ({{ $recentReturns->count() }} SP)</h2>
    <div class="table-wrap" style="margin-bottom: 2rem;">
        <table>
            <thead>
                <tr>
                    <th>No. SP Retur</th>
                    <th>Tanggal</th>
                    <th>Outlet</th>
                    <th>Items</th>
                    <th style="text-align: right;">Total Retur</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentReturns as $trx)
                    <tr style="cursor: pointer;" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'none' ? 'table-row' : 'none';">
                        <td style="color: var(--danger); font-weight: 600;">{{ $trx->invoice_number }}</td>
                        <td>{{ $trx->transaction_date?->format('d M Y') ?? '-' }}</td>
                        <td style="color: var(--text-primary);">{{ $trx->outlet?->name ?? '-' }}</td>
                        <td>{{ $trx->sales->count() }} item</td>
                        <td style="text-align: right; color: var(--danger); font-weight: 600;">Rp {{ number_format(abs($trx->sales->sum('total')), 0, ',', '.') }}</td>
                    </tr>
                    <!-- Expandable Item Detail -->
                    <tr style="display: none; background: var(--bg-secondary);">
                        <td colspan="5" style="padding: 0.75rem 1rem;">
                            <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.5rem;">📦 Detail Item Retur:</div>
                            <table style="width: 100%; font-size: 0.8rem;">
                                <thead>
                                    <tr>
                                        <th style="padding: 0.35rem 0.5rem; font-size: 0.7rem;">Produk</th>
                                        <th style="padding: 0.35rem 0.5rem; font-size: 0.7rem;">Qty</th>
                                        <th style="padding: 0.35rem 0.5rem; font-size: 0.7rem; text-align: right;">Harga</th>
                                        <th style="padding: 0.35rem 0.5rem; font-size: 0.7rem; text-align: right;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($trx->sales as $sale)
                                        <tr>
                                            <td style="padding: 0.3rem 0.5rem; color: var(--text-primary);">{{ $sale->product?->name ?? 'N/A' }}</td>
                                            <td style="padding: 0.3rem 0.5rem;">{{ number_format(abs($sale->qty)) }}</td>
                                            <td style="padding: 0.3rem 0.5rem; text-align: right;">Rp {{ number_format(abs($sale->price), 0, ',', '.') }}</td>
                                            <td style="padding: 0.3rem 0.5rem; text-align: right; color: var(--danger);">Rp {{ number_format(abs($sale->total), 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">Tidak ada retur. 🎉</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="text-align: center; color: var(--text-muted); font-size: 0.8rem; padding: 1rem 0;">
        <em>Klik pada baris transaksi untuk melihat detail item. Data di atas merupakan 50 SP penjualan & 30 SP retur terbaru.</em>
    </div>
@endsection
