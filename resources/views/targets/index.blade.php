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

    <!-- Modals to add target -->
    <div id="targetModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 100; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div style="background: var(--bg-card); width: 100%; max-width: 450px; border-radius: 12px; padding: 1.5rem; border: 1px solid var(--border); box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
            <div style="display: flex; justify-content: space-between; margin-bottom: 1.5rem;">
                <h3 style="font-size: 1.1rem; font-weight: 600;">Set Target Baru</h3>
                <button onclick="document.getElementById('targetModal').style.display = 'none'" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.25rem;">&times;</button>
            </div>
            
            <form action="{{ route('targets.store') }}" method="POST">
                @csrf
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 0.5rem;">Tipe Target</label>
                    <select id="typeSelect" name="type" style="width: 100%; padding: 0.6rem; border-radius: 6px; background: var(--bg-input); border: 1px solid var(--border); color: var(--text-primary);" onchange="toggleNameSelect()">
                        <option value="salesman">Salesman</option>
                        <option value="principle">Principle (Brand)</option>
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
        
    </div>

    <!-- Script to toggle dropdowns based on type -->
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script>
        // Initialize TomSelect and store instances
        const tsSalesman = new TomSelect("#nameSalesman", { create: false, maxOptions: 50 });
        const tsPrinciple = new TomSelect("#namePrinciple", { create: false, maxOptions: 50 });

        function toggleNameSelect() {
            const type = document.getElementById('typeSelect').value;
            const wrapSalesman = document.getElementById('wrapSalesman');
            const wrapPrinciple = document.getElementById('wrapPrinciple');
            const selSalesman = document.getElementById('nameSalesman');
            const selPrinciple = document.getElementById('namePrinciple');

            if (type === 'salesman') {
                wrapSalesman.style.display = 'block';
                selSalesman.name = 'name';
                tsSalesman.enable();

                wrapPrinciple.style.display = 'none';
                selPrinciple.name = 'name_alt';
                tsPrinciple.disable();
            } else {
                wrapPrinciple.style.display = 'block';
                selPrinciple.name = 'name';
                tsPrinciple.enable();

                wrapSalesman.style.display = 'none';
                selSalesman.name = 'name_alt';
                tsSalesman.disable();
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
    </script>
@endsection
