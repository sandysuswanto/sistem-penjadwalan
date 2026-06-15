@extends('layouts_kaprodi.app')
@section('title', 'Manajemen Kelas')
@section('header', 'Manajemen Kelas')
@section('content')

    @php
        $isValidated = auth()->user()->role === 'kaprodi' && optional(auth()->user()->prodi)->is_validated;
    @endphp

    <div class="max-w-7xl mx-auto">
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-users text-indigo-600"></i> Daftar Kelas
                    </h2>
                    @if (!$isValidated)
                        <button onclick="openCreateModal()"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition duration-200">
                            <i class="fas fa-plus-circle"></i> Tambah Kelas
                        </button>
                    @endif
                </div>

                <div id="alert-container"></div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Kelas</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Angkatan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prodi</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kapasitas</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="kelas-table-body">
                            @forelse ($kelasList as $index => $kelas)
                                <tr data-id="{{ $kelas->id }}" id="kelas-row-{{ $kelas->id }}">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $loop->iteration }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $kelas->nama }}
                                        @if (!$kelas->is_active)
                                            <span class="ml-2 px-2 py-0.5 bg-red-100 text-red-700 text-xs rounded-full">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $kelas->angkatan->tahun ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $kelas->angkatan->prodi->nama ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $kelas->kapasitas ?? 0 }} mahasiswa</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                                        @if (!$isValidated)
                                            <button
                                                onclick='openEditModal({{ $kelas->id }}, "{{ $kelas->nama }}", {{ $kelas->angkatan_id }}, {{ $kelas->kapasitas ?? 0 }})'
                                                class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 px-3 py-1 rounded-md transition duration-200">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button onclick="deleteKelas({{ $kelas->id }}, {{ $kelas->is_active ? 'true' : 'false' }})"
                                                class="{{ $kelas->is_active ? 'text-red-600 hover:text-red-900 bg-red-50' : 'text-green-600 hover:text-green-900 bg-green-50' }} px-3 py-1 rounded-md transition duration-200">
                                                <i class="fas {{ $kelas->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                                {{ $kelas->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        @else
                                            <span class="text-gray-400 text-sm italic">Terkunci</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                        <i class="fas fa-users fa-3x mb-2"></i>
                                        <p>Belum ada data kelas</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL CREATE/EDIT -->
    <div id="modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center pb-3 border-b">
                <h3 id="modal-title" class="text-xl font-semibold text-gray-900">Tambah Kelas</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form id="kelas-form">
                @csrf
                <input type="hidden" name="_method" id="method" value="POST">
                <input type="hidden" name="id" id="edit-id">

                <div class="mt-4">
                    <label class="block text-gray-700 font-medium mb-2">Angkatan <span class="text-red-500">*</span></label>
                    <select name="angkatan_id" id="angkatan_id"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        required>
                        <option value="">-- Pilih Angkatan --</option>
                        @foreach ($angkatans as $angkatan)
                            <option value="{{ $angkatan->id }}" data-prodi-id="{{ $angkatan->prodi_id }}"
                                data-prodi-nama="{{ $angkatan->prodi->nama }}">
                                {{ $angkatan->tahun }} - {{ $angkatan->prodi->nama }}
                            </option>
                        @endforeach
                    </select>
                    <div class="text-red-500 text-sm mt-1 error-angkatan_id"></div>
                </div>

                <div class="mt-4">
                    <label class="block text-gray-700 font-medium mb-2">Nama Kelas <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="nama" id="nama"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        placeholder="Contoh: A, B, C, D" maxlength="10" required>
                    <div class="text-red-500 text-sm mt-1 error-nama"></div>
                </div>

                <div class="mt-4">
                    <label class="block text-gray-700 font-medium mb-2">Kapasitas Kelas <span
                            class="text-red-500">*</span></label>
                    <input type="number" name="kapasitas" id="kapasitas"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        placeholder="Jumlah mahasiswa" min="1" max="200" required>
                    <div id="kapasitas-info" class="text-sm mt-1 hidden"></div>
                    <div class="text-red-500 text-sm mt-1 error-kapasitas"></div>
                </div>

                <div class="flex justify-end space-x-3 mt-6 pt-3 border-t">
                    <button type="button" onclick="closeModal()"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition duration-200">
                        Batal
                    </button>
                    <button type="submit" id="submit-btn"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition duration-200">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // ============================================
        // DOM Elements
        // ============================================
        const modal = document.getElementById('modal');
        const modalTitle = document.getElementById('modal-title');
        const form = document.getElementById('kelas-form');
        const methodInput = document.getElementById('method');
        const editIdInput = document.getElementById('edit-id');
        const angkatanSelect = document.getElementById('angkatan_id');
        const kapasitasInput = document.getElementById('kapasitas');
        const kapasitasInfo = document.getElementById('kapasitas-info');
        const submitBtn = document.getElementById('submit-btn');

        // State variables
        let maxKapasitasTersedia = 0;
        let maxKapasitasProdi = 0;
        let maxKapasitasUmum = 0;
        let isSubmitting = false;

        // ============================================
        // Helper Functions
        // ============================================
        function showAlert(message, type = 'success') {
            const alertDiv = document.getElementById('alert-container');
            const bgColor = type === 'success' ? 'bg-green-100 border-green-500 text-green-700' :
                type === 'warning' ? 'bg-yellow-100 border-yellow-500 text-yellow-700' :
                'bg-red-100 border-red-500 text-red-700';
            const icon = type === 'success' ? 'fa-check-circle' :
                type === 'warning' ? 'fa-exclamation-triangle' :
                'fa-times-circle';

            alertDiv.innerHTML = `
            <div class="${bgColor} border-l-4 p-4 mb-6 rounded shadow-sm flex items-start gap-3">
                <i class="fas ${icon} mt-0.5"></i>
                <div class="flex-1">${message}</div>
                <button onclick="this.parentElement.remove()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;

            setTimeout(() => {
                const alert = alertDiv.firstChild;
                if (alert) alert.style.opacity = '0';
                setTimeout(() => alertDiv.innerHTML = '', 300);
            }, 5000);
        }

        function clearErrors() {
            document.querySelectorAll('.error-angkatan_id, .error-nama, .error-kapasitas').forEach(el => {
                if (el) el.innerText = '';
            });
            document.querySelectorAll('#angkatan_id, #nama, #kapasitas').forEach(el => {
                if (el) el.classList.remove('border-red-500');
            });
        }

        function closeModal() {
            modal.classList.add('hidden');
            modal.style.display = 'none';
            clearErrors();
            kapasitasInfo.classList.add('hidden');
            isSubmitting = false;
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Simpan';
            }
        }

        function openModal() {
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
        }

        // ============================================
        // Kapasitas Validation Functions
        // ============================================
        async function updateKapasitasInfo() {
            const selectedOption = angkatanSelect.options[angkatanSelect.selectedIndex];
            const prodiId = selectedOption?.dataset.prodiId;
            const prodiNama = selectedOption?.dataset.prodiNama;

            if (!prodiId) {
                kapasitasInfo.classList.add('hidden');
                return;
            }

            try {
                const response = await fetch(`/get-max-ruangan-capacity?prodi_id=${prodiId}`);
                if (!response.ok) throw new Error('Failed to fetch capacity');

                const data = await response.json();

                maxKapasitasTersedia = data.max_kapasitas_tersedia;
                maxKapasitasProdi = data.max_kapasitas_prodi;
                maxKapasitasUmum = data.max_kapasitas_umum;

                let infoHtml = `<i class="fas fa-info-circle"></i> `;

                if (data.has_own_rooms) {
                    infoHtml +=
                        `Maksimal kapasitas ruangan milik prodi ${prodiNama}: <strong>${maxKapasitasProdi}</strong> mahasiswa.<br>`;
                } else {
                    infoHtml +=
                        `<span class="text-yellow-600">⚠️ Tidak ada ruangan milik prodi ${prodiNama}.</span><br>`;
                }

                infoHtml += `Maksimal kapasitas ruangan umum: <strong>${maxKapasitasUmum}</strong> mahasiswa.<br>`;
                infoHtml +=
                    `<strong class="text-blue-600">Total maksimal yang bisa digunakan: ${maxKapasitasTersedia} mahasiswa.</strong>`;

                kapasitasInfo.innerHTML = infoHtml;
                kapasitasInfo.classList.remove('hidden');
                kapasitasInfo.classList.add('text-gray-600', 'bg-gray-50', 'p-2', 'rounded');

                if (kapasitasInput) {
                    kapasitasInput.max = maxKapasitasTersedia;
                }

                validateKapasitas();
            } catch (error) {
                console.error('Error fetching capacity:', error);
                kapasitasInfo.innerHTML =
                    '<i class="fas fa-exclamation-triangle"></i> Gagal memuat informasi kapasitas';
                kapasitasInfo.classList.remove('hidden');
            }
        }

        function validateKapasitas() {
            const value = parseInt(kapasitasInput.value);
            const errorDiv = document.querySelector('.error-kapasitas');

            if (!errorDiv) return true;

            if (isNaN(value)) {
                errorDiv.innerText = '';
                kapasitasInput.classList.remove('border-red-500');
                return true;
            }

            if (maxKapasitasTersedia > 0 && value > maxKapasitasTersedia) {
                errorDiv.innerText =
                    `Kapasitas kelas tidak boleh melebihi ${maxKapasitasTersedia} mahasiswa (maksimal ruangan yang tersedia)`;
                kapasitasInput.classList.add('border-red-500');
                return false;
            } else if (maxKapasitasProdi > 0 && value > maxKapasitasProdi) {
                errorDiv.innerText =
                    `⚠️ Peringatan: Kapasitas ${value} melebihi kapasitas ruangan prodi sendiri (${maxKapasitasProdi}). Kelas ini hanya bisa menggunakan ruangan umum.`;
                kapasitasInput.classList.remove('border-red-500');
                return true;
            } else {
                errorDiv.innerText = '';
                kapasitasInput.classList.remove('border-red-500');
                return true;
            }
        }

        // ============================================
        // Modal Functions
        // ============================================
        function openCreateModal() {
            modalTitle.innerText = 'Tambah Kelas';
            methodInput.value = 'POST';
            editIdInput.value = '';
            if (document.getElementById('nama')) document.getElementById('nama').value = '';
            if (kapasitasInput) kapasitasInput.value = '';
            if (angkatanSelect) angkatanSelect.value = '';
            clearErrors();
            kapasitasInfo.classList.add('hidden');
            maxKapasitasTersedia = 0;
            openModal();
        }

        // PERBAIKAN: Fungsi openEditModal
        function openEditModal(id, nama, angkatanId, kapasitas) {
            console.log('Edit modal opened with:', {
                id,
                nama,
                angkatanId,
                kapasitas
            });

            modalTitle.innerText = 'Edit Kelas';
            methodInput.value = 'PUT';
            editIdInput.value = id;
            if (document.getElementById('nama')) document.getElementById('nama').value = nama;
            if (kapasitasInput) kapasitasInput.value = kapasitas;
            if (angkatanSelect) angkatanSelect.value = angkatanId;
            clearErrors();

            // Update kapasitas info after setting angkatan
            setTimeout(() => {
                updateKapasitasInfo();
            }, 100);

            openModal();
        }

        // ========== DELETE FUNCTION (FIXED) ==========
        async function deleteKelas(id, isActive) {
            const aksi = isActive ? 'nonaktifkan' : 'aktifkan';
            if (!confirm(`Yakin ingin ${aksi} kelas ini?`)) {
                return;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            try {
                const response = await fetch(`/kelas/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();
                console.log('Delete response:', data);

                if (data.success === true) {
                    showAlert(data.message, 'success');
                    const row = document.getElementById(`kelas-row-${id}`);
                    if (row) {
                        row.remove();
                    }
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showAlert(data.message || 'Gagal mengubah status', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('Kesalahan koneksi', 'error');
            }
        }

        // ========== FORM SUBMIT (FIXED) ==========
        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();

                if (isSubmitting) return;

                // Validate kapasitas
                if (!validateKapasitas()) {
                    showAlert('Mohon perbaiki validasi kapasitas terlebih dahulu', 'error');
                    return;
                }

                isSubmitting = true;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

                clearErrors();

                const formData = new FormData(form);
                let url;
                const method = methodInput.value;
                const id = editIdInput.value;

                if (method === 'PUT') {
                    url = `/kelas/${id}`;
                    formData.append('_method', 'PUT');
                } else {
                    url = '/kelas';
                }

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    });

                    const data = await response.json();
                    console.log('Submit response:', data);

                    // PERBAIKAN: Cek success dengan benar
                    if (data.success === true) {
                        showAlert(data.message, 'success');
                        closeModal();
                        setTimeout(() => location.reload(), 1500);
                    } else if (data.errors) {
                        // Validation errors
                        for (let field in data.errors) {
                            let errorEl = document.querySelector(`.error-${field}`);
                            let inputEl = document.getElementById(field);
                            if (errorEl) errorEl.innerText = data.errors[field][0];
                            if (inputEl) inputEl.classList.add('border-red-500');
                        }
                        showAlert('Validasi gagal, periksa kembali input Anda', 'error');
                    } else {
                        showAlert(data.message || 'Terjadi kesalahan', 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showAlert('Kesalahan koneksi', 'error');
                } finally {
                    isSubmitting = false;
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save"></i> Simpan';
                }
            });
        }
        // ============================================
        // Event Listeners
        // ============================================
        if (angkatanSelect) {
            angkatanSelect.addEventListener('change', updateKapasitasInfo);
        }
        if (kapasitasInput) {
            kapasitasInput.addEventListener('input', validateKapasitas);
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target === modal) {
                closeModal();
            }
        }

        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Set CSRF token meta if not exists
            if (!document.querySelector('meta[name="csrf-token"]')) {
                const meta = document.createElement('meta');
                meta.name = 'csrf-token';
                meta.content = '{{ csrf_token() }}';
                document.head.appendChild(meta);
            }
        });
    </script>

@endsection
