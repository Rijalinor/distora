<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — Distora</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet" />
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg-primary: #0a0a0f;
            --bg-secondary: #111118;
            --bg-card: #16161e;
            --bg-card-hover: #1c1c26;
            --bg-input: #1a1a24;
            --border: #2a2a3a;
            --border-light: #3a3a4a;
            --text-primary: #e8e8f0;
            --text-secondary: #8888a0;
            --text-muted: #5a5a72;
            --accent: #6366f1;
            --accent-hover: #818cf8;
            --accent-glow: rgba(99, 102, 241, 0.15);
            --success: #22c55e;
            --success-bg: rgba(34, 197, 94, 0.1);
            --warning: #f59e0b;
            --warning-bg: rgba(245, 158, 11, 0.1);
            --danger: #ef4444;
            --danger-bg: rgba(239, 68, 68, 0.1);
            --info: #3b82f6;
            --info-bg: rgba(59, 130, 246, 0.1);
            --pending: #a78bfa;
            --pending-bg: rgba(167, 139, 250, 0.1);
            --font: 'Inter', -apple-system, BlinkMacSystemFont, system-ui, sans-serif;
        }

        html, body {
            max-width: 100%;
            overflow-x: hidden;
        }

        body {
            font-family: var(--font);
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
        }

        /* Layout */
        .app-wrapper { min-height: 100vh; display: flex; flex-direction: column; }
        .app-header {
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border);
            padding: 0 2rem;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
            backdrop-filter: blur(12px);
        }
        .app-logo {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .app-logo-icon {
            width: 32px; height: 32px;
            background: linear-gradient(135deg, var(--accent), #a855f7);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.85rem; font-weight: 800; color: white;
        }
        .app-nav { display: flex; gap: 0.5rem; }
        .app-nav a {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        .app-nav a:hover, .app-nav a.active {
            color: var(--text-primary);
            background: var(--bg-card);
        }

        .app-content { flex: 1; padding: 2rem; max-width: 1280px; margin: 0 auto; width: 100%; }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.25rem;
            transition: all 0.3s;
        }
        .stat-card:hover {
            border-color: var(--border-light);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.3);
        }
        .stat-label { font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem; }
        .stat-value { font-size: 1.75rem; font-weight: 700; color: var(--text-primary); }

        /* Upload Section */
        .section-title { font-size: 1.125rem; font-weight: 600; margin-bottom: 1rem; color: var(--text-primary); }

        .upload-zone {
            background: var(--bg-card);
            border: 2px dashed var(--border);
            border-radius: 16px;
            padding: 3rem 2rem;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
            margin-bottom: 2rem;
            position: relative;
        }
        .upload-zone:hover, .upload-zone.dragover {
            border-color: var(--accent);
            background: var(--accent-glow);
        }
        .upload-zone-icon { font-size: 3rem; margin-bottom: 1rem; opacity: 0.6; }
        .upload-zone-text { color: var(--text-secondary); font-size: 0.95rem; }
        .upload-zone-text strong { color: var(--accent-hover); }
        .upload-zone input[type="file"] { display: none; }

        .upload-progress {
            display: none;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .upload-progress.active { display: block; }
        .progress-bar-bg {
            height: 6px; background: var(--bg-input); border-radius: 3px; overflow: hidden; margin-top: 0.75rem;
        }
        .progress-bar-fill {
            height: 100%; background: linear-gradient(90deg, var(--accent), #a855f7);
            border-radius: 3px; transition: width 0.3s; width: 0%;
        }

        /* Table */
        .table-wrap {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }
        table { width: 100%; border-collapse: collapse; }
        thead { background: var(--bg-secondary); }
        th {
            text-align: left; padding: 0.75rem 1rem; font-size: 0.75rem;
            text-transform: uppercase; letter-spacing: 0.05em;
            color: var(--text-muted); font-weight: 600; border-bottom: 1px solid var(--border);
        }
        td {
            padding: 0.85rem 1rem; font-size: 0.875rem; border-bottom: 1px solid var(--border);
            color: var(--text-secondary);
        }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: var(--bg-card-hover); }

        /* Status Badge */
        .badge {
            display: inline-flex; align-items: center; gap: 0.35rem;
            padding: 0.25rem 0.75rem; border-radius: 999px;
            font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.03em;
        }
        .badge-success { background: var(--success-bg); color: var(--success); }
        .badge-warning { background: var(--warning-bg); color: var(--warning); }
        .badge-danger { background: var(--danger-bg); color: var(--danger); }
        .badge-info { background: var(--info-bg); color: var(--info); }
        .badge-pending { background: var(--pending-bg); color: var(--pending); }
        .badge-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
        .badge-pending .badge-dot { animation: pulse 2s infinite; }

        /* Buttons */
        .btn {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.6rem 1.25rem; border-radius: 8px;
            font-size: 0.875rem; font-weight: 500;
            border: none; cursor: pointer; transition: all 0.2s;
            text-decoration: none;
        }
        .btn-primary { background: var(--accent); color: white; }
        .btn-primary:hover { background: var(--accent-hover); transform: translateY(-1px); }
        .btn-ghost { background: transparent; color: var(--text-secondary); border: 1px solid var(--border); }
        .btn-ghost:hover { border-color: var(--border-light); color: var(--text-primary); }
        .btn-danger { background: var(--danger-bg); color: var(--danger); border: 1px solid rgba(239,68,68,0.2); }
        .btn-danger:hover { background: rgba(239,68,68,0.2); }
        .btn-sm { padding: 0.35rem 0.75rem; font-size: 0.8rem; }

        /* Detail Cards */
        .detail-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .detail-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; padding: 1.25rem; }
        .detail-card-title { font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.75rem; }
        .detail-row { display: flex; justify-content: space-between; padding: 0.35rem 0; font-size: 0.875rem; }
        .detail-label { color: var(--text-secondary); }
        .detail-value { color: var(--text-primary); font-weight: 500; }

        /* Pagination */
        .pagination { display: flex; gap: 0.25rem; justify-content: center; padding: 1.25rem; }
        .pagination a, .pagination span {
            padding: 0.4rem 0.8rem; border-radius: 6px; font-size: 0.8rem;
            color: var(--text-secondary); text-decoration: none; border: 1px solid var(--border);
        }
        .pagination a:hover { color: var(--text-primary); border-color: var(--border-light); }
        .pagination .active span { background: var(--accent); color: white; border-color: var(--accent); }
        .pagination .disabled span { opacity: 0.3; }

        /* Breadcrumb */
        .breadcrumb { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.5rem; font-size: 0.875rem; }
        .breadcrumb a { color: var(--text-muted); text-decoration: none; }
        .breadcrumb a:hover { color: var(--text-primary); }
        .breadcrumb span.sep { color: var(--text-muted); opacity: 0.5; }
        .breadcrumb span.current { color: var(--text-primary); }

        /* Toast Notification */
        .toast {
            position: fixed; top: 80px; right: 2rem; z-index: 100;
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: 12px; padding: 1rem 1.5rem;
            box-shadow: 0 12px 40px rgba(0,0,0,0.4);
            transform: translateX(120%); transition: transform 0.4s ease;
            display: flex; align-items: center; gap: 0.75rem;
            max-width: 400px;
        }
        .toast.show { transform: translateX(0); }
        .toast-success { border-color: rgba(34,197,94,0.3); }
        .toast-error { border-color: rgba(239,68,68,0.3); }

        /* Empty state */
        .empty-state { text-align: center; padding: 4rem 2rem; color: var(--text-muted); }
        .empty-state-icon { font-size: 3rem; margin-bottom: 1rem; opacity: 0.4; }

        /* Responsive */
        .hamburger { display: none; background: none; border: none; color: var(--text-primary); font-size: 1.5rem; cursor: pointer; padding: 0.5rem; }

        @media (max-width: 768px) {
            .app-header { padding: 0 0.75rem; height: 52px; }
            .app-content { padding: 0.75rem; max-width: 100%; }
            .stats-grid { grid-template-columns: repeat(3, 1fr); gap: 0.5rem; margin-bottom: 1rem; }
            .stat-card { padding: 0.6rem 0.5rem; border-radius: 8px; }
            .stat-label { font-size: 0.6rem; margin-bottom: 0.25rem; }
            .stat-value { font-size: 1rem !important; }
            .table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; margin-bottom: 1rem; }
            table { min-width: 420px; }
            .detail-grid { grid-template-columns: 1fr; }
            .section-title { font-size: 0.9rem; margin-bottom: 0.5rem; }
            h1 { font-size: 1.15rem !important; }
            .hamburger { display: block; }
            .app-logo-icon { width: 28px; height: 28px; font-size: 0.75rem; border-radius: 6px; }
            .app-logo { font-size: 1.05rem; gap: 0.35rem; }
            .app-nav {
                display: none;
                position: absolute;
                top: 52px;
                left: 0;
                right: 0;
                background: var(--bg-secondary);
                border-bottom: 1px solid var(--border);
                flex-direction: column;
                padding: 0.5rem 0.75rem;
                gap: 0.15rem;
                box-shadow: 0 8px 24px rgba(0,0,0,0.4);
                z-index: 49;
            }
            .app-nav.open { display: flex; }
            .app-nav a { padding: 0.6rem 0.75rem; border-radius: 6px; font-size: 0.9rem; }
            .nav-divider { display: none; }
            .nav-user-info {
                flex-direction: row !important;
                align-items: center !important;
                padding: 0.6rem 0.75rem;
                border-top: 1px solid var(--border);
                margin-top: 0.15rem;
            }
            .nav-user-info > div:first-child { align-items: flex-start !important; flex: 1; }
            .btn { padding: 0.4rem 0.75rem; font-size: 0.8rem; }
            .btn-sm { padding: 0.3rem 0.5rem; font-size: 0.7rem; }
            th, td { padding: 0.45rem 0.5rem; font-size: 0.78rem; }
            th { font-size: 0.65rem; }
        }

        @media (max-width: 420px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 0.4rem; }
            .stat-card { padding: 0.5rem; }
            .stat-label { font-size: 0.55rem; }
            .stat-value { font-size: 0.9rem !important; }
            .app-content { padding: 0.5rem; }
            table { min-width: 360px; }
        }

        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }
        @keyframes spin { to { transform: rotate(360deg); } }
        .spinner { width: 16px; height: 16px; border: 2px solid var(--border); border-top-color: var(--accent); border-radius: 50%; animation: spin 0.8s linear infinite; }
    </style>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="app-wrapper">
        <header class="app-header">
            <a href="{{ route('dashboard') }}" class="app-logo">
                <div class="app-logo-icon">D</div>
                Distora
            </a>
            <button class="hamburger" onclick="document.querySelector('.app-nav').classList.toggle('open')" aria-label="Menu">☰</button>
            <nav class="app-nav">
                @if(auth()->check())
                    @if(auth()->user()->role === 'admin')
                        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard*') ? 'active' : '' }}">Dashboard</a>
                        <a href="{{ route('analytics.index') }}" class="{{ request()->routeIs('analytics.*') ? 'active' : '' }}">📈 Analytics</a>
                        <a href="{{ route('targets.index') }}" class="{{ request()->routeIs('targets.*') ? 'active' : '' }}">🎯 Target KPI</a>
                        <a href="{{ route('reports.sales-summary') }}" class="{{ request()->routeIs('reports.*') ? 'active' : '' }}">📊 Laporan</a>
                        <a href="{{ route('reset.index') }}" class="{{ request()->routeIs('reset.*') ? 'active' : '' }}" style="color: {{ request()->routeIs('reset.*') ? 'var(--warning)' : '' }}">📒 Tutup Buku</a>
                        <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'active' : '' }}">👥 User</a>
                    @else
                        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard*') ? 'active' : '' }}">📊 Dashboard Saya</a>
                    @endif
                    
                    <div class="nav-divider" style="width: 1px; background: var(--border); margin: 0 0.5rem; align-self: stretch;"></div>
                    
                    <div class="nav-user-info" style="display: flex; align-items: center; gap: 0.5rem;">
                        <div style="display: flex; flex-direction: column; align-items: flex-end; line-height: 1.2;">
                            <span style="font-size: 0.85rem; font-weight: 600; color: var(--text-primary);">{{ auth()->user()->name }}</span>
                            <span style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase;">{{ auth()->user()->role }}</span>
                        </div>
                        <form method="POST" action="{{ route('logout') }}" style="display:inline; margin-left: 0.5rem;">
                            @csrf
                            <button type="submit" class="btn btn-ghost btn-sm" style="padding: 0.4rem 0.75rem; color: var(--danger); border-color: rgba(239, 68, 68, 0.2);">Logout</button>
                        </form>
                    </div>
                @endif
            </nav>
        </header>

        <main class="app-content">
            @yield('content')
        </main>
    </div>

    <div id="toast" class="toast">
        <span id="toast-icon"></span>
        <span id="toast-message"></span>
    </div>

    <script>
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const icon = document.getElementById('toast-icon');
            const msg = document.getElementById('toast-message');
            toast.className = 'toast toast-' + type + ' show';
            icon.textContent = type === 'success' ? '✓' : '✕';
            msg.textContent = message;
            setTimeout(() => toast.classList.remove('show'), 4000);
        }
    </script>

    @stack('scripts')

    @if(session('success'))
        <script>showToast('{{ session("success") }}', 'success');</script>
    @endif
    @if(session('error'))
        <script>showToast('{{ session("error") }}', 'error');</script>
    @endif
</body>
</html>
