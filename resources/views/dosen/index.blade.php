@extends('layouts_kaprodi.app')
@section('title', 'Manajemen Dosen')
@section('header', 'Manajemen Dosen')
@section('content')

    @php
        $isValidated = auth()->user()->role === 'kaprodi' && optional(auth()->user()->prodi)->is_validated;
    @endphp

    <div class="max-w-7xl mx-auto">
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-chalkboard-user text-indigo-600"></i> Daftar Dosen
                    </h2>
                    @if (!$isValidated)
                        <button onclick="openCreateModal()"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-4 py-2 rounded-lg transition">
                            <i class="fas fa-plus-circle"></i> Tambah Dosen
                        </button>
                    @endif
                </div>

                <div id="alert-container"></div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prodi</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">NIDN</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Slot Tersedia
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($dosens as $dosen)
                                @php
                                    $maxSks = $dosen->max_sks;
                                    $currentSks = $dosen->getTotalSksDiampu('ganjil');
                                    $sisa = $maxSks === null ? '∞' : $maxSks - $currentSks;
                                    $isOver = $maxSks !== null && $currentSks > $maxSks;
                                @endphp
                                <tr data-id="{{ $dosen->id }}" id="dosen-row-{{ $dosen->id }}"
                                    class="{{ $isOver ? 'bg-red-50' : '' }}">
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        @if (!$dosen->prodi_id)
                                            <span class="px-2 py-0.5 bg-blue-100 text-blue-700 text-xs rounded-full font-medium">Umum</span>
                                        @else
                                            {{ $dosen->prodi->nama ?? '-' }}
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $dosen->nidn }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $dosen->nama }}
                                        @if (!$dosen->is_active)
                                            <span class="ml-2 px-2 py-0.5 bg-red-100 text-red-700 text-xs rounded-full">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{!! $dosen->formatted_slot_tersedia !!}</td>

                                    <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                                        @if (!$isValidated)
                                            <button
                                                onclick='openEditModal({{ $dosen->id }}, {{ $dosen->prodi_id ?? 'null' }}, "{{ $dosen->nidn }}", "{{ $dosen->nama }}", {{ json_encode($dosen->slot_tersedia ?? []) }})'
                                                class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 px-3 py-1 rounded-md">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button onclick="deleteDosen({{ $dosen->id }}, {{ $dosen->is_active ? 'true' : 'false' }})"
                                                class="{{ $dosen->is_active ? 'text-red-600 hover:text-red-900 bg-red-50' : 'text-green-600 hover:text-green-900 bg-green-50' }} px-3 py-1 rounded-md">
                                                <i class="fas {{ $dosen->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                                {{ $dosen->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        @else
                                            <span class="text-gray-400 text-sm italic">Terkunci</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($dosens->isEmpty())
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-users fa-3x mb-2"></i>
                        <p>Belum ada data dosen</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- MODAL CREATE/EDIT -->
    <div id="modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50 flex items-start justify-center pt-10 pb-10">
        <div class="flex flex-col border w-full max-w-2xl shadow-lg rounded-md bg-white max-h-[90vh]">
            <div class="flex justify-between items-center px-6 py-4 border-b shrink-0">
                <h3 id="modal-title" class="text-xl font-semibold text-gray-900">Tambah Dosen</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form id="dosen-form" class="flex flex-col min-h-0">
                @csrf
                <input type="hidden" name="_method" id="method" value="POST">
                <input type="hidden" name="id" id="edit-id">

                <div class="px-6 py-4 overflow-y-auto space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 font-medium mb-1">
                                Program Studi
                                @if (auth()->user()->role !== 'admin') <span class="text-red-500">*</span> @endif
                            </label>
                            @if (auth()->user()->role === 'admin')
                                <div class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-gray-100 text-gray-500 text-sm flex items-center gap-2">
                                    <i class="fas fa-globe text-blue-500"></i> Dosen Umum (semua prodi)
                                </div>
                            @else
                                @php $kaprodiProdi = auth()->user()->prodi; @endphp
                                <div class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-gray-100 text-gray-700 text-sm flex items-center gap-2">
                                    <i class="fas fa-building text-blue-500"></i> {{ $kaprodiProdi->nama ?? '-' }}
                                </div>
                                <input type="hidden" name="prodi_id" value="{{ $kaprodiProdi->id ?? '' }}">
                            @endif
                            <div class="text-red-500 text-sm mt-1 error-prodi_id"></div>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-medium mb-1">NIDN <span class="text-red-500">*</span></label>
                            <input type="text" name="nidn" id="nidn" class="w-full border border-gray-300 rounded-lg px-4 py-2" required>
                            <div class="text-red-500 text-sm mt-1 error-nidn"></div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-gray-700 font-medium mb-1">Nama Dosen <span class="text-red-500">*</span></label>
                        <input type="text" name="nama" id="nama" class="w-full border border-gray-300 rounded-lg px-4 py-2" required>
                        <div class="text-red-500 text-sm mt-1 error-nama"></div>
                    </div>

                    <p class="text-xs text-gray-400 -mt-2">
                        <i class="fas fa-info-circle"></i> Kosongkan Program Studi jika ingin menjadikan <strong>Dosen Umum</strong> (bisa ngajar di semua prodi)
                    </p>

                    {{-- Slot Tersedia dengan fitur Pilih Semua per Hari --}}
                    <div>
                        <label class="block text-gray-700 font-medium mb-1">Slot Tersedia</label>
                        <p class="text-xs text-gray-500 mb-2">
                            <i class="fas fa-info-circle"></i>
                            Jika <strong>TIDAK DIPILIH</strong>, dosen tersedia di <strong>SEMUA SLOT</strong> (tanpa
                            batasan).<br>
                            Centang "Pilih Semua Slot" pada hari tertentu untuk memilih semua slot di hari itu.<br>
                            Setiap slot = <strong>1 SKS</strong> kapasitas mengajar.
                        </p>

                        @foreach ([1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'] as $hariId => $hariNama)
                            @php
                                $slotsPerHari = $slots->where('hari', $hariId);
                                $jumlahSlot = $slotsPerHari->count();
                                $totalSks = $jumlahSlot;
                            @endphp
                            <div class="mb-3 border rounded-lg p-3">
                                <div class="flex items-center justify-between mb-2">
                                    <label class="font-semibold text-gray-700 text-sm">{{ $hariNama }}</label>
                                    <label class="flex items-center text-xs text-indigo-600 cursor-pointer">
                                        <input type="checkbox" class="select-all-hari mr-2" data-hari="{{ $hariId }}">
                                        <span>Pilih Semua Slot ({{ $totalSks }} SKS)</span>
                                    </label>
                                </div>
                                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-1">
                                    @foreach ($slotsPerHari as $slot)
                                        @php
                                            $slotId = $slot->id;
                                            $jumlahDosen = \App\Models\Dosen::whereRaw('JSON_CONTAINS(slot_tersedia, ?)', [
                                                json_encode($slotId),
                                            ])->count();
                                            $isPenuh = $jumlahDosen >= 50;
                                        @endphp
                                        <label class="flex items-center p-1.5 rounded hover:bg-gray-50 text-xs {{ $isPenuh ? 'bg-red-50 opacity-60' : '' }}">
                                            <input type="checkbox" name="slot_tersedia[]" value="{{ $slotId }}"
                                                class="slot-checkbox slot-hari-{{ $hariId }} mr-1.5 shrink-0"
                                                {{ $isPenuh ? 'disabled' : '' }}>
                                            <span class="text-gray-700 truncate">{{ $slot->jam_mulai }} - {{ $slot->jam_selesai }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        <div class="text-red-500 text-sm error-slot_tersedia"></div>
                        <div id="sks-summary" class="text-sm text-gray-600 p-2 bg-gray-50 rounded hidden">
                            <strong>Ringkasan Kapasitas:</strong> <span id="total-sks">0</span> SKS dari slot yang dipilih
                        </div>
                        <p class="text-xs text-gray-400 mt-1">
                            ⚠️ Maksimal 50 dosen per slot. Jika tidak pilih slot apapun = tanpa batasan.
                        </p>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 px-6 py-4 border-t shrink-0">
                    <button type="button" onclick="closeModal()"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Batal
                    </button>
                    <button type="submit" id="submit-btn"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('modal');
        const modalTitle = document.getElementById('modal-title');
        const form = document.getElementById('dosen-form');
        const methodInput = document.getElementById('method');
        const editIdInput = document.getElementById('edit-id');
        const nidnInput = document.getElementById('nidn');
        const namaInput = document.getElementById('nama');
        const submitBtn = document.getElementById('submit-btn');
        const sksSummary = document.getElementById('sks-summary');
        const totalSksSpan = document.getElementById('total-sks');

        let isSubmitting = false;

        function updateTotalSks() {
            const selectedSlots = document.querySelectorAll('.slot-checkbox:checked');
            const total = selectedSlots.length;

            if (total > 0) {
                totalSksSpan.innerText = total;
                sksSummary.classList.remove('hidden');
            } else {
                sksSummary.classList.add('hidden');
            }
        }

        function showAlert(message, type = 'success') {
            const alertDiv = document.getElementById('alert-container');
            const bgColor = type === 'success' ? 'bg-green-100 border-green-500 text-green-700' :
                'bg-red-100 border-red-500 text-red-700';
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
            alertDiv.innerHTML =
                `<div class="${bgColor} border-l-4 p-4 mb-6 rounded shadow-sm"><i class="fas ${icon} mr-2"></i> ${message}</div>`;
            setTimeout(() => alertDiv.innerHTML = '', 3000);
        }

        function clearErrors() {
            document.querySelectorAll('.error-prodi_id, .error-nidn, .error-nama, .error-slot_tersedia').forEach(el => el
                .innerText = '');
        }

        function closeModal() {
            modal.classList.add('hidden');
            isSubmitting = false;
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Simpan';
            }
        }

        function openModal() {
            modal.classList.remove('hidden');
        }

        function openCreateModal() {
            modalTitle.innerText = 'Tambah Dosen';
            methodInput.value = 'POST';
            editIdInput.value = '';
            nidnInput.value = '';
            namaInput.value = '';
            document.querySelectorAll('.slot-checkbox').forEach(cb => cb.checked = false);
            document.querySelectorAll('.select-all-hari').forEach(cb => cb.checked = false);
            clearErrors();
            updateTotalSks();
            openModal();
        }

        function openEditModal(id, prodiId, nidn, nama, slotArray) {
            modalTitle.innerText = 'Edit Dosen';
            methodInput.value = 'PUT';
            editIdInput.value = id;
            nidnInput.value = nidn;
            namaInput.value = nama;

            document.querySelectorAll('.slot-checkbox').forEach(cb => cb.checked = false);
            document.querySelectorAll('.select-all-hari').forEach(cb => cb.checked = false);

            if (slotArray && slotArray.length > 0) {
                document.querySelectorAll('.slot-checkbox').forEach(cb => {
                    const slotId = parseInt(cb.value);
                    cb.checked = slotArray.includes(slotId);
                });

                for (let hari = 1; hari <= 6; hari++) {
                    const checkboxesHari = document.querySelectorAll(`.slot-hari-${hari}`);
                    const semuaTercentang = Array.from(checkboxesHari).every(cb => cb.checked || cb.disabled);
                    const selectAllCheckbox = document.querySelector(`.select-all-hari[data-hari="${hari}"]`);
                    if (selectAllCheckbox) {
                        selectAllCheckbox.checked = semuaTercentang;
                    }
                }
            }

            clearErrors();
            updateTotalSks();
            openModal();
        }

        async function deleteDosen(id, isActive) {
            const aksi = isActive ? 'nonaktifkan' : 'aktifkan';
            if (!confirm(`Yakin ingin ${aksi} dosen ini?`)) return;

            try {
                const response = await fetch(`/dosen/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    showAlert(data.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert(data.message, 'error');
                }
            } catch (error) {
                showAlert('Kesalahan koneksi', 'error');
            }
        }

        document.querySelectorAll('.select-all-hari').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const hariId = this.dataset.hari;
                const checkboxesHari = document.querySelectorAll(`.slot-hari-${hariId}`);
                checkboxesHari.forEach(cb => {
                    if (!cb.disabled) {
                        cb.checked = this.checked;
                    }
                });
                updateTotalSks();
            });
        });

        document.querySelectorAll('.slot-checkbox').forEach(cb => {
            cb.addEventListener('change', function() {
                updateTotalSks();
                const hariMatch = this.className.match(/slot-hari-(\d+)/);
                if (hariMatch) {
                    const hari = hariMatch[1];
                    const checkboxesHari = document.querySelectorAll(`.slot-hari-${hari}`);
                    const semuaTercentang = Array.from(checkboxesHari).every(cb => cb.checked || cb
                        .disabled);
                    const selectAllCheckbox = document.querySelector(
                        `.select-all-hari[data-hari="${hari}"]`);
                    if (selectAllCheckbox) {
                        selectAllCheckbox.checked = semuaTercentang;
                    }
                }
            });
        });

        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                if (isSubmitting) return;

                isSubmitting = true;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
                clearErrors();

                const formData = new FormData(form);
                let url = '/dosen';
                const method = methodInput.value;
                const id = editIdInput.value;

                if (method === 'PUT') {
                    url = `/dosen/${id}`;
                    formData.append('_method', 'PUT');
                }

                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: formData
                    });

                    const data = await response.json();

                    if (response.ok && data.success) {
                        showAlert(data.message, 'success');
                        closeModal();
                        setTimeout(() => location.reload(), 1500);
                    } else if (data.errors) {
                        for (let field in data.errors) {
                            let errorEl = document.querySelector(`.error-${field}`);
                            if (errorEl) errorEl.innerText = data.errors[field][0];
                        }
                        showAlert('Validasi gagal', 'error');
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

        window.onclick = function(event) {
            if (event.target === modal) closeModal();
        }
    </script>

@endsection
