<div class="d-flex justify-content-between align-items-center mb-4" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.8rem; font-weight: 800; background: linear-gradient(135deg, var(--accent), #f472b6); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Pusat Kendali Keputusan</h1>
        <div style="display: flex; gap: 1rem; align-items: center; margin-top: 0.25rem;">
            <p style="color: var(--text-muted); font-size: 1rem; margin: 0;">Analisis data transaksi <strong>3 bulan terakhir</strong> (hingga {{ $activePeriod->name }}).</p>
            <a href="{{ route('insights.guide') }}" style="color: var(--accent); font-size: 0.9rem; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 0.4rem; padding: 0.2rem 0.6rem; border: 1px solid var(--accent); border-radius: 8px;">Panduan Baca</a>
        </div>
    </div>