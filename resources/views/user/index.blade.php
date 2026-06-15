@extends('layouts_kaprodi.app')
@section('title', 'Manajemen User')
@section('header', 'Manajemen User')
@section('content')

    <div class="max-w-7xl mx-auto">
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-users-cog text-indigo-600"></i> Daftar User
                    </h2>
                    <button onclick="openCreateModal()"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition duration-200">
                        <i class="fas fa-plus-circle"></i> Tambah User
                    </button>
                </div>

                <div id="alert-container"></div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prodi</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($users as $u)
                                <tr data-id="{{ $u->id }}">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $u->name }}
                                        @if (!$u->is_active)
                                            <span class="ml-2 px-2 py-0.5 bg-red-100 text-red-700 text-xs rounded-full">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $u->email }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        @if ($u->role == 'admin')
                                            <span class="bg-purple-100 text-purple-800 px-2 py-1 rounded-full text-xs">
                                                <i class="fas fa-shield-alt"></i> Admin
                                            </span>
                                        @else
                                            <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs">
                                                <i class="fas fa-user-tie"></i> Kaprodi
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        @if ($u->prodi)
                                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">
                                                <i class="fas fa-building"></i> {{ $u->prodi->nama }}
                                            </span>
                                        @else
                                            <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs">
                                                <i class="fas fa-globe"></i> Semua Prodi
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                                        <button
                                            onclick="openEditModal({{ $u->id }}, '{{ addslashes($u->name) }}', '{{ $u->email }}', '{{ $u->role }}', {{ $u->prodi_id ?? 'null' }})"
                                            class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 px-3 py-1 rounded-md transition">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button onclick="deleteUser({{ $u->id }}, {{ $u->is_active ? 'true' : 'false' }})"
                                            class="{{ $u->is_active ? 'text-red-600 hover:text-red-900 bg-red-50' : 'text-green-600 hover:text-green-900 bg-green-50' }} hover:bg-red-100 px-3 py-1 rounded-md transition">
                                            <i class="fas {{ $u->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                            {{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($users->isEmpty())
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-users-cog fa-3x mb-2"></i>
                        <p>Belum ada data user</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div id="modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center pb-3 border-b">
                <h3 id="modal-title" class="text-xl font-semibold text-gray-900">Tambah User</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form id="user-form">
                @csrf
                <input type="hidden" name="_method" id="method" value="POST">
                <input type="hidden" name="id" id="edit-id">

                <div class="mt-4">
                    <label class="block text-gray-700 font-medium mb-2">Nama <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Nama lengkap" required>
                    <div class="text-red-500 text-sm mt-1 error-name"></div>
                </div>

                <div class="mt-4">
                    <label class="block text-gray-700 font-medium mb-2">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" id="email"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="user@example.com" required>
                    <div class="text-red-500 text-sm mt-1 error-email"></div>
                </div>

                <div class="mt-4">
                    <label class="block text-gray-700 font-medium mb-2">
                        Password <span class="text-red-500" id="password-required">*</span>
                        <span id="password-hint" class="text-gray-400 text-xs font-normal hidden">(kosongkan jika tidak diubah)</span>
                    </label>
                    <input type="password" name="password" id="password"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Minimal 6 karakter">
                    <div class="text-red-500 text-sm mt-1 error-password"></div>
                </div>

                <div class="mt-4">
                    <label class="block text-gray-700 font-medium mb-2">Role <span class="text-red-500">*</span></label>
                    <select name="role" id="role"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="kaprodi">Kaprodi</option>
                        <option value="admin">Admin</option>
                    </select>
                    <div class="text-red-500 text-sm mt-1 error-role"></div>
                </div>

                <div class="mt-4">
                    <label class="block text-gray-700 font-medium mb-2">Program Studi</label>
                    <select name="prodi_id" id="prodi_id"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Semua Prodi (Admin)</option>
                        @foreach ($prodis as $prodi)
                            <option value="{{ $prodi->id }}">{{ $prodi->nama }} ({{ $prodi->kode }})</option>
                        @endforeach
                    </select>
                    <div class="text-gray-500 text-xs mt-2">
                        <i class="fas fa-info-circle"></i>
                        Pilih prodi jika user adalah Kaprodi, atau biarkan "Semua Prodi" untuk Admin.
                    </div>
                    <div class="text-red-500 text-sm mt-1 error-prodi_id"></div>
                </div>

                <div class="flex justify-end space-x-3 mt-6 pt-3 border-t">
                    <button type="button" onclick="closeModal()"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                        Batal
                    </button>
                    <button type="submit"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('modal');
        const modalTitle = document.getElementById('modal-title');
        const form = document.getElementById('user-form');
        const methodInput = document.getElementById('method');
        const editIdInput = document.getElementById('edit-id');

        function openCreateModal() {
            modalTitle.innerText = 'Tambah User';
            methodInput.value = 'POST';
            editIdInput.value = '';
            document.getElementById('name').value = '';
            document.getElementById('email').value = '';
            document.getElementById('password').value = '';
            document.getElementById('password').required = true;
            document.getElementById('password-required').classList.remove('hidden');
            document.getElementById('password-hint').classList.add('hidden');
            document.getElementById('role').value = 'kaprodi';
            document.getElementById('prodi_id').value = '';
            clearErrors();
            modal.classList.remove('hidden');
        }

        function openEditModal(id, name, email, role, prodiId) {
            modalTitle.innerText = 'Edit User';
            methodInput.value = 'PUT';
            editIdInput.value = id;
            document.getElementById('name').value = name;
            document.getElementById('email').value = email;
            document.getElementById('password').value = '';
            document.getElementById('password').required = false;
            document.getElementById('password-required').classList.add('hidden');
            document.getElementById('password-hint').classList.remove('hidden');
            document.getElementById('role').value = role;
            document.getElementById('prodi_id').value = prodiId || '';
            clearErrors();
            modal.classList.remove('hidden');
        }

        function closeModal() {
            modal.classList.add('hidden');
        }

        function clearErrors() {
            document.querySelectorAll('.error-name, .error-email, .error-password, .error-role, .error-prodi_id').forEach(el => el
                .innerText = '');
            document.querySelectorAll('#name, #email, #password, #role, #prodi_id').forEach(el => {
                el.classList.remove('border-red-500');
            });
        }

        function showLocalAlert(message, type = 'success') {
            const alertDiv = document.getElementById('alert-container');
            const bgColor = type === 'success' ? 'bg-green-100 border-green-500 text-green-700' :
                'bg-red-100 border-red-500 text-red-700';
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
            alertDiv.innerHTML = `
            <div class="${bgColor} border-l-4 p-4 mb-6 rounded shadow-sm">
                <i class="fas ${icon} mr-2"></i> ${message}
            </div>
        `;
            setTimeout(() => alertDiv.innerHTML = '', 5000);
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearErrors();

            const formData = new FormData(form);
            const method = methodInput.value;
            let url;

            if (method === 'PUT') {
                const id = editIdInput.value;
                url = `/user/${id}`;
                formData.append('_method', 'PUT');
            } else {
                url = '{{ route('user.store') }}';
            }

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content'),
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    showAlert(data.message, 'success');
                    closeModal();
                    setTimeout(() => location.reload(), 1500);
                } else if (response.status === 422 && data.errors) {
                    if (data.errors.name) {
                        document.querySelector('.error-name').innerText = data.errors.name[0];
                        document.getElementById('name').classList.add('border-red-500');
                    }
                    if (data.errors.email) {
                        document.querySelector('.error-email').innerText = data.errors.email[0];
                        document.getElementById('email').classList.add('border-red-500');
                    }
                    if (data.errors.password) {
                        document.querySelector('.error-password').innerText = data.errors.password[0];
                        document.getElementById('password').classList.add('border-red-500');
                    }
                    if (data.errors.role) {
                        document.querySelector('.error-role').innerText = data.errors.role[0];
                        document.getElementById('role').classList.add('border-red-500');
                    }
                    if (data.errors.prodi_id) {
                        document.querySelector('.error-prodi_id').innerText = data.errors.prodi_id[0];
                        document.getElementById('prodi_id').classList.add('border-red-500');
                    }
                    showAlert('Validasi gagal, periksa kembali input Anda', 'error');
                } else {
                    showAlert(data.message || 'Terjadi kesalahan', 'error');
                }
            } catch (error) {
                showAlert('Kesalahan koneksi: ' + error.message, 'error');
            }
        });

        async function deleteUser(id, isActive) {
            const aksi = isActive ? 'nonaktifkan' : 'aktifkan';
            if (!confirm(`Yakin ingin ${aksi} user ini?`)) return;

            try {
                const response = await fetch(`/user/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content'),
                        'Accept': 'application/json',
                    }
                });
                const data = await response.json();

                if (response.ok && data.success) {
                    showAlert(data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert(data.message || 'Gagal mengubah status', 'error');
                }
            } catch (error) {
                showAlert('Kesalahan koneksi', 'error');
            }
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });

        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });
    </script>

@endsection
