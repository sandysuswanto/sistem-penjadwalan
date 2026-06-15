{{-- resources/views/ruangan/index.blade.php --}}
@extends('layouts_kaprodi.app')
@section('title', 'Manajemen Ruangan')
@section('header', 'Manajemen Ruangan')
@section('content')

    <div class="max-w-7xl mx-auto">
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-building text-indigo-600"></i> Daftar Ruangan
                    </h2>
                    <button onclick="openCreateModal()"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition duration-200">
                        <i class="fas fa-plus-circle"></i> Tambah Ruangan
                    </button>
                </div>

                <div id="alert-container"></div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kode</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kapasitas</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pemilik</th>

                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($ruangans as $r)
                                <tr data-id="{{ $r->id }}">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $r->kode }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $r->nama }}
                                        @if (!$r->is_active)
                                            <span class="ml-2 px-2 py-0.5 bg-red-100 text-red-700 text-xs rounded-full">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $r->kapasitas }} org

                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        @if ($r->prodi)
                                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">
                                                <i class="fas fa-building"></i> {{ $r->prodi->nama }}
                                            </span>
                                        @else
                                            <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs">
                                                <i class="fas fa-globe"></i> UMUM (Semua Prodi)
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                                        <button
                                            onclick="openEditModal({{ $r->id }}, '{{ $r->kode }}', '{{ addslashes($r->nama) }}', {{ $r->kapasitas }}, {{ $r->is_lab ? 'true' : 'false' }}, {{ $r->prodi_id ?? 'null' }})"
                                            class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 px-3 py-1 rounded-md transition">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button onclick="deleteRuangan({{ $r->id }}, {{ $r->is_active ? 'true' : 'false' }})"
                                            class="{{ $r->is_active ? 'text-red-600 hover:text-red-900 bg-red-50' : 'text-green-600 hover:text-green-900 bg-green-50' }} hover:bg-red-100 px-3 py-1 rounded-md transition">
                                            <i class="fas {{ $r->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                            {{ $r->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($ruangans->isEmpty())
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-building fa-3x mb-2"></i>
                        <p>Belum ada data ruangan</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div id="modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center pb-3 border-b">
                <h3 id="modal-title" class="text-xl font-semibold text-gray-900">Tambah Ruangan</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form id="ruangan-form">
                @csrf
                <input type="hidden" name="_method" id="method" value="POST">
                <input type="hidden" name="id" id="edit-id">

                <div class="mt-4">
                    <label class="block text-gray-700 font-medium mb-2">Kode Ruangan <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="kode" id="kode"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Contoh: R-IF-01" required>
                    <div class="text-red-500 text-sm mt-1 error-kode"></div>
                </div>

                <div class="mt-4">
                    <label class="block text-gray-700 font-medium mb-2">Nama Ruangan <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="nama" id="nama"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Contoh: Lab Komputer Informatika" required>
                    <div class="text-red-500 text-sm mt-1 error-nama"></div>
                </div>

                <div class="mt-4">
                    <label class="block text-gray-700 font-medium mb-2">Kapasitas <span
                            class="text-red-500">*</span></label>
                    <input type="number" name="kapasitas" id="kapasitas"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        min="1" max="200" placeholder="Jumlah mahasiswa" required>
                    <div class="text-red-500 text-sm mt-1 error-kapasitas"></div>
                </div>



                <div class="mt-4">
                    <label class="block text-gray-700 font-medium mb-2">Pemilik Ruangan</label>
                    <select name="prodi_id" id="prodi_id"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">🌍 UMUM (Bisa dipakai semua prodi)</option>
                        @foreach ($prodis as $prodi)
                            <option value="{{ $prodi->id }}">🏛️ {{ $prodi->nama }} ({{ $prodi->kode }})</option>
                        @endforeach
                    </select>
                    <div class="text-gray-500 text-xs mt-2">
                        <i class="fas fa-info-circle"></i>
                        <strong>Pilih "UMUM"</strong> jika ruangan boleh dipakai semua prodi.<br>
                        <strong>Pilih prodi tertentu</strong> jika ruangan khusus untuk prodi tersebut (prioritas utama).
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
        const form = document.getElementById('ruangan-form');
        const methodInput = document.getElementById('method');
        const editIdInput = document.getElementById('edit-id');

        function openCreateModal() {
            modalTitle.innerText = 'Tambah Ruangan';
            methodInput.value = 'POST';
            editIdInput.value = '';
            document.getElementById('kode').value = '';
            document.getElementById('nama').value = '';
            document.getElementById('kapasitas').value = '';
            document.getElementById('prodi_id').value = '';
            clearErrors();
            modal.classList.remove('hidden');
        }

        function openEditModal(id, kode, nama, kapasitas, isLab, prodiId) {
            modalTitle.innerText = 'Edit Ruangan';
            methodInput.value = 'PUT';
            editIdInput.value = id;
            document.getElementById('kode').value = kode;
            document.getElementById('nama').value = nama;
            document.getElementById('kapasitas').value = kapasitas;
            document.getElementById('prodi_id').value = prodiId || '';
            clearErrors();
            modal.classList.remove('hidden');
        }

        function closeModal() {
            modal.classList.add('hidden');
        }

        function clearErrors() {
            document.querySelectorAll('.error-kode, .error-nama, .error-kapasitas, .error-prodi_id').forEach(el => el
                .innerText = '');
            document.querySelectorAll('#kode, #nama, #kapasitas, #prodi_id').forEach(el => {
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
                // 🔥 EDIT: Gunakan route PUT dengan spoofing
                const id = editIdInput.value;
                url = `/ruangan/${id}`;
                formData.append('_method', 'PUT');
            } else {
                // 🔥 CREATE
                url = '{{ route('ruangan.store') }}';
            }

            try {
                const response = await fetch(url, {
                    method: 'POST', // ← Selalu POST, karena kita spoof dengan _method
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
                    if (data.errors.kode) {
                        document.querySelector('.error-kode').innerText = data.errors.kode[0];
                        document.getElementById('kode').classList.add('border-red-500');
                    }
                    if (data.errors.nama) {
                        document.querySelector('.error-nama').innerText = data.errors.nama[0];
                        document.getElementById('nama').classList.add('border-red-500');
                    }
                    if (data.errors.kapasitas) {
                        document.querySelector('.error-kapasitas').innerText = data.errors.kapasitas[0];
                        document.getElementById('kapasitas').classList.add('border-red-500');
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

        async function deleteRuangan(id, isActive) {
            const aksi = isActive ? 'nonaktifkan' : 'aktifkan';
            if (!confirm(`Yakin ingin ${aksi} ruangan ini?`)) return;

            try {
                const response = await fetch(`/ruangan/${id}`, {
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

        // Close modal with ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });

        // Close modal when clicking outside
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });
    </script>

@endsection
