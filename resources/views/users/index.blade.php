@extends('layouts.app')
@section('title', 'Kelola User')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 700;">👥 Kelola User</h1>
            <p style="color: var(--text-muted); font-size: 0.875rem;">Kelola akun Admin dan Salesman</p>
        </div>
        <button onclick="openAddModal()" class="btn btn-primary">+ Tambah User</button>
    </div>

    <!-- Users Table -->
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Linked Salesman</th>
                    <th>Dibuat</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $i => $user)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td style="color: var(--text-primary); font-weight: 600;">{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            @if($user->role === 'admin')
                                <span class="badge badge-info"><span class="badge-dot"></span> ADMIN</span>
                            @else
                                <span class="badge badge-success"><span class="badge-dot"></span> SALESMAN</span>
                            @endif
                        </td>
                        <td style="color: var(--accent-hover); font-size: 0.85rem;">{{ $user->linked_salesman_name ?? '-' }}</td>
                        <td>{{ $user->created_at->format('d M Y') }}</td>
                        <td>
                            <div style="display: flex; gap: 0.5rem;">
                                <button class="btn btn-ghost btn-sm" onclick='openEditModal(@json($user))'>Edit</button>
                                @if($user->id !== auth()->id())
                                    <form action="{{ route('users.destroy', $user) }}" method="POST" onsubmit="return confirm('Hapus user {{ $user->name }}?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Add User Modal -->
    <div id="addModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 100; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div style="background: var(--bg-card); width: 100%; max-width: 480px; border-radius: 12px; padding: 1.5rem; border: 1px solid var(--border); box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
            <div style="display: flex; justify-content: space-between; margin-bottom: 1.5rem;">
                <h3 style="font-size: 1.1rem; font-weight: 600;">Tambah User Baru</h3>
                <button onclick="closeModal('addModal')" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.25rem;">&times;</button>
            </div>

            <form action="{{ route('users.store') }}" method="POST">
                @csrf
                <div style="display: grid; gap: 1rem;">
                    <div>
                        <label style="display: block; font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 0.4rem;">Nama Lengkap</label>
                        <input type="text" name="name" required placeholder="Contoh: Ahmad Fauzi" style="width: 100%; padding: 0.6rem; border-radius: 6px; background: var(--bg-input); border: 1px solid var(--border); color: var(--text-primary);">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 0.4rem;">Email</label>
                        <input type="email" name="email" required placeholder="email@distora.com" style="width: 100%; padding: 0.6rem; border-radius: 6px; background: var(--bg-input); border: 1px solid var(--border); color: var(--text-primary);">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 0.4rem;">Password</label>
                        <input type="password" name="password" required minlength="6" placeholder="Min. 6 karakter" style="width: 100%; padding: 0.6rem; border-radius: 6px; background: var(--bg-input); border: 1px solid var(--border); color: var(--text-primary);">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 0.4rem;">Role</label>
                        <select name="role" id="addRole" onchange="toggleLinkedField('add')" style="width: 100%; padding: 0.6rem; border-radius: 6px; background: var(--bg-input); border: 1px solid var(--border); color: var(--text-primary);">
                            <option value="salesman">Salesman</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div id="addLinkedField">
                        <label style="display: block; font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 0.4rem;">Nama Salesman (di Data Excel)</label>
                        <select name="linked_salesman_name" id="addLinkedSelect" style="width: 100%; padding: 0.6rem; border-radius: 6px; background: var(--bg-input); border: 1px solid var(--border); color: var(--text-primary);">
                            <option value="">-- Pilih Salesman --</option>
                            @foreach($salesmanNames as $name)
                                <option value="{{ $name }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        <small style="color: var(--text-muted); font-size: 0.75rem;">Hubungkan dengan nama salesman di data Excel agar data otomatis terfilter.</small>
                    </div>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-ghost" onclick="closeModal('addModal')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 100; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div style="background: var(--bg-card); width: 100%; max-width: 480px; border-radius: 12px; padding: 1.5rem; border: 1px solid var(--border); box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
            <div style="display: flex; justify-content: space-between; margin-bottom: 1.5rem;">
                <h3 style="font-size: 1.1rem; font-weight: 600;">Edit User</h3>
                <button onclick="closeModal('editModal')" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.25rem;">&times;</button>
            </div>

            <form id="editForm" method="POST">
                @csrf @method('PUT')
                <div style="display: grid; gap: 1rem;">
                    <div>
                        <label style="display: block; font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 0.4rem;">Nama Lengkap</label>
                        <input type="text" name="name" id="editName" required style="width: 100%; padding: 0.6rem; border-radius: 6px; background: var(--bg-input); border: 1px solid var(--border); color: var(--text-primary);">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 0.4rem;">Email</label>
                        <input type="email" name="email" id="editEmail" required style="width: 100%; padding: 0.6rem; border-radius: 6px; background: var(--bg-input); border: 1px solid var(--border); color: var(--text-primary);">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 0.4rem;">Password <small style="color: var(--text-muted);">(kosongkan jika tidak ingin ganti)</small></label>
                        <input type="password" name="password" minlength="6" placeholder="Biarkan kosong jika tidak ganti" style="width: 100%; padding: 0.6rem; border-radius: 6px; background: var(--bg-input); border: 1px solid var(--border); color: var(--text-primary);">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 0.4rem;">Role</label>
                        <select name="role" id="editRole" onchange="toggleLinkedField('edit')" style="width: 100%; padding: 0.6rem; border-radius: 6px; background: var(--bg-input); border: 1px solid var(--border); color: var(--text-primary);">
                            <option value="salesman">Salesman</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div id="editLinkedField">
                        <label style="display: block; font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 0.4rem;">Nama Salesman (di Data Excel)</label>
                        <select name="linked_salesman_name" id="editLinkedSelect" style="width: 100%; padding: 0.6rem; border-radius: 6px; background: var(--bg-input); border: 1px solid var(--border); color: var(--text-primary);">
                            <option value="">-- Pilih Salesman --</option>
                            @foreach($salesmanNames as $name)
                                <option value="{{ $name }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-ghost" onclick="closeModal('editModal')">Batal</button>
                    <button type="submit" class="btn btn-primary">Perbarui</button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    function openAddModal() {
        document.getElementById('addModal').style.display = 'flex';
    }

    function openEditModal(user) {
        document.getElementById('editForm').action = '/users/' + user.id;
        document.getElementById('editName').value = user.name;
        document.getElementById('editEmail').value = user.email;
        document.getElementById('editRole').value = user.role;

        const linkedSelect = document.getElementById('editLinkedSelect');
        linkedSelect.value = user.linked_salesman_name || '';

        toggleLinkedField('edit');
        document.getElementById('editModal').style.display = 'flex';
    }

    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }

    function toggleLinkedField(prefix) {
        const role = document.getElementById(prefix + 'Role').value;
        const field = document.getElementById(prefix + 'LinkedField');
        field.style.display = role === 'salesman' ? 'block' : 'none';
    }
</script>
@endpush
