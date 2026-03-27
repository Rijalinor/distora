@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <!-- Active Period & Selector -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.25rem;">Dashboard</h1>
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <select onchange="window.location.href='{{ route('dashboard') }}?period_id=' + this.value" class="btn btn-ghost btn-sm" style="font-weight: 700; color: var(--accent-hover); background: var(--bg-card);">
                    @foreach($allPeriods as $p)
                        <option value="{{ $p->id }}" {{ $p->id === $activePeriod->id ? 'selected' : '' }}>
                            {{ $p->name }} {{ $p->status === 'closed' ? '(Closed)' : '' }}
                        </option>
                    @endforeach
                </select>
                @if($activePeriod->status === 'closed')
                    <span class="badge badge-warning" style="font-size: 0.7rem;"><span class="badge-dot"></span> Historical View</span>
                @else
                    <span class="badge badge-success" style="font-size: 0.7rem;"><span class="badge-dot"></span> Active Period</span>
                @endif
            </div>
        </div>
        @if(auth()->user()->role === 'admin')
        <div style="display: flex; gap: 0.5rem;">
            <a href="{{ route('reset.index') }}" class="btn btn-ghost btn-sm">⚙️ Kelola Periode</a>
            @if($activePeriod->status === 'active')
            <a href="{{ route('reset.index') }}?period_id={{ $activePeriod->id }}" class="btn btn-danger btn-sm" style="background: var(--warning-bg); color: var(--warning); border-color: rgba(245, 158, 11, 0.2);">📒 Tutup Buku</a>
            @endif
        </div>
        @endif
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Upload ({{ $activePeriod->name }})</div>
            <div class="stat-value">{{ number_format($stats['total_uploads']) }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Outlets Aktif</div>
            <div class="stat-value">{{ number_format($stats['outlets']) }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Products Terjual</div>
            <div class="stat-value">{{ number_format($stats['products']) }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Transaksi</div>
            <div class="stat-value">{{ number_format($stats['transactions']) }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Sales Logs</div>
            <div class="stat-value">{{ number_format($stats['sales']) }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Stok Records</div>
            <div class="stat-value">{{ number_format($stats['stocks']) }}</div>
        </div>
    </div>

    @if(auth()->user()->role === 'admin')
    <!-- Upload Zone -->
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1rem;">
        <h2 class="section-title" style="margin-bottom: 0;">📤 Upload File Excel</h2>
        @if($activePeriod->status === 'closed')
             <span style="color: var(--danger); font-size: 0.8rem; font-weight: 600;">⚠️ Periode ini sudah ditutup. Upload baru mungkin tidak disarankan.</span>
        @endif
    </div>

    <div style="margin-bottom: 1rem; display: none;">
        <select id="periodSelect">
            <option value="{{ $activePeriod->id }}" selected>{{ $activePeriod->name }}</option>
        </select>
    </div>

    <div class="upload-zone" id="uploadZone" onclick="document.getElementById('fileInput').click()">
        <div class="upload-zone-icon">📤</div>
        <div class="upload-zone-text">
            <strong>Klik untuk pilih file</strong> atau drag & drop ke sini<br>
            <small style="color: var(--text-muted)">Format: .xlsx, .xls, .csv — Maks 50MB</small>
        </div>
        <input type="file" id="fileInput" accept=".xlsx,.xls,.csv">
    </div>

    <div class="upload-progress" id="uploadProgress">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div class="spinner"></div>
                <span id="uploadFileName" style="font-weight: 500;"></span>
            </div>
            <span id="uploadStatus" class="badge badge-info"><span class="badge-dot"></span> Uploading</span>
        </div>
        <div class="progress-bar-bg">
            <div class="progress-bar-fill" id="progressBar"></div>
        </div>
    </div>

    <!-- Upload History -->
    <h2 class="section-title">📋 Riwayat Upload</h2>

    @if($histories->isEmpty())
        <div class="empty-state">
            <div class="empty-state-icon">📭</div>
            <p>Belum ada riwayat upload.</p>
        </div>
    @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>File</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Sukses</th>
                        <th>Gagal</th>
                        <th>Waktu</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($histories as $h)
                        <tr>
                            <td>{{ $h->id }}</td>
                            <td style="color: var(--text-primary); font-weight: 500; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                {{ $h->file_name }}
                            </td>
                            <td>
                                @switch($h->status)
                                    @case('success')
                                        <span class="badge badge-success"><span class="badge-dot"></span> Success</span>
                                        @break
                                    @case('partial')
                                        <span class="badge badge-warning"><span class="badge-dot"></span> Partial</span>
                                        @break
                                    @case('failed')
                                        <span class="badge badge-danger"><span class="badge-dot"></span> Failed</span>
                                        @break
                                    @case('processing')
                                        <span class="badge badge-info"><span class="badge-dot"></span> Processing</span>
                                        @break
                                    @default
                                        <span class="badge badge-pending"><span class="badge-dot"></span> Pending</span>
                                @endswitch
                            </td>
                            <td>{{ number_format($h->total_rows ?? 0) }}</td>
                            <td style="color: var(--success);">{{ number_format($h->success_rows ?? 0) }}</td>
                            <td style="color: {{ ($h->failed_rows ?? 0) > 0 ? 'var(--danger)' : 'var(--text-muted)' }};">
                                {{ number_format($h->failed_rows ?? 0) }}
                            </td>
                            <td>{{ $h->created_at->format('d M Y H:i') }}</td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <a href="{{ route('dashboard.show', $h->id) }}" class="btn btn-ghost btn-sm">Detail</a>
                                    @if(in_array($h->status, ['failed', 'partial']))
                                        <button class="btn btn-primary btn-sm" onclick="retryUpload({{ $h->id }})">Retry</button>
                                    @endif
                                    <button class="btn btn-sm" style="background: var(--danger, #ef4444); color: white; border: none; padding: 0.25rem 0.5rem; border-radius: 0.375rem; cursor: pointer;" onclick="deleteUpload({{ $h->id }})" title="Hapus">
                                        🗑️
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($histories->hasPages())
            <div class="pagination">
                {{ $histories->links('pagination::simple-default') }}
            </div>
        @endif
    @endif
    @endif
@endsection

@push('scripts')
<script>
    const uploadZone = document.getElementById('uploadZone');
    const fileInput = document.getElementById('fileInput');
    const uploadProgress = document.getElementById('uploadProgress');
    const progressBar = document.getElementById('progressBar');
    const uploadFileName = document.getElementById('uploadFileName');
    const uploadStatus = document.getElementById('uploadStatus');

    // Drag & Drop
    ['dragenter', 'dragover'].forEach(e => {
        uploadZone.addEventListener(e, (ev) => { ev.preventDefault(); uploadZone.classList.add('dragover'); });
    });
    ['dragleave', 'drop'].forEach(e => {
        uploadZone.addEventListener(e, (ev) => { ev.preventDefault(); uploadZone.classList.remove('dragover'); });
    });
    uploadZone.addEventListener('drop', (ev) => {
        const files = ev.dataTransfer.files;
        if (files.length > 0) uploadFile(files[0]);
    });

    fileInput.addEventListener('change', () => {
        if (fileInput.files.length > 0) uploadFile(fileInput.files[0]);
    });

    function uploadFile(file) {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('period_id', document.getElementById('periodSelect').value);

        uploadFileName.textContent = file.name;
        uploadProgress.classList.add('active');
        uploadZone.style.display = 'none';
        progressBar.style.width = '0%';

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/imports/sales');
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').content);

        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                const pct = Math.round((e.loaded / e.total) * 100);
                progressBar.style.width = pct + '%';
            }
        });

        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                const data = JSON.parse(xhr.responseText);
                uploadStatus.className = 'badge badge-success';
                uploadStatus.innerHTML = '<span class="badge-dot"></span> Uploaded';
                progressBar.style.width = '100%';
                showToast('File berhasil diupload! Import sedang diproses.', 'success');
                setTimeout(() => location.reload(), 2000);
            } else {
                uploadStatus.className = 'badge badge-danger';
                uploadStatus.innerHTML = '<span class="badge-dot"></span> Error';
                showToast('Upload gagal. Silakan coba lagi.', 'error');
                setTimeout(() => {
                    uploadProgress.classList.remove('active');
                    uploadZone.style.display = '';
                }, 3000);
            }
        };

        xhr.onerror = function() {
            uploadStatus.className = 'badge badge-danger';
            uploadStatus.innerHTML = '<span class="badge-dot"></span> Error';
            showToast('Koneksi gagal.', 'error');
        };

        xhr.send(formData);
    }

    function retryUpload(id) {
        if (!confirm('Retry import ini? Data lama akan dihapus dan diproses ulang.')) return;
        fetch('/imports/sales/' + id + '/retry', {
            method: 'POST',
            headers: { 
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(r => r.json())
        .then(data => {
            showToast(data.message || 'Berhasil di-retry.', 'success');
            setTimeout(() => location.reload(), 1500);
        })
        .catch(() => showToast('Retry gagal.', 'error'));
    }

    function deleteUpload(id) {
        if (!confirm('Hapus import ini? Semua data transaksi, penjualan, dan stok terkait akan dihapus secara permanen.')) return;
        fetch('/imports/sales/' + id, {
            method: 'DELETE',
            headers: { 
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(r => r.json())
        .then(data => {
            showToast(data.message || 'File berhasil dihapus.', 'success');
            setTimeout(() => location.reload(), 1500);
        })
        .catch(() => showToast('Gagal menghapus file.', 'error'));
    }
</script>
@endpush
