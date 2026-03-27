@extends('layouts.app')

@section('title', 'Detail Upload #' . $uploadHistory->id)

@section('content')
    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="{{ route('dashboard') }}">Dashboard</a>
        <span class="sep">›</span>
        <span class="current">Upload #{{ $uploadHistory->id }}</span>
    </div>

    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.25rem;">{{ $uploadHistory->file_name }}</h1>
            <span style="color: var(--text-muted); font-size: 0.875rem;">
                Uploaded {{ $uploadHistory->created_at->format('d M Y H:i') }}
                @if($uploadHistory->finished_at)
                    · Selesai {{ $uploadHistory->finished_at->format('H:i') }}
                @endif
            </span>
        </div>
        <div style="display: flex; gap: 0.5rem; align-items: center;">
            @switch($uploadHistory->status)
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

            @if(in_array($uploadHistory->status, ['failed', 'partial']))
                <button class="btn btn-primary btn-sm" onclick="retryUpload({{ $uploadHistory->id }})">↻ Retry</button>
            @endif
            <button class="btn btn-danger btn-sm" onclick="deleteUpload({{ $uploadHistory->id }})">🗑 Hapus</button>
        </div>
    </div>

    <!-- Detail Cards -->
    <div class="detail-grid">
        <div class="detail-card">
            <div class="detail-card-title">📊 Ringkasan Import</div>
            <div class="detail-row">
                <span class="detail-label">Total Rows</span>
                <span class="detail-value">{{ number_format($uploadHistory->total_rows ?? 0) }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Sukses</span>
                <span class="detail-value" style="color: var(--success);">{{ number_format($uploadHistory->success_rows ?? 0) }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Gagal</span>
                <span class="detail-value" style="color: {{ ($uploadHistory->failed_rows ?? 0) > 0 ? 'var(--danger)' : 'var(--text-muted)' }};">{{ number_format($uploadHistory->failed_rows ?? 0) }}</span>
            </div>
        </div>

        <div class="detail-card">
            <div class="detail-card-title">🗄️ Data Tersimpan</div>
            <div class="detail-row">
                <span class="detail-label">Transaksi</span>
                <span class="detail-value">{{ number_format($summary['transactions']) }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Sales</span>
                <span class="detail-value">{{ number_format($summary['sales']) }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Stok</span>
                <span class="detail-value">{{ number_format($summary['stocks']) }}</span>
            </div>
        </div>

        <div class="detail-card">
            <div class="detail-card-title">ℹ️ Info File</div>
            <div class="detail-row">
                <span class="detail-label">Nama File</span>
                <span class="detail-value" style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $uploadHistory->file_name }}">{{ $uploadHistory->file_name }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Mulai</span>
                <span class="detail-value">{{ $uploadHistory->started_at?->format('H:i:s') ?? '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Selesai</span>
                <span class="detail-value">{{ $uploadHistory->finished_at?->format('H:i:s') ?? '-' }}</span>
            </div>
        </div>
    </div>

    @if($uploadHistory->errors_summary)
        <div style="background: var(--danger-bg); border: 1px solid rgba(239,68,68,0.2); border-radius: 12px; padding: 1rem 1.25rem; margin-bottom: 2rem; color: var(--danger); font-size: 0.875rem;">
            <strong>Error:</strong> {{ $uploadHistory->errors_summary }}
        </div>
    @endif

    <!-- Error Logs -->
    <h2 class="section-title">⚠️ Error Logs</h2>

    @if($logs->isEmpty())
        <div class="empty-state" style="padding: 2rem;">
            <p style="color: var(--success);">✓ Tidak ada error.</p>
        </div>
    @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Row</th>
                        <th>Level</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                        <tr>
                            <td>{{ $log->row_number }}</td>
                            <td>
                                <span class="badge badge-{{ $log->level === 'error' ? 'danger' : 'warning' }}">
                                    {{ $log->level }}
                                </span>
                            </td>
                            <td style="max-width: 500px; word-break: break-word;">{{ $log->message }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="pagination">
                {{ $logs->links('pagination::simple-default') }}
            </div>
        @endif
    @endif
@endsection

@push('scripts')
<script>
    function retryUpload(id) {
        if (!confirm('Retry import ini? Data lama akan dihapus dan diproses ulang.')) return;
        fetch('/imports/sales/' + id + '/retry', { method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(r => r.json())
        .then(data => { showToast(data.message, 'success'); setTimeout(() => location.reload(), 1500); })
        .catch(() => showToast('Retry gagal.', 'error'));
    }

    function deleteUpload(id) {
        if (!confirm('Hapus upload ini dan semua data terkait? Aksi ini tidak bisa dibatalkan.')) return;
        fetch('/imports/sales/' + id, { method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(r => r.json())
        .then(data => { showToast(data.message, 'success'); setTimeout(() => window.location.href = '{{ route("dashboard") }}', 1500); })
        .catch(() => showToast('Hapus gagal.', 'error'));
    }
</script>
@endpush
