@extends('layouts_kaprodi.app')
@section('title', 'Manajemen Slot')
@section('header', 'Manajemen Slot')
@section('content')

    <div class="max-w-7xl mx-auto">
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-clock text-indigo-600"></i> Daftar Slot Waktu
                    </h2>
                    <button onclick="openCreateModal()"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition duration-200">
                        <i class="fas fa-plus-circle"></i> Tambah Slot
                    </button>
                </div>

                <div id="alert-container"></div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hari</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Slot Ke-</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jam Mulai</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jam Selesai</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Durasi SKS</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($slots as $s)
                                <tr data-id="{{ $s->id }}">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $s->hari_nama }}
                                        @if (!$s->is_active)
                                            <span class="ml-2 px-2 py-0.5 bg-red-100 text-red-700 text-xs rounded-full">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $s->slot_ke }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $s->jam_mulai }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $s->jam_selesai }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $s->durasi_sks }} SKS</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        @if ($s->is_active)
                                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">
                                                <i class="fas fa-check-circle"></i> Aktif
                                            </span>
                                        @else
                                            <span class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs">
                                                <i class="fas fa-times-circle"></i> Nonaktif
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                                        <button
                                            onclick="openEditModal({{ $s->id }}, {{ $s->hari }}, {{ $s->slot_ke }}, '{{ $s->jam_mulai }}', '{{ $s->jam_selesai }}', {{ $s->durasi_sks }}, {{ $s->is_active ? 'true' : 'false' }})"
                                            class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 px-3 py-1 rounded-md transition">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button onclick="deleteSlot({{ $s->id }}, {{ $s->is_active ? 'true' : 'false' }})"
                                            class="{{ $s->is_active ? 'text-red-600 hover:text-red-900 bg-red-50' : 'text-green-600 hover:text-green-900 bg-green-50' }} hover:bg-red-100 px-3 py-1 rounded-md transition">
                                            <i class="fas {{ $s->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                            {{ $s->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($slots->isEmpty())
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-clock fa-3x mb-2"></i>
                        <p>Belum ada data slot</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div id="modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center pb-3 border-b">
                <h3 id="modal-title" class="text-xl font-semibold text-gray-900">Tambah Slot</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form id="slot-form">
                @csrf
                <input type="hidden" name="_method" id="method" value="POST">
                <input type="hidden" name="id" id="edit-id">

                <div class="mt-4">
                    <label class="block text-gray-700 font-medium mb-2">Hari <span class="text-red-500">*</span></label>
                    <select name="hari" id="hari"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="1">Senin</option>
                        <option value="2">Selasa</option>
                        <option value="3">Rabu</option>
                        <option value="4">Kamis</option>
                        <option value="5">Jumat</option>
                        <option value="6">Sabtu</option>
                    </select>
                    <div class="text-red-500 text-sm mt-1 error-hari"></div>
                </div>

                <div class="mt-4">
                    <label class="block text-gray-700 font-medium mb-2">Slot Ke- <span class="text-red-500">*</span></label>
                    <input type="number" name="slot_ke" id="slot_ke"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        min="0" max="11" placeholder="0-11" required>
                    <div class="text-red-500 text-sm mt-1 error-slot_ke"></div>
                </div>

                <div class="mt-4">
                    <label class="block text-gray-700 font-medium mb-2">Jam Mulai <span class="text-red-500">*</span></label>
                    <input type="text" name="jam_mulai" id="jam_mulai"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="HH:MM" required>
                    <div class="text-red-500 text-sm mt-1 error-jam_mulai"></div>
                </div>

                <div class="mt-4">
                    <label class="block text-gray-700 font-medium mb-2">Jam Selesai <span class="text-red-500">*</span></label>
                    <input type="text" name="jam_selesai" id="jam_selesai"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="HH:MM" required>
                    <div class="text-red-500 text-sm mt-1 error-jam_selesai"></div>
                </div>

                <div class="mt-4">
                    <label class="block text-gray-700 font-medium mb-2">Durasi SKS <span class="text-red-500">*</span></label>
                    <input type="number" name="durasi_sks" id="durasi_sks"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        min="1" max="3" placeholder="1" required>
                    <div class="text-red-500 text-sm mt-1 error-durasi_sks"></div>
                </div>

                <div class="mt-4">
                    <label class="block text-gray-700 font-medium mb-2">Status Aktif</label>
                    <select name="is_active" id="is_active"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="1">Aktif</option>
                        <option value="0">Nonaktif</option>
                    </select>
                    <div class="text-red-500 text-sm mt-1 error-is_active"></div>
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
        const form = document.getElementById('slot-form');
        const methodInput = document.getElementById('method');
        const editIdInput = document.getElementById('edit-id');

        function openCreateModal() {
            modalTitle.innerText = 'Tambah Slot';
            methodInput.value = 'POST';
            editIdInput.value = '';
            document.getElementById('hari').value = '1';
            document.getElementById('slot_ke').value = '';
            document.getElementById('jam_mulai').value = '';
            document.getElementById('jam_selesai').value = '';
            document.getElementById('durasi_sks').value = '';
            document.getElementById('is_active').value = '1';
            clearErrors();
            modal.classList.remove('hidden');
        }

        function openEditModal(id, hari, slotKe, jamMulai, jamSelesai, durasiSks, isActive) {
            modalTitle.innerText = 'Edit Slot';
            methodInput.value = 'PUT';
            editIdInput.value = id;
            document.getElementById('hari').value = hari;
            document.getElementById('slot_ke').value = slotKe;
            document.getElementById('jam_mulai').value = jamMulai;
            document.getElementById('jam_selesai').value = jamSelesai;
            document.getElementById('durasi_sks').value = durasiSks;
            document.getElementById('is_active').value = isActive ? '1' : '0';
            clearErrors();
            modal.classList.remove('hidden');
        }

        function closeModal() {
            modal.classList.add('hidden');
        }

        function clearErrors() {
            document.querySelectorAll('.error-hari, .error-slot_ke, .error-jam_mulai, .error-jam_selesai, .error-durasi_sks, .error-is_active').forEach(el => el
                .innerText = '');
            document.querySelectorAll('#hari, #slot_ke, #jam_mulai, #jam_selesai, #durasi_sks, #is_active').forEach(el => {
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
                url = `/slot/${id}`;
                formData.append('_method', 'PUT');
            } else {
                url = '{{ route('slot.store') }}';
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
                    if (data.errors.hari) {
                        document.querySelector('.error-hari').innerText = data.errors.hari[0];
                        document.getElementById('hari').classList.add('border-red-500');
                    }
                    if (data.errors.slot_ke) {
                        document.querySelector('.error-slot_ke').innerText = data.errors.slot_ke[0];
                        document.getElementById('slot_ke').classList.add('border-red-500');
                    }
                    if (data.errors.jam_mulai) {
                        document.querySelector('.error-jam_mulai').innerText = data.errors.jam_mulai[0];
                        document.getElementById('jam_mulai').classList.add('border-red-500');
                    }
                    if (data.errors.jam_selesai) {
                        document.querySelector('.error-jam_selesai').innerText = data.errors.jam_selesai[0];
                        document.getElementById('jam_selesai').classList.add('border-red-500');
                    }
                    if (data.errors.durasi_sks) {
                        document.querySelector('.error-durasi_sks').innerText = data.errors.durasi_sks[0];
                        document.getElementById('durasi_sks').classList.add('border-red-500');
                    }
                    if (data.errors.is_active) {
                        document.querySelector('.error-is_active').innerText = data.errors.is_active[0];
                        document.getElementById('is_active').classList.add('border-red-500');
                    }
                    showAlert('Validasi gagal, periksa kembali input Anda', 'error');
                } else {
                    showAlert(data.message || 'Terjadi kesalahan', 'error');
                }
            } catch (error) {
                showAlert('Kesalahan koneksi: ' + error.message, 'error');
            }
        });

        async function deleteSlot(id, isActive) {
            const aksi = isActive ? 'nonaktifkan' : 'aktifkan';
            if (!confirm(`Yakin ingin ${aksi} slot ini?`)) return;

            try {
                const response = await fetch(`/slot/${id}`, {
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
