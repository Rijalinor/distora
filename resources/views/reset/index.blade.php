@extends('layouts.app')
@section('title', 'Tutup Buku')

@section('content')
    <div style="max-width: 720px; margin: 0 auto;">
        <div class="breadcrumb">
            <a href="{{ route('dashboard') }}">Dashboard</a>
            <span class="sep">›</span>
            <span class="current">Tutup Buku</span>
        </div>

        <!-- Header -->
        <div style="text-align: center; margin-bottom: 2rem;">
            <div style="font-size: 3rem; margin-bottom: 0.75rem;">📒</div>
            <h1 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem;">Tutup Buku — {{ $summary['period']->name }}</h1>
            <p style="color: var(--text-muted); font-size: 0.95rem;">
                Tutup periode ini untuk memulai bulan baru. Data akan diarsip sebagai ringkasan.
            </p>
        </div>

        <!-- Active Period Data -->
        <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem;">
            <div style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); margin-bottom: 1rem;">
                📊 Ringkasan Periode {{ $summary['period']->name }}
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 0.75rem;">
                <div style="background: var(--bg-input); border-radius: 8px; padding: 0.75rem 1rem;">
                    <div style="font-size: 0.7rem; color: var(--text-muted);">Upload</div>
                    <div style="font-size: 1.25rem; font-weight: 700;">{{ number_format($summary['upload_histories']) }}</div>
                </div>
                <div style="background: var(--bg-input); border-radius: 8px; padding: 0.75rem 1rem;">
                    <div style="font-size: 0.7rem; color: var(--text-muted);">Transaksi</div>
                    <div style="font-size: 1.25rem; font-weight: 700;">{{ number_format($summary['transactions']) }}</div>
                </div>
                <div style="background: var(--bg-input); border-radius: 8px; padding: 0.75rem 1rem;">
                    <div style="font-size: 0.7rem; color: var(--text-muted);">Sales</div>
                    <div style="font-size: 1.25rem; font-weight: 700;">{{ number_format($summary['sales']) }}</div>
                </div>
                <div style="background: var(--bg-input); border-radius: 8px; padding: 0.75rem 1rem;">
                    <div style="font-size: 0.7rem; color: var(--text-muted);">Return</div>
                    <div style="font-size: 1.25rem; font-weight: 700;">{{ number_format($summary['returns']) }}</div>
                </div>
                <div style="background: var(--bg-input); border-radius: 8px; padding: 0.75rem 1rem;">
                    <div style="font-size: 0.7rem; color: var(--text-muted);">Stok</div>
                    <div style="font-size: 1.25rem; font-weight: 700;">{{ number_format($summary['stocks']) }}</div>
                </div>
            </div>

            @if($summary['total_sales_value'] > 0)
                <div style="margin-top: 1rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                    <div style="background: var(--success-bg); border-radius: 8px; padding: 0.75rem 1rem; flex: 1;">
                        <div style="font-size: 0.75rem; color: var(--success);">Total Penjualan</div>
                        <div style="font-size: 1.1rem; font-weight: 700; color: var(--success);">Rp {{ number_format($summary['total_sales_value'], 0, ',', '.') }}</div>
                    </div>
                    <div style="background: var(--danger-bg); border-radius: 8px; padding: 0.75rem 1rem; flex: 1;">
                        <div style="font-size: 0.75rem; color: var(--danger);">Total Return</div>
                        <div style="font-size: 1.1rem; font-weight: 700; color: var(--danger);">Rp {{ number_format(abs($summary['total_return_value']), 0, ',', '.') }}</div>
                    </div>
                </div>
            @endif
        </div>

        <!-- What happens -->
        <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; padding: 1.25rem; margin-bottom: 1.5rem;">
            <div style="font-size: 0.875rem; font-weight: 600; margin-bottom: 0.75rem;">Apa yang terjadi saat tutup buku?</div>
            <ul style="color: var(--text-secondary); font-size: 0.875rem; padding-left: 1.25rem; margin: 0; line-height: 1.8;">
                <li>✅ Ringkasan bulan ini <strong style="color: var(--success);">disimpan permanen</strong> (bisa dilihat kapan saja)</li>
                <li>🗑 Data detail (transaksi, sales, stok) dihapus untuk ruang bersih</li>
                <li>📅 Periode bulan baru otomatis dibuat</li>
                <li>📥 Siap menerima data import bulan baru</li>
            </ul>
        </div>

        <!-- Confirmation Form -->
        <form method="POST" action="{{ route('reset.execute') }}" id="closeForm"
              style="background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem;">
            @csrf

            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 0.5rem;">
                    Ketik <strong style="color: var(--warning);">TUTUP</strong> untuk konfirmasi:
                </label>
                <input type="text" name="confirmation" id="confirmInput"
                       style="width: 100%; background: var(--bg-input); border: 1px solid var(--border); color: var(--text-primary);
                              padding: 0.75rem 1rem; border-radius: 8px; font-size: 1rem; font-family: monospace; letter-spacing: 0.1em; text-align: center;"
                       placeholder="Ketik TUTUP di sini" autocomplete="off" required>

                @error('confirmation')
                    <div style="color: var(--danger); font-size: 0.8rem; margin-top: 0.5rem;">{{ $message }}</div>
                @enderror
            </div>

            <div style="display: flex; gap: 0.75rem;">
                <a href="{{ route('dashboard') }}" class="btn btn-ghost" style="flex: 1; justify-content: center;">← Batal</a>
                <button type="submit" id="closeBtn" class="btn" disabled
                        style="flex: 1; justify-content: center; background: var(--border); color: var(--text-muted); cursor: not-allowed; transition: all 0.3s;">
                    📒 Tutup Buku {{ $summary['period']->name }}
                </button>
            </div>
        </form>

        @if(session('error'))
            <div style="background: var(--danger-bg); border: 1px solid rgba(239,68,68,0.3); border-radius: 12px; padding: 1rem 1.25rem; margin-bottom: 1rem; color: var(--danger); font-size: 0.875rem;">
                {{ session('error') }}
            </div>
        @endif

        <!-- Closed Periods History -->
        @if($closedPeriods->isNotEmpty())
            <h2 class="section-title" style="margin-top: 2rem;">📚 Riwayat Periode</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Periode</th>
                            <th class="text-right">Sales</th>
                            <th class="text-right">Return</th>
                            <th class="text-right">Total Penjualan</th>
                            <th>Ditutup</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($closedPeriods as $p)
                            <tr>
                                <td style="color: var(--text-primary); font-weight: 500;">{{ $p->name }}</td>
                                <td class="text-right number">{{ number_format($p->summary['sales_count'] ?? 0) }}</td>
                                <td class="text-right number">{{ number_format($p->summary['returns_count'] ?? 0) }}</td>
                                <td class="text-right number text-success" style="font-weight: 600;">
                                    Rp {{ number_format($p->summary['total_sales'] ?? 0, 0, ',', '.') }}
                                </td>
                                <td>{{ $p->closed_at->format('d M Y H:i') }}</td>
                                <td>
                                    <a href="{{ route('reset.show', $p->id) }}" class="btn btn-ghost btn-sm">Detail</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <script>
        const input = document.getElementById('confirmInput');
        const btn = document.getElementById('closeBtn');
        input.addEventListener('input', function() {
            if (this.value === 'TUTUP') {
                btn.disabled = false;
                btn.style.background = 'var(--warning)';
                btn.style.color = '#000';
                btn.style.cursor = 'pointer';
            } else {
                btn.disabled = true;
                btn.style.background = 'var(--border)';
                btn.style.color = 'var(--text-muted)';
                btn.style.cursor = 'not-allowed';
            }
        });
        document.getElementById('closeForm').addEventListener('submit', function(e) {
            if (input.value !== 'TUTUP') { e.preventDefault(); return; }
            if (!confirm('Yakin tutup buku periode ini?')) { e.preventDefault(); }
        });
    </script>
@endsection
