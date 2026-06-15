{{--
    VIEW: Manajemen Angkatan (index)
    Fitur: CRUD dengan modal popup (tanpa reload halaman, kecuali setelah sukses direload sederhana)
    Menggunakan Tailwind CSS, Font Awesome, dan Fetch API (AJAX)
--}}

@extends('layouts_kaprodi.app')
@section('title', 'Manajemen Angkatan')
@section('header', 'Manajemen Angkatan')
@section('content')
    <div class="max-w-7xl mx-auto">
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="p-6">
                {{-- Header: judul dan tombol tambah --}}
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-layer-group text-indigo-600"></i> Daftar Angkatan
                    </h2>
                    <button onclick="openCreateModal()" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> Tambah Angkatan
                    </button>
                </div>

                {{-- Tempat alert sukses/error dari AJAX --}}
                <div id="alert-container"></div>

                {{-- Tabel data angkatan --}}
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200" id="angkatan-table">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Prodi</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Tahun</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            {{-- Loop data dari controller --}}
                            @foreach ($angkatans as $angkatan)
                                <tr data-id="{{ $angkatan->id }}">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $angkatan->prodi->nama }} ({{ $angkatan->prodi->kode }})
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $angkatan->tahun }}
                                        @if (!$angkatan->is_active)
                                            <span
                                                class="ml-2 px-2 py-0.5 bg-red-100 text-red-700 text-xs rounded-full">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                        <button
                                            onclick="openEditModal({{ $angkatan->id }}, '{{ $angkatan->prodi_id }}', '{{ $angkatan->tahun }}')"
                                            class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 px-3 py-1 rounded-md inline-flex items-center gap-1 transition">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>

                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL untuk CREATE dan EDIT --}}
    <div id="modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center pb-3">
                <h3 id="modal-title" class="text-xl font-semibold text-gray-900">Tambah Angkatan</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="angkatan-form">
                @csrf
                {{-- Method spoofing untuk PUT karena form hanya support GET/POST --}}
                <input type="hidden" name="_method" id="method" value="POST">
                <input type="hidden" name="id" id="edit-id"> {{-- menyimpan ID saat edit --}}
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2">Program Studi</label>
                    <select name="prodi_id" id="prodi_id"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        required>
                        <option value="">-- Pilih Prodi --</option>
                        @foreach ($prodis as $prodi)
                            <option value="{{ $prodi->id }}">{{ $prodi->nama }} ({{ $prodi->kode }})</option>
                        @endforeach
                    </select>
                    <div class="text-red-500 text-sm mt-1 error-prodi_id"></div> {{-- tempat error validasi --}}
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2">Tahun</label>
                    <input type="number" name="tahun" id="tahun"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        required min="2000" max="2100">
                    <div class="text-red-500 text-sm mt-1 error-tahun"></div>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeModal()"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Batal</button>
                    <button type="submit"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // ========================
        // 1. Ambil elemen DOM yang diperlukan
        // ========================
        const modal = document.getElementById('modal');
        const modalTitle = document.getElementById('modal-title');
        const form = document.getElementById('angkatan-form');
        const methodInput = document.getElementById('method');
        const editIdInput = document.getElementById('edit-id');
        const prodiSelect = document.getElementById('prodi_id');
        const tahunInput = document.getElementById('tahun');
        const errorProdi = document.querySelector('.error-prodi_id');
        const errorTahun = document.querySelector('.error-tahun');

        // ========================
        // 2. Fungsi untuk membuka modal CREATE
        // ========================
        function openCreateModal() {
            modalTitle.innerText = 'Tambah Angkatan';
            methodInput.value = 'POST'; // set method POST
            editIdInput.value = ''; // kosongkan ID
            form.action = '{{ route('angkatan.store') }}'; // action ke store
            prodiSelect.value = ''; // reset pilihan
            tahunInput.value = ''; // reset tahun
            clearErrors(); // hapus error sebelumnya
            modal.classList.remove('hidden'); // tampilkan modal
        }

        // ========================
        // 3. Fungsi untuk membuka modal EDIT
        //    Parameter: id, prodiId, tahun (dari tombol edit)
        // ========================
        function openEditModal(id, prodiId, tahun) {
            modalTitle.innerText = 'Edit Angkatan';
            methodInput.value = 'PUT'; // set method PUT
            editIdInput.value = id; // simpan ID untuk update
            form.action = `/angkatan/${id}`; // action ke route update (resource)
            prodiSelect.value = prodiId; // isi dropdown dengan prodi yang sedang diedit
            tahunInput.value = tahun; // isi tahun
            clearErrors();
            modal.classList.remove('hidden');
        }

        // ========================
        // 4. Tutup modal
        // ========================
        function closeModal() {
            modal.classList.add('hidden');
        }

        // ========================
        // 5. Hapus pesan error dan styling merah pada input
        // ========================
        function clearErrors() {
            errorProdi.innerText = '';
            errorTahun.innerText = '';
            prodiSelect.classList.remove('border-red-500');
            tahunInput.classList.remove('border-red-500');
        }

        // ========================
        // 6. Tampilkan alert sementara di atas tabel
        //    type: 'success' atau 'error'
        // ========================
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
        // 7. Event submit form (CREATE atau EDIT) menggunakan Fetch API
        // ========================
        form.addEventListener('submit', async (e) => {
            e.preventDefault(); // cegah reload halaman
            clearErrors();

            const formData = new FormData(form);
            const url = form.action;
            let method = methodInput.value;

            // Jika method PUT, tambahkan _method=PUT ke FormData, lalu ubah method fetch menjadi POST
            if (method === 'PUT') {
                formData.append('_method', 'PUT');
                method = 'POST';
            }

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
                    // Sukses: tampilkan pesan, tutup modal, reload halaman (cara simpel)
                    showAlert(data.message, 'success');
                    closeModal();
                    location
                        .reload(); // Reload untuk memperbarui tabel (bisa diganti dengan update DOM jika ingin lebih advanced)
                } else {
                    // Jika response 422 (Unprocessable Entity), tampilkan error validasi
                    if (data.errors) {
                        if (data.errors.prodi_id) {
                            errorProdi.innerText = data.errors.prodi_id[0];
                            prodiSelect.classList.add('border-red-500');
                        }
                        if (data.errors.tahun) {
                            errorTahun.innerText = data.errors.tahun[0];
                            tahunInput.classList.add('border-red-500');
                        }
                    } else {
                        // Error lain (misal 500)
                        showAlert(data.message || 'Terjadi kesalahan', 'error');
                    }
                }
            } catch (error) {
                // Koneksi gagal atau fetch error
                console.error(error);
                showAlert('Kesalahan koneksi', 'error');
            }
        });
    </script>
@endsection
