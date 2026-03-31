@extends('layouts.app')

@section('content')
    <div style="display: flex; gap: 2rem; flex-wrap: wrap;">
        <!-- Sidebar -->
        <aside style="width: 220px; flex-shrink: 0;">
            <div style="position: sticky; top: 80px;">
                <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); margin-bottom: 0.75rem; padding-left: 0.75rem;">📊 Penjualan</div>
                <a href="{{ route('reports.sales-summary') }}" class="report-nav {{ request()->routeIs('reports.sales-summary') ? 'active' : '' }}">Ringkasan Penjualan</a>
                <a href="{{ route('reports.top-products') }}" class="report-nav {{ request()->routeIs('reports.top-products') ? 'active' : '' }}">Top Produk</a>
                <a href="{{ route('reports.top-outlets') }}" class="report-nav {{ request()->routeIs('reports.top-outlets') ? 'active' : '' }}">Top Outlet</a>
                <a href="{{ route('reports.sales-by-salesman') }}" class="report-nav {{ request()->routeIs('reports.sales-by-salesman') ? 'active' : '' }}">Per Salesman</a>
                <a href="{{ route('reports.sales-by-principle') }}" class="report-nav {{ request()->routeIs('reports.sales-by-principle') ? 'active' : '' }}">Per Principle</a>

                <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); margin: 1.25rem 0 0.75rem; padding-left: 0.75rem;">📦 Stok</div>
                <a href="{{ route('reports.stock-by-warehouse') }}" class="report-nav {{ request()->routeIs('reports.stock-by-warehouse') ? 'active' : '' }}">Per Gudang</a>
                <a href="{{ route('reports.slow-moving') }}" class="report-nav {{ request()->routeIs('reports.slow-moving') ? 'active' : '' }}">Slow Moving</a>
                <a href="{{ route('reports.stock-coverage') }}" class="report-nav {{ request()->routeIs('reports.stock-coverage') ? 'active' : '' }}">Stock Coverage</a>

                <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); margin: 1.25rem 0 0.75rem; padding-left: 0.75rem;">🔄 Return</div>
                <a href="{{ route('reports.return-rate') }}" class="report-nav {{ request()->routeIs('reports.return-rate') ? 'active' : '' }}">Return Rate</a>
                <a href="{{ route('reports.top-returns') }}" class="report-nav {{ request()->routeIs('reports.top-returns') ? 'active' : '' }}">Top Return</a>

                <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); margin: 1.25rem 0 0.75rem; padding-left: 0.75rem;">💰 Keuangan</div>
                <a href="{{ route('reports.discount-summary') }}" class="report-nav {{ request()->routeIs('reports.discount-summary') ? 'active' : '' }}">Ringkasan Diskon</a>
                <a href="{{ route('reports.gross-vs-net') }}" class="report-nav {{ request()->routeIs('reports.gross-vs-net') ? 'active' : '' }}">Gross vs Net</a>
                <a href="{{ route('reports.tax-vat-compliance') }}" class="report-nav {{ request()->routeIs('reports.tax-vat-compliance') ? 'active' : '' }}">Tax & VAT Compliance</a>
            </div>
        </aside>

        <main class="report-main" style="flex: 1; min-width: 0;">
            <div class="report-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h1 class="report-title">@yield('report_title')</h1>
                
                @if(request()->segment(2) && in_array(request()->segment(2), ['sales-summary', 'top-products', 'stock-by-warehouse']))
                    <a href="{{ route('export.download', request()->segment(2)) }}" class="btn btn-sm" style="background: #10b981; color: white;">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-right: 6px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Export Excel
                    </a>
                @endif
            </div>
            
            <!-- Filters -->
            @yield('report-content')
        </main>
    </div>

    <style>
        .report-nav {
            display: block; padding: 0.5rem 0.75rem; border-radius: 8px;
            color: var(--text-secondary); text-decoration: none; font-size: 0.85rem;
            transition: all 0.2s; margin-bottom: 2px;
        }
        .report-nav:hover { color: var(--text-primary); background: var(--bg-card); }
        .report-nav.active { color: var(--accent-hover); background: var(--accent-glow); font-weight: 500; }

        .report-header { margin-bottom: 1.5rem; }
        .report-title { font-size: 1.25rem; font-weight: 700; margin-bottom: 0.25rem; }
        .report-subtitle { color: var(--text-muted); font-size: 0.875rem; }

        .filter-bar {
            display: flex; gap: 0.75rem; margin-bottom: 1.5rem; flex-wrap: wrap; align-items: center;
        }
        .filter-input {
            background: var(--bg-input); border: 1px solid var(--border); color: var(--text-primary);
            padding: 0.5rem 0.75rem; border-radius: 8px; font-size: 0.85rem; font-family: var(--font);
        }
        .filter-input:focus { outline: none; border-color: var(--accent); }
        select.filter-input { cursor: pointer; }

        .number { font-variant-numeric: tabular-nums; }
        .text-right { text-align: right; }
        .text-success { color: var(--success); }
        .text-danger { color: var(--danger); }
        .text-warning { color: var(--warning); }

        .bar-cell { position: relative; }
        .bar-fill {
            position: absolute; top: 0; left: 0; bottom: 0;
            background: var(--accent-glow); border-radius: 0 4px 4px 0;
            z-index: 0;
        }
        .bar-cell span { position: relative; z-index: 1; }

        @media (max-width: 768px) {
            aside { width: 100% !important; }
            aside > div { position: static !important; display: flex; flex-wrap: wrap; gap: 0.25rem; }
            aside > div > div { display: none; }
        }
    </style>
@endsection
