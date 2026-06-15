@extends('layouts_kaprodi.app')
@section('title', 'Manajemen Prodi')
@section('header', 'Manajemen Prodi')
@section('content')
    <div class="max-w-7xl mx-auto">
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-building text-indigo-600"></i> Daftar Program Studi
                    </h2>
                    <button onclick="openCreateModal()" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> Tambah Prodi
                    </button>
                </div>

                <div id="alert-container"></div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kode</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($prodis as $prodi)
                                <tr data-id="{{ $prodi->id }}">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $prodi->kode }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $prodi->nama }}
                                        @if (!$prodi->is_active)
                                            <span
                                                class="ml-2 px-2 py-0.5 bg-red-100 text-red-700 text-xs rounded-full">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                                        <button
                                            onclick="openEditModal({{ $prodi->id }}, '{{ $prodi->kode }}', '{{ addslashes($prodi->nama) }}')"
                                            class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL CREATE/EDIT --}}
    <div id="modal" class="modal-overlay">
        <div class="modal-box">
            <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200">
                <h3 id="modal-title" class="text-lg font-bold text-gray-900">Tambah Prodi</h3>
                <button onclick="closeModal()"
                    class="text-gray-400 hover:text-gray-600 p-1 hover:bg-gray-100 rounded-lg transition">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <form id="prodi-form">
                @csrf
                <input type="hidden" name="_method" id="method" value="POST">
                <input type="hidden" name="id" id="edit-id">

                <div class="px-6 py-4 space-y-4">
                    <div>
                        <label class="block text-gray-700 font-medium mb-1.5 text-sm">Kode Prodi <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="kode" id="kode"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            required maxlength="10" placeholder="Contoh: IF">
                        <div class="text-red-500 text-sm mt-1 error-kode"></div>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-1.5 text-sm">Nama Prodi <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="nama" id="nama"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            required maxlength="100" placeholder="Contoh: Informatika">
                        <div class="text-red-500 text-sm mt-1 error-nama"></div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-2xl">
                    <button type="button" onclick="closeModal()" class="btn btn-ghost">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // ========================
        // 1. DOM Elements
        // ========================
        const modal = document.getElementById('modal');
        const modalTitle = document.getElementById('modal-title');
        const form = document.getElementById('prodi-form');
        const methodInput = document.getElementById('method');
        const editIdInput = document.getElementById('edit-id');
        const kodeInput = document.getElementById('kode');
        const namaInput = document.getElementById('nama');
        const errorKode = document.querySelector('.error-kode');
        const errorNama = document.querySelector('.error-nama');

        // ========================
        // 2. Fungsi Modal
        // ========================
        function openCreateModal() {
            modalTitle.innerText = 'Tambah Prodi';
            methodInput.value = 'POST';
            editIdInput.value = '';
            form.action = '{{ route('prodi.store') }}';
            kodeInput.value = '';
            namaInput.value = '';
            clearErrors();
            modal.classList.add('active');
        }

        function openEditModal(id, kode, nama) {
            modalTitle.innerText = 'Edit Prodi';
            methodInput.value = 'PUT';
            editIdInput.value = id;
            form.action = `/prodi/${id}`;
            kodeInput.value = kode;
            namaInput.value = nama;
            clearErrors();
            modal.classList.add('active');
        }

        function closeModal() {
            modal.classList.remove('active');
        }

        function clearErrors() {
            errorKode.innerText = '';
            errorNama.innerText = '';
            kodeInput.classList.remove('border-red-500');
            namaInput.classList.remove('border-red-500');
        }

        function showLocalAlert(message, type = 'success') {
            const alertDiv = document.getElementById('alert-container');
            const bgColor = type === 'success' ? 'bg-green-100 border-green-500 text-green-700' :
                'bg-red-100 border-red-500 text-red-700';
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
            alertDiv.innerHTML =
                `<div class="${bgColor} border-l-4 p-4 mb-6 rounded shadow-sm"><i class="fas ${icon} mr-2"></i> ${message}</div>`;
            setTimeout(() => alertDiv.innerHTML = '', 3000);
        }

        // ========================
        // 3. Submit AJAX
        // ========================
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearErrors();

            const formData = new FormData(form);
            let url = form.action;
            let method = methodInput.value;
            if (method === 'PUT') {
                formData.append('_method', 'PUT');
                method = 'POST';
            }

            console.log('Submit to:', url, 'Method:', method);
            try {
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content'),
                        'Accept': 'application/json',
                    },
                    body: formData
                });

                const data = await response.json();
                if (response.ok) {
                    showAlert(data.message, 'success');
                    closeModal();
                    location.reload();
                } else if (response.status === 422 && data.errors) {
                    if (data.errors.kode) {
                        errorKode.innerText = data.errors.kode[0];
                        kodeInput.classList.add('border-red-500');
                    }
                    if (data.errors.nama) {
                        errorNama.innerText = data.errors.nama[0];
                        namaInput.classList.add('border-red-500');
                    }
                    showAlert('Validasi gagal. Periksa input Anda.', 'error');
                } else {
                    showAlert(data.message || 'Terjadi kesalahan', 'error');
                }
            } catch (error) {
                console.error(error);
                showAlert('Kesalahan koneksi', 'error');
            }
        });
    </script>
@endsection
