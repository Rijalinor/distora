@extends('layouts.app')

@section('title', 'AI Analisis & Prediksi')

@section('content')
<div class="mb-4">
    <div class="mb-4 d-flex justify-content-between align-items-center" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 700;">🧠 AI Analisis & Prediksi</h1>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Pusat kecerdasan data Distora menggunakan Machine Learning.</p>
        </div>

        <form method="GET" action="{{ route('insights.ai-dashboard') }}" style="display: flex; gap: 1rem; align-items: center; background: var(--bg-card); padding: 0.5rem 1rem; border-radius: 12px; border: 1px solid var(--border); flex-wrap: wrap;">
            <div style="display: flex; flex-direction: column;">
                <label style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase;">Wilayah</label>
                <select name="branch" onchange="this.form.submit()" style="padding: 0.2rem; border: none; background: transparent; color: var(--text-primary); font-weight: 600; outline: none; cursor: pointer;">
                    <option value="all" {{ $branch === 'all' ? 'selected' : '' }}>Semua Cabang</option>
                    <option value="OBM_01" {{ $branch === 'OBM_01' ? 'selected' : '' }}>Banjarmasin</option>
                    <option value="OBM_02" {{ $branch === 'OBM_02' ? 'selected' : '' }}>Barabai</option>
                    <option value="OBM_03" {{ $branch === 'OBM_03' ? 'selected' : '' }}>Batulicin</option>
                </select>
            </div>
            <div style="width: 1px; height: 30px; background: var(--border);"></div>
            <div style="display: flex; flex-direction: column;">
                <label style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase;">Periode</label>
                <select name="period_id" onchange="this.form.submit()" style="padding: 0.2rem; border: none; background: transparent; color: var(--accent-hover); font-weight: 800; outline: none; cursor: pointer;">
                    @foreach($allPeriods as $p)
                        <option value="{{ $p->id }}" {{ $p->id === $activePeriod->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
</div>

<div class="row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 2rem;">
    <!-- AI Advisor (Decision Assistant) -->
    <a href="{{ route('insights.ai-advisor', ['branch' => $branch, 'period_id' => $activePeriod->id]) }}" style="text-decoration: none; display: block;">
        <div style="background: linear-gradient(135deg, var(--bg-card), rgba(168, 85, 247, 0.05)); border-radius: 20px; border: 1px solid var(--accent); padding: 2rem; transition: all 0.3s; height: 100%; position: relative;" onmouseover="this.style.borderColor='#a855f7'; this.style.transform='translateY(-5px)';" onmouseout="this.style.borderColor='var(--accent)'; this.style.transform='translateY(0)';">
            <div style="font-size: 3rem; margin-bottom: 1.5rem;">🧠</div>
            <h2 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 0.5rem; color: #fff;">AI Decision Advisor</h2>
            <div style="font-size: 0.75rem; color: var(--accent); font-weight: 700; text-transform: uppercase; margin-bottom: 1rem;">
                ✨ {{ $advisorCount }} Saran Tindakan Baru
            </div>
            <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 2rem;">Asisten pusat yang merangkum anomali, risiko stok, dan peluang cuci gudang dalam satu pusat kendali.</p>
            <div style="background: linear-gradient(90deg, var(--accent), #a855f7); color: white; padding: 0.75rem 1.25rem; border-radius: 12px; font-weight: 700; text-align: center; display: inline-block;">Lihat Rekomendasi →</div>
        </div>
    </a>

    <!-- AI Salesman -->
    <a href="{{ route('insights.salesman-intelligence', ['branch' => $branch, 'period_id' => $activePeriod->id]) }}" style="text-decoration: none; display: block;">
        <div style="background: var(--bg-card); border-radius: 20px; border: 1px solid var(--border); padding: 2rem; transition: all 0.3s; height: 100%;" onmouseover="this.style.borderColor='var(--accent)'; this.style.transform='translateY(-5px)';" onmouseout="this.style.borderColor='var(--border)'; this.style.transform='translateY(0)';">
            <div style="font-size: 3rem; margin-bottom: 1.5rem;">👥</div>
            <h2 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 1rem; color: #fff;">Salesman Intelligence AI</h2>
            <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 2rem;">Prediksi pencapaian (Run Rate) dan deteksi tren performa tiap salesman lewat Machine Learning (Linear Regression).</p>
            <div style="background: rgba(99, 102, 241, 0.1); color: var(--accent-hover); padding: 0.75rem 1.25rem; border-radius: 12px; font-weight: 700; text-align: center; display: inline-block;">Masuk Dashboard Sales →</div>
        </div>
    </a>

    <!-- AI Inventory (Purchase Order) -->
    <a href="{{ route('insights.purchase-order', ['branch' => $branch, 'period_id' => $activePeriod->id, 'mode' => 'ai']) }}" style="text-decoration: none; display: block;">
        <div style="background: var(--bg-card); border-radius: 20px; border: 1px solid var(--border); padding: 2rem; transition: all 0.3s; height: 100%;" onmouseover="this.style.borderColor='#10b981'; this.style.transform='translateY(-5px)';" onmouseout="this.style.borderColor='var(--border)'; this.style.transform='translateY(0)';">
            <div style="font-size: 3rem; margin-bottom: 1.5rem;">📦</div>
            <h2 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 1rem; color: #fff;">Smart AI Purchase Order</h2>
            <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 2rem;">Rekomendasi stok pabrik yang cerdas. Menghitung kebutuhan 30-90 hari ke depan berdasarkan momentum penjualan riil.</p>
            <div style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 0.75rem 1.25rem; border-radius: 12px; font-weight: 700; text-align: center; display: inline-block;">Cek Stok Cerdas →</div>
        </div>
    </a>

    <!-- AI Principal Analyst -->
    <a href="{{ route('insights.principal-report', ['branch' => $branch, 'period_id' => $activePeriod->id, 'mode' => 'ai']) }}" style="text-decoration: none; display: block;">
        <div style="background: var(--bg-card); border-radius: 20px; border: 1px solid var(--border); padding: 2rem; transition: all 0.3s; height: 100%;" onmouseover="this.style.borderColor='#3b82f6'; this.style.transform='translateY(-5px)';" onmouseout="this.style.borderColor='var(--border)'; this.style.transform='translateY(0)';">
            <div style="font-size: 3rem; margin-bottom: 1.5rem;">🏢</div>
            <h2 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 1rem; color: #fff;">Principal Intelligence</h2>
            <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 2rem;">Audit cerdas per brand. Membandingkan pertumbuhan antar periode dan memprediksi target brand bulan depan.</p>
            <div style="background: rgba(59, 130, 246, 0.1); color: #3b82f6; padding: 0.75rem 1.25rem; border-radius: 12px; font-weight: 700; text-align: center; display: inline-block;">Buka Insight Brand →</div>
        </div>
    </a>
</div>

<div class="mt-5" style="border-top: 1px solid var(--border); padding-top: 2rem;">
    <div style="background: rgba(255, 255, 255, 0.02); padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border);">
        <h4 style="font-size: 0.9rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 1rem;">🔧 Status AI Engine</h4>
        <div style="display: flex; gap: 2rem; flex-wrap: wrap;">
            <div>
                <span style="font-size: 0.8rem; color: var(--text-secondary);">Core Engine:</span>
                <span style="font-size: 0.8rem; color: var(--success); font-weight: 700;">Python 3.10 (Running)</span>
            </div>
            <div>
                <span style="font-size: 0.8rem; color: var(--text-secondary);">Algorithm:</span>
                <span style="font-size: 0.8rem; color: var(--accent-hover); font-weight: 700;">Weighted Linear Regression V2</span>
            </div>
            <div>
                <span style="font-size: 0.8rem; color: var(--text-secondary);">Memori Data:</span>
                <span style="font-size: 0.8rem; color: var(--text-primary); font-weight: 700;">6 Bulan Terakhir</span>
            </div>
        </div>
    </div>
</div>
@endsection
