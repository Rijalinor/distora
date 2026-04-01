@extends('layouts.app')
@section('title', 'Target & KPI')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 700;">🎯 Target Penjualan (KPI)</h1>
            <p style="color: var(--text-muted); font-size: 0.95rem;">
                Periode Aktif: <strong style="color: var(--accent-hover);">{{ $period->name }}</strong>
            </p>
        </div>
        @if(auth()->user()->role === 'admin')
        <button onclick="document.getElementById('targetModal').style.display = 'flex'" class="btn btn-primary">
            + Tambah Target
        </button>
        @endif
    </div>
    <form method="GET" action="{{ route('targets.index') }}" style="display:flex; gap:0.75rem; align-items:center; background:var(--bg-card); padding:0.6rem 0.9rem; border:1px solid var(--border); border-radius:10px; margin-bottom:1rem; max-width:520px;">
        <label for="period_id" style="font-size:0.82rem; color:var(--text-muted);">Periode</label>
        <select name="period_id" id="period_id" onchange="this.form.submit()" style="padding:0.35rem; background:transparent; color:var(--text-primary); border:none; outline:none;">
            @foreach($allPeriods as $p)
                <option value="{{ $p->id }}" {{ $p->id === $period->id ? 'selected' : '' }}>{{ $p->name }}</option>
            @endforeach
        </select>
        <div style="width:1px; height:20px; background:var(--border);"></div>
        <label for="branch" style="font-size:0.82rem; color:var(--text-muted);">Wilayah</label>
        <select name="branch" id="branch" onchange="this.form.submit()" style="padding:0.35rem; background:transparent; color:var(--text-primary); border:none; outline:none;">
            <option value="all" {{ ($selectedBranch ?? 'all') === 'all' ? 'selected' : '' }}>Semua</option>
            <option value="OBM_01" {{ ($selectedBranch ?? 'all') === 'OBM_01' ? 'selected' : '' }}>Banjarmasin</option>
            <option value="OBM_02" {{ ($selectedBranch ?? 'all') === 'OBM_02' ? 'selected' : '' }}>Barabai</option>
            <option value="OBM_03" {{ ($selectedBranch ?? 'all') === 'OBM_03' ? 'selected' : '' }}>Batulicin</option>
        </select>
    </form>

    <!-- Tom Select CDN -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <style>
        .ts-control { background: #1f2937 !important; border: 1px solid #374151 !important; color: #f9fafb !important; border-radius: 6px !important; padding: 0.6rem !important; }
        .ts-wrapper.single .ts-control { background-color: #1f2937 !important; color: #f9fafb !important; border-radius: 6px !important;}
        .ts-dropdown, .ts-control, .ts-control input { color: #f9fafb !important; font-family: inherit; }
        .ts-dropdown { background: #111827 !important; border: 1px solid #374151 !important; border-radius: 6px !important; }
        .ts-dropdown .option { padding: 0.5rem 0.6rem !important; color: #f9fafb !important; }
        .ts-dropdown .active { background: #6366f1 !important; color: white !important; }
        .ts-wrapper.dropdown-active { border-radius: 6px !important; }
    </style>

    @if(auth()->user()->role === 'admin')
    <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; padding: 1rem; margin-bottom: 1.5rem;">
        <div style="font-weight: 700; margin-bottom: 0.75rem;">Alokasi Target Tim (By Principal)</div>
        <div style="display: grid; grid-template-columns: 1fr 180px auto auto; gap: 0.6rem; align-items: end; margin-bottom: 0.8rem;">
            <div>
                <label style="display:block; font-size:0.8rem; color:var(--text-muted); margin-bottom:0.3rem;">Principal</label>
                <select id="teamPrincipal" style="width:100%; padding:0.55rem; border-radius:6px; background:var(--bg-input); border:1px solid var(--border); color:var(--text-primary);">
                    <option value="">Pilih Principal...</option>
                    @foreach($principleNames as $name)
                        <option value="{{ $name }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="display:block; font-size:0.8rem; color:var(--text-muted); margin-bottom:0.3rem;">Target Tim (Rp)</label>
                <input id="teamTarget" type="text" value="1.000.000.000" style="width:100%; padding:0.55rem; border-radius:6px; background:var(--bg-input); border:1px solid var(--border); color:var(--text-primary);" oninput="formatRupiahOnly(this)">
            </div>
            <button type="button" class="btn btn-ghost" onclick="previewTeamAllocation()">Preview Bagi</button>
            <form id="applyTeamForm" action="{{ route('targets.team-allocation-apply') }}" method="POST" style="display:inline;">
                @csrf
                <input type="hidden" name="principal_name" id="applyPrincipalName">
                <input type="hidden" name="team_target" id="applyTeamTarget">
                <input type="hidden" name="period_id" value="{{ $period->id }}">
                <input type="hidden" name="branch" value="{{ $selectedBranch ?? 'all' }}">
                <button type="submit" class="btn" onclick="return prepareApplyTeamAllocation()">Terapkan</button>
            </form>
        </div>
        <div id="teamAllocationInfo" style="font-size:0.8rem; color:var(--text-muted); margin-bottom:0.6rem;">Pilih principal dan target tim, lalu klik preview untuk melihat pembagian proporsional 3 bulan terakhir.</div>
        <div id="teamAllocationTableWrap" style="display:none; max-height:260px; overflow:auto; border:1px solid var(--border); border-radius:8px;">
            <table style="margin:0;">
                <thead>
                    <tr>
                        <th>Sales</th>
                        <th style="text-align:right;">Histori 3 Bulan</th>
                        <th style="text-align:right;">Kontribusi</th>
                        <th style="text-align:right;">Alokasi Target</th>
                    </tr>
                </thead>
                <tbody id="teamAllocationBody"></tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Modals to add target -->
    <div id="targetModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 100; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div style="background: var(--bg-card); width: 100%; max-width: 450px; border-radius: 12px; padding: 1.5rem; border: 1px solid var(--border); box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
            <div style="display: flex; justify-content: space-between; margin-bottom: 1.5rem;">
                <h3 style="font-size: 1.1rem; font-weight: 600;">Set Target Baru</h3>
                <button onclick="document.getElementById('targetModal').style.display = 'none'" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.25rem;">&times;</button>
            </div>
            
            <form action="{{ route('targets.store') }}" method="POST">
                @csrf
                <input type="hidden" name="period_id" value="{{ $period->id }}">
                <input type="hidden" name="branch" value="{{ $selectedBranch ?? 'all' }}">
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 0.5rem;">Tipe Target</label>
	                    <select id="typeSelect" name="type" style="width: 100%; padding: 0.6rem; border-radius: 6px; background: var(--bg-input); border: 1px solid var(--border); color: var(--text-primary);" onchange="toggleNameSelect()">
	                        <option value="salesman">Salesman</option>
	                        <option value="principle">Principle (Brand)</option>
	                        <option value="outlet">Toko</option>
	                    </select>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 0.5rem;">Nama</label>
                    
                    <!-- Salesman Select -->
                    <div id="wrapSalesman">
                        <select id="nameSalesman" name="name" placeholder="Ketik untuk mencari salesman...">
                            <option value="">Cari Salesman...</option>
                            @foreach($salesmanNames as $name)
                                <option value="{{ $name }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Principle Select -->
	                    <div id="wrapPrinciple" style="display: none;">
	                        <select id="namePrinciple" name="name_alt" disabled placeholder="Ketik untuk mencari principle...">
	                            <option value="">Cari Principle...</option>
	                            @foreach($principleNames as $name)
	                                <option value="{{ $name }}">{{ $name }}</option>
	                            @endforeach
	                        </select>
	                    </div>
	                    <div id="wrapOutlet" style="display: none;">
	                        <select id="nameOutlet" name="name_alt2" disabled placeholder="Ketik untuk mencari toko...">
	                            <option value="">Cari Toko...</option>
	                            @foreach($outletNames as $name)
	                                <option value="{{ $name }}">{{ $name }}</option>
	                            @endforeach
	                        </select>
	                    </div>
	                    <div id="wrapOutletPrincipal" style="display: none; margin-top: 0.75rem;">
	                        <select id="outletPrincipal" name="principal_name_alt" disabled placeholder="Pilih principal untuk target toko...">
	                            <option value="">Pilih Principal...</option>
	                            @foreach($principleNames as $name)
	                                <option value="{{ $name }}">{{ $name }}</option>
	                            @endforeach
	                        </select>
	                    </div>
	                </div>
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 0.5rem;">Kenaikan dari Rata-rata 3 Bulan (%)</label>
                    <div style="display: flex; gap: 0.6rem; align-items: center;">
                        <input type="number" id="growth_percent" value="10" step="0.1" style="width: 120px; padding: 0.6rem; border-radius: 6px; background: var(--bg-input); border: 1px solid var(--border); color: var(--text-primary);">
                        <button type="button" class="btn btn-ghost" onclick="calculateAutoTarget()">Hitung Otomatis</button>
                    </div>
                    <div id="auto_target_info" style="margin-top: 0.5rem; font-size: 0.78rem; color: var(--text-muted);">
                        Target = Rata-rata 3 bulan sebelumnya + persentase kenaikan.
                    </div>
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 0.5rem;">Target Nominal (Rp)</label>
                    <input type="text" id="target_amount_display" required placeholder="Contoh: 150.000.000" style="width: 100%; padding: 0.6rem; border-radius: 6px; background: var(--bg-input); border: 1px solid var(--border); color: var(--text-primary);" oninput="formatRupiah(this)">
                    <input type="hidden" id="target_amount_real" name="target_amount">
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                    <button type="button" class="btn btn-ghost" onclick="document.getElementById('targetModal').style.display = 'none'">Batal</button>
                    <button type="submit" class="btn">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Targets Content -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem;">
        
        <!-- Salesman Targets -->
        <div>
            <h2 class="section-title">📊 Performa Salesman</h2>
            <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem;">
                
                @if($salesmanTargets->isEmpty())
                    <div style="text-align: center; color: var(--text-muted); padding: 2rem 0; font-size: 0.9rem;">
                        Belum ada target salesman di bulan ini.
                    </div>
                @endif

                @foreach($salesmanTargets as $row)
                    @php 
                        $color = $row->raw_progress >= 100 ? 'var(--success)' : ($row->raw_progress >= 70 ? 'var(--warning)' : 'var(--danger)');
                    @endphp
                    <div style="margin-bottom: 1.25rem;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 0.4rem;">
                            <div>
                                <div style="font-weight: 600; font-size: 0.95rem;">{{ $row->name }}</div>
                                @if(!empty($row->principal_name))
                                <div style="font-size: 0.78rem; color: var(--accent-hover); margin-top: 2px;">
                                    Principal: {{ $row->principal_name }}
                                </div>
                                @endif
                                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">
                                    Aktual: Rp {{ number_format($row->actual, 0, ',', '.') }} / 
                                    Target: Rp {{ number_format($row->target, 0, ',', '.') }}
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 1.1rem; font-weight: 700; color: {{ $color }};">
                                    {{ $row->raw_progress }}%
                                </div>
                                @if(auth()->user()->role === 'admin')
                                <form action="{{ route('targets.destroy', $row->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Hapus KPI ini?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" style="background:none; border:none; color: var(--text-muted); cursor: pointer; font-size: 0.7rem; text-decoration: underline; padding: 0;">hapus</button>
                                </form>
                                @endif
                            </div>
                        </div>
                        <div style="height: 10px; background: rgba(255,255,255,0.05); border-radius: 5px; overflow: hidden;">
                            <div style="width: {{ $row->progress }}%; height: 100%; background: {{ $color }}; border-radius: 5px; transition: width 0.5s ease;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

	        <!-- Principle Targets -->
	        <div>
            <h2 class="section-title">🏢 Performa Principle</h2>
            <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem;">
                
                @if($principleTargets->isEmpty())
                    <div style="text-align: center; color: var(--text-muted); padding: 2rem 0; font-size: 0.9rem;">
                        Belum ada target principle di bulan ini.
                    </div>
                @endif

                @foreach($principleTargets as $row)
                    @php 
                        $color = $row->raw_progress >= 100 ? 'var(--success)' : ($row->raw_progress >= 70 ? 'var(--warning)' : 'var(--danger)');
                    @endphp
                    <div style="margin-bottom: 1.25rem;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 0.4rem;">
                            <div>
                                <div style="font-weight: 600; font-size: 0.95rem;">{{ $row->name }}</div>
                                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">
                                    Aktual: Rp {{ number_format($row->actual, 0, ',', '.') }} / 
                                    Target: Rp {{ number_format($row->target, 0, ',', '.') }}
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 1.1rem; font-weight: 700; color: {{ $color }};">
                                    {{ $row->raw_progress }}%
                                </div>
                                @if(auth()->user()->role === 'admin')
                                <form action="{{ route('targets.destroy', $row->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Hapus KPI ini?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" style="background:none; border:none; color: var(--text-muted); cursor: pointer; font-size: 0.7rem; text-decoration: underline; padding: 0;">hapus</button>
                                </form>
                                @endif
                            </div>
                        </div>
                        <div style="height: 10px; background: rgba(255,255,255,0.05); border-radius: 5px; overflow: hidden;">
                            <div style="width: {{ $row->progress }}%; height: 100%; background: {{ $color }}; border-radius: 5px; transition: width 0.5s ease;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
	        </div>
	        <div>
	            <h2 class="section-title">Performa Toko</h2>
	            <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem;">
	                @if($outletTargets->isEmpty())
	                    <div style="text-align: center; color: var(--text-muted); padding: 2rem 0; font-size: 0.9rem;">
	                        Belum ada target toko di bulan ini.
	                    </div>
	                @endif

	                @foreach($outletTargets as $row)
	                    @php 
	                        $color = $row->raw_progress >= 100 ? 'var(--success)' : ($row->raw_progress >= 70 ? 'var(--warning)' : 'var(--danger)');
	                    @endphp
	                    <div style="margin-bottom: 1.25rem;">
	                        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 0.4rem;">
	                            <div>
	                                <div style="font-weight: 600; font-size: 0.95rem;">{{ $row->name }}</div>
	                                @if(!empty($row->principal_name))
	                                <div style="font-size: 0.78rem; color: var(--accent-hover); margin-top: 2px;">
	                                    Principal: {{ $row->principal_name }}
	                                </div>
	                                @endif
	                                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">
	                                    Aktual: Rp {{ number_format($row->actual, 0, ',', '.') }} / 
	                                    Target: Rp {{ number_format($row->target, 0, ',', '.') }}
	                                </div>
	                            </div>
	                            <div style="text-align: right;">
	                                <div style="font-size: 1.1rem; font-weight: 700; color: {{ $color }};">
	                                    {{ $row->raw_progress }}%
	                                </div>
	                                @if(auth()->user()->role === 'admin')
	                                <form action="{{ route('targets.destroy', $row->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Hapus KPI ini?');">
	                                    @csrf @method('DELETE')
	                                    <button type="submit" style="background:none; border:none; color: var(--text-muted); cursor: pointer; font-size: 0.7rem; text-decoration: underline; padding: 0;">hapus</button>
	                                </form>
	                                @endif
	                            </div>
	                        </div>
	                        <div style="height: 10px; background: rgba(255,255,255,0.05); border-radius: 5px; overflow: hidden;">
	                            <div style="width: {{ $row->progress }}%; height: 100%; background: {{ $color }}; border-radius: 5px; transition: width 0.5s ease;"></div>
	                        </div>
	                    </div>
	                @endforeach
	            </div>
	        </div>
	        
	    </div>

    <!-- Script to toggle dropdowns based on type -->
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script>
        // Initialize TomSelect and store instances
        const tsSalesman = new TomSelect("#nameSalesman", {
            create: true,
            persist: false,
            maxOptions: 100,
            createOnBlur: true
        });
	        const tsPrinciple = new TomSelect("#namePrinciple", {
	            create: true,
	            persist: false,
	            maxOptions: 100,
	            createOnBlur: true
	        });
	        const tsOutlet = new TomSelect("#nameOutlet", {
	            create: true,
	            persist: false,
	            maxOptions: 100,
	            createOnBlur: true
	        });
	        const tsOutletPrincipal = new TomSelect("#outletPrincipal", {
	            create: false,
	            maxOptions: 100
	        });

        function toggleNameSelect() {
	            const type = document.getElementById('typeSelect').value;
	            const wrapSalesman = document.getElementById('wrapSalesman');
	            const wrapPrinciple = document.getElementById('wrapPrinciple');
	            const wrapOutlet = document.getElementById('wrapOutlet');
	            const wrapOutletPrincipal = document.getElementById('wrapOutletPrincipal');
	            const selSalesman = document.getElementById('nameSalesman');
	            const selPrinciple = document.getElementById('namePrinciple');
	            const selOutlet = document.getElementById('nameOutlet');
	            const selOutletPrincipal = document.getElementById('outletPrincipal');

            if (type === 'salesman') {
                wrapSalesman.style.display = 'block';
                selSalesman.name = 'name';
                tsSalesman.enable();

	                wrapPrinciple.style.display = 'none';
	                selPrinciple.name = 'name_alt';
	                tsPrinciple.disable();

	                wrapOutlet.style.display = 'none';
	                selOutlet.name = 'name_alt2';
	                tsOutlet.disable();
	                wrapOutletPrincipal.style.display = 'none';
	                selOutletPrincipal.name = 'principal_name_alt';
	                tsOutletPrincipal.disable();
	            } else if (type === 'principle') {
	                wrapPrinciple.style.display = 'block';
	                selPrinciple.name = 'name';
	                tsPrinciple.enable();

	                wrapSalesman.style.display = 'none';
	                selSalesman.name = 'name_alt';
	                tsSalesman.disable();

	                wrapOutlet.style.display = 'none';
	                selOutlet.name = 'name_alt2';
	                tsOutlet.disable();
	                wrapOutletPrincipal.style.display = 'none';
	                selOutletPrincipal.name = 'principal_name_alt';
	                tsOutletPrincipal.disable();
	            } else {
	                wrapOutlet.style.display = 'block';
	                selOutlet.name = 'name';
	                tsOutlet.enable();
	                wrapOutletPrincipal.style.display = 'block';
	                selOutletPrincipal.name = 'principal_name';
	                tsOutletPrincipal.enable();

	                wrapSalesman.style.display = 'none';
	                selSalesman.name = 'name_alt';
	                tsSalesman.disable();

	                wrapPrinciple.style.display = 'none';
	                selPrinciple.name = 'name_alt2';
	                tsPrinciple.disable();
	            }
	        }

        function formatRupiah(input) {
            let number_string = input.value.replace(/[^,\d]/g, '').toString(),
                split = number_string.split(','),
                sisa = split[0].length % 3,
                rupiah = split[0].substr(0, sisa),
                ribuan = split[0].substr(sisa).match(/\d{3}/gi);

            if (ribuan) {
                separator = sisa ? '.' : '';
                rupiah += separator + ribuan.join('.');
            }

            rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
            input.value = rupiah;
            
            // Set real value to hidden input
            document.getElementById('target_amount_real').value = number_string;
        }

        function formatRupiahOnly(input) {
            let number_string = input.value.replace(/[^,\d]/g, '').toString(),
                split = number_string.split(','),
                sisa = split[0].length % 3,
                rupiah = split[0].substr(0, sisa),
                ribuan = split[0].substr(sisa).match(/\d{3}/gi);

            if (ribuan) {
                const separator = sisa ? '.' : '';
                rupiah += separator + ribuan.join('.');
            }

            input.value = split[1] !== undefined ? rupiah + ',' + split[1] : rupiah;
        }

        function getRawNumericValue(inputId) {
            const val = (document.getElementById(inputId)?.value || '').toString();
            return Number(val.replace(/[^\d]/g, '')) || 0;
        }

        function formatRupiahNumber(number) {
            const rounded = Math.round(Number(number || 0));
            return rounded.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

	        function getSelectedName() {
	            const type = document.getElementById('typeSelect').value;
	            if (type === 'salesman') return tsSalesman.getValue();
	            if (type === 'principle') return tsPrinciple.getValue();
	            return tsOutlet.getValue();
	        }

        async function calculateAutoTarget() {
	            const type = document.getElementById('typeSelect').value;
	            const name = getSelectedName();
	            const principalName = tsOutletPrincipal.getValue();
	            const growthPct = document.getElementById('growth_percent').value;
	            const info = document.getElementById('auto_target_info');

	            if (!name) {
	                info.innerText = 'Pilih nama terlebih dahulu.';
	                info.style.color = 'var(--danger)';
	                return;
	            }

	            if (type === 'outlet' && !principalName) {
	                info.innerText = 'Untuk target toko, principal wajib dipilih.';
	                info.style.color = 'var(--danger)';
	                return;
	            }

            try {
                info.innerText = 'Menghitung target...';
                info.style.color = 'var(--text-muted)';

	                const url = `{{ route('targets.suggest') }}?period_id={{ $period->id }}&branch={{ $selectedBranch ?? 'all' }}&type=${encodeURIComponent(type)}&name=${encodeURIComponent(name)}&principal_name=${encodeURIComponent(principalName)}&growth_pct=${encodeURIComponent(growthPct)}`;
                const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                if (!res.ok) {
                    throw new Error('Gagal menghitung target otomatis');
                }

                const data = await res.json();
                const suggested = Math.round(Number(data.suggested_target || 0));

                document.getElementById('target_amount_real').value = suggested;
                document.getElementById('target_amount_display').value = formatRupiahNumber(suggested);

                info.innerText = `Rata-rata 3 bulan: Rp ${formatRupiahNumber(data.average_last_3_months)} | Kenaikan: ${data.growth_pct}% | Target: Rp ${formatRupiahNumber(suggested)}`;
                info.style.color = 'var(--success)';
            } catch (e) {
                info.innerText = e.message || 'Terjadi kesalahan saat menghitung target.';
                info.style.color = 'var(--danger)';
            }
        }

        function formatNumberId(value) {
            return Number(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });
        }

        async function previewTeamAllocation() {
            const principal = (document.getElementById('teamPrincipal').value || '').trim();
            const teamTarget = getRawNumericValue('teamTarget');
            const info = document.getElementById('teamAllocationInfo');
            const body = document.getElementById('teamAllocationBody');
            const wrap = document.getElementById('teamAllocationTableWrap');

            if (!principal) {
                info.innerText = 'Principal harus dipilih.';
                info.style.color = 'var(--danger)';
                return;
            }
            if (teamTarget <= 0) {
                info.innerText = 'Target tim harus lebih dari 0.';
                info.style.color = 'var(--danger)';
                return;
            }

            try {
                info.innerText = 'Memproses pembagian target tim...';
                info.style.color = 'var(--text-muted)';
	                const url = `{{ route('targets.team-allocation-preview') }}?period_id={{ $period->id }}&branch={{ $selectedBranch ?? 'all' }}&principal_name=${encodeURIComponent(principal)}&team_target=${encodeURIComponent(teamTarget)}`;
                const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                if (!res.ok) throw new Error('Gagal mengambil preview alokasi');
                const data = await res.json();

                body.innerHTML = '';
                if (!data.rows || data.rows.length === 0) {
                    wrap.style.display = 'none';
                    info.innerText = 'Tidak ada data sales untuk principal ini di 3 bulan terakhir.';
                    info.style.color = 'var(--warning)';
                    return;
                }

                data.rows.forEach((row) => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${row.salesman_name}</td>
                        <td style="text-align:right;">Rp ${formatNumberId(row.historical_amount)}</td>
                        <td style="text-align:right;">${Number(row.contribution_pct).toFixed(2)}%</td>
                        <td style="text-align:right; font-weight:700;">Rp ${formatNumberId(row.allocated_target)}</td>
                    `;
                    body.appendChild(tr);
                });
                wrap.style.display = 'block';
                info.innerText = `Total histori: Rp ${formatNumberId(data.summary.total_historical)} | Sales terlibat: ${data.summary.sales_count}`;
                info.style.color = 'var(--success)';
            } catch (e) {
                wrap.style.display = 'none';
                info.innerText = e.message || 'Gagal memproses preview alokasi.';
                info.style.color = 'var(--danger)';
            }
        }

        function prepareApplyTeamAllocation() {
            const principal = (document.getElementById('teamPrincipal').value || '').trim();
            const teamTarget = getRawNumericValue('teamTarget');
            if (!principal || teamTarget <= 0) {
                alert('Pilih principal dan isi target tim terlebih dahulu.');
                return false;
            }
            document.getElementById('applyPrincipalName').value = principal;
            document.getElementById('applyTeamTarget').value = teamTarget;
            return confirm('Terapkan pembagian target ini ke KPI salesman?');
        }

	        toggleNameSelect();
	    </script>
@endsection
