    <form method="GET" action="{{ route('insights.index') }}" style="display: flex; gap: 0.75rem; align-items: center; background: var(--bg-card); padding: 0.5rem 1rem; border-radius: 12px; border: 1px solid var(--border);">
        <label for="period_id" style="font-size: 0.85rem; color: var(--text-secondary); font-weight: 600;">Periode:</label>
        <select name="period_id" id="period_id" onchange="this.form.submit()" style="padding: 0.4rem; border: none; background: transparent; color: var(--accent-hover); font-weight: 800; outline: none; cursor: pointer;">
            @foreach($allPeriods as $p)
                <option value="{{ $p->id }}" {{ $p->id === $activePeriod->id ? 'selected' : '' }}>
                    {{ $p->name }} {{ $p->status === 'closed' ? '(Closed)' : '' }}
                </option>
            @endforeach
        </select>

        <div style="width: 1px; height: 20px; background: var(--border);"></div>

        <label for="branch" style="font-size: 0.85rem; color: var(--text-secondary); font-weight: 600;">Wilayah:</label>
        <select name="branch" id="branch" onchange="this.form.submit()" style="padding: 0.4rem; border: none; background: transparent; color: var(--text-primary); font-weight: 700; outline: none; cursor: pointer;">
            <option value="all" {{ $data['selected_branch'] === 'all' ? 'selected' : '' }}>Semua Cabang</option>
            <option value="OBM_01" {{ $data['selected_branch'] === 'OBM_01' ? 'selected' : '' }}>Banjarmasin (OBM_01)</option>
            <option value="OBM_02" {{ $data['selected_branch'] === 'OBM_02' ? 'selected' : '' }}>Barabai (OBM_02)</option>
            <option value="OBM_03" {{ $data['selected_branch'] === 'OBM_03' ? 'selected' : '' }}>Batulicin (OBM_03)</option>
        </select>
    </form>
</div>