@extends('layouts_kaprodi.app')
@section('title', 'Manajemen Mata Kuliah')
@section('header', 'Manajemen Mata Kuliah')
@section('content')
    @php
        $isValidated = auth()->user()->role === 'kaprodi' && optional(auth()->user()->prodi)->is_validated;
    @endphp
    <div class="max-w-7xl mx-auto">
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-book text-indigo-600"></i> Daftar Mata Kuliah
                    </h2>
                    @if (!$isValidated)
                        <button onclick="openCreateModal()"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg">
                            <i class="fas fa-plus-circle"></i> Tambah Mata Kuliah
                        </button>
                    @endif
                </div>

                <div id="alert-container"></div>

                <!-- FILTER -->
                <div class="flex flex-wrap gap-4 mb-6">
                    <select id="filter-semester"
                        class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500">
                        <option value="">Semua Semester</option>
                        @for ($i = 1; $i <= 8; $i++)
                            <option value="{{ $i }}" {{ request('semester_ke') == $i ? 'selected' : '' }}>
                                Semester {{ $i }}
                            </option>
                        @endfor
                    </select>

                    <select id="filter-jenis-semester"
                        class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500">
                        <option value="">Semua Jenis</option>
                        <option value="ganjil" {{ request('semester') == 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                        <option value="genap" {{ request('semester') == 'genap' ? 'selected' : '' }}>Genap</option>
                    </select>

                    <!-- TAMBAHKAN FILTER DOSEN -->
                    <select id="filter-dosen"
                        class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500">
                        <option value="">Semua Dosen</option>
                        @foreach ($dosens as $dosen)
                            <option value="{{ $dosen->id }}" {{ request('dosen_id') == $dosen->id ? 'selected' : '' }}>
                                {{ $dosen->nama }}
                            </option>
                        @endforeach
                    </select>

                    <button onclick="applyFilter()"
                        class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">
                        Filter
                    </button>
                    <button onclick="resetFilter()" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
                        Reset
                    </button>
                </div>

                <!-- TABLE -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kode</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prodi</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKS</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Semester</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dosen</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ruangan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($matkuls as $mk)
                                @php
                                    $dosenIdsRaw = $mk->dosen_id ?? [];
                                    if (is_string($dosenIdsRaw)) {
                                        $dosenIdsRaw = json_decode($dosenIdsRaw, true) ?: [];
                                    }
                                    if (!is_array($dosenIdsRaw)) {
                                        $dosenIdsRaw = $dosenIdsRaw ? [$dosenIdsRaw] : [];
                                    }
                                    $dosenIdsJson = json_encode($dosenIdsRaw);

                                    $dosenNames = [];
                                    foreach ($dosenIdsRaw as $did) {
                                        $d = \App\Models\Dosen::find($did);
                                        if ($d) {
                                            $dosenNames[] = $d->nama;
                                        }
                                    }
                                @endphp
                                <tr data-id="{{ $mk->id }}">
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $mk->kode }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $mk->nama }}
                                        @if (!$mk->is_active)
                                            <span class="ml-2 px-2 py-0.5 bg-red-100 text-red-700 text-xs rounded-full">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $mk->prodi->nama }}
                                        ({{ $mk->prodi->kode }})
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $mk->sks }} SKS</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $mk->semester_ke }} -
                                        {{ ucfirst($mk->semester) }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ implode(', ', $dosenNames) ?: '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $mk->ruangan->nama ?? 'Random' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                                        @if (!$isValidated)
                                            <button
                                                onclick='openEditModal({{ $mk->id }}, "{{ addslashes($mk->kode) }}", "{{ addslashes($mk->nama) }}", {{ $mk->prodi_id }}, {{ $mk->sks }}, {{ $mk->semester_ke }}, "{{ $mk->semester }}", {{ $dosenIdsJson }}, {{ $mk->ruangan_id ?? 'null' }})'
                                                class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 px-3 py-1 rounded-md">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button onclick="deleteMatkul({{ $mk->id }}, {{ $mk->is_active ? 'true' : 'false' }})"
                                                class="{{ $mk->is_active ? 'text-red-600 hover:text-red-900 bg-red-50' : 'text-green-600 hover:text-green-900 bg-green-50' }} px-3 py-1 rounded-md">
                                                <i class="fas {{ $mk->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                                {{ $mk->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
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

                @if ($matkuls->isEmpty())
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-book fa-3x mb-2"></i>
                        <p>Belum ada data mata kuliah</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- MODAL CREATE/EDIT -->
    <div id="modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center pb-3">
                <h3 id="modal-title" class="text-xl font-semibold text-gray-900">Tambah Mata Kuliah</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="matkul-form">
                @csrf
                <input type="hidden" name="_method" id="method" value="POST">
                <input type="hidden" name="id" id="edit-id">

                <div class="grid grid-cols-2 gap-4">
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-2">Kode MK</label>
                        <input type="text" name="kode" id="kode"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2" required>
                        <div class="text-red-500 text-sm mt-1 error-kode"></div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-2">Nama MK</label>
                        <input type="text" name="nama" id="nama"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2" required>
                        <div class="text-red-500 text-sm mt-1 error-nama"></div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-2">Prodi</label>
                        <select name="prodi_id" id="prodi_id" class="w-full border border-gray-300 rounded-lg px-4 py-2"
                            required>
                            <option value="">-- Pilih Prodi --</option>
                            @foreach ($prodis as $prodi)
                                <option value="{{ $prodi->id }}" data-prodi-id="{{ $prodi->id }}">
                                    {{ $prodi->nama }} ({{ $prodi->kode }})
                                </option>
                            @endforeach
                        </select>
                        <div class="text-red-500 text-sm mt-1 error-prodi_id"></div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-2">SKS</label>
                        <select name="sks" id="sks" class="w-full border border-gray-300 rounded-lg px-4 py-2"
                            required>
                            <option value="">-- Pilih SKS --</option>
                            <option value="2">2 SKS</option>
                            <option value="3">3 SKS</option>
                        </select>
                        <div class="text-red-500 text-sm mt-1 error-sks"></div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-2">Semester Ke</label>
                        <input type="number" name="semester_ke" id="semester_ke"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2" required min="1"
                            max="8">
                        <div class="text-red-500 text-sm mt-1 error-semester_ke"></div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-2">Jenis Semester</label>
                        <select name="semester" id="semester" class="w-full border border-gray-300 rounded-lg px-4 py-2"
                            required>
                            <option value="">-- Pilih --</option>
                            <option value="ganjil">Ganjil</option>
                            <option value="genap">Genap</option>
                        </select>
                        <div class="text-red-500 text-sm mt-1 error-semester"></div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2">Dosen Pengampu (Opsional)</label>
                    <input type="text" id="dosen-search" placeholder="Cari dosen..." class="w-full border border-gray-300 rounded-lg px-4 py-2 mb-2 text-sm" oninput="filterDosenList()">
                    <div id="dosen-container" class="border border-gray-300 rounded-lg max-h-64 overflow-y-auto p-2 space-y-1">
                        <p class="text-gray-400 text-sm text-center py-4">-- Pilih Prodi terlebih dahulu --</p>
                    </div>
                    <div class="flex items-center gap-2 mt-1">
                        <span id="dosen-count" class="text-xs text-gray-500">0 dosen dipilih</span>
                        <button type="button" onclick="clearSelectedDosen()" class="text-xs text-red-500 hover:text-red-700 ml-auto">Hapus semua</button>
                    </div>
                    <div id="dosen-warning" class="text-red-600 text-sm mt-1 hidden"></div>
                    <div class="text-red-500 text-sm mt-1 error-dosen_id"></div>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2">Ruangan (Opsional)</label>
                    <select name="ruangan_id" id="ruangan_id" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                        <option value="">-- Random (pilih otomatis) --</option>
                    </select>
                    <div class="text-red-500 text-sm mt-1 error-ruangan_id"></div>
                </div>

                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeModal()"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Batal</button>
                    <button type="submit" id="submit-btn"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        #modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        #modal.hidden {
            display: none;
        }

        #modal:not(.hidden) {
            display: flex !important;
        }

        #dosen-container {
            scrollbar-width: thin;
        }
        #dosen-container::-webkit-scrollbar {
            width: 4px;
        }
        #dosen-container::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 2px;
        }
        .dosen-checkbox:checked + span {
            font-weight: 600;
        }
    </style>

    <script>
        const modal = document.getElementById('modal');
        const modalTitle = document.getElementById('modal-title');
        const form = document.getElementById('matkul-form');
        const methodInput = document.getElementById('method');
        const editIdInput = document.getElementById('edit-id');
        const dosenContainer = document.getElementById('dosen-container');
        const ruanganSelect = document.getElementById('ruangan_id');
        const prodiSelect = document.getElementById('prodi_id');
        const semesterSelect = document.getElementById('semester');
        const sksSelect = document.getElementById('sks');
        const submitBtn = document.getElementById('submit-btn');
        const dosenWarning = document.getElementById('dosen-warning');

        let isSubmitting = false;

        // Data dari server
        const semuaDosen = @json($dosens);
        const semuaRuangan = @json($ruangans);
        const sharedProdiIds = @json($sharedProdiIds);

        function getSelectedSemester() {
            return document.getElementById('semester').value || 'ganjil';
        }

    function getSelectedDosenIds() {
        return Array.from(dosenContainer.querySelectorAll('input[type=checkbox]:checked')).map(cb => parseInt(cb.value));
    }

    function getDosenData(id) {
        return semuaDosen.find(d => d.id == id);
    }

    function getGroupLabel(dosen, prodiId) {
        if (!dosen.prodi_id) return { label: '🌐 Dosen Umum', key: 'umum' };
        if (dosen.prodi_id == prodiId) return { label: '🏠 ' + (dosen.prodi?.kode || 'Prodi Sendiri'), key: 'sendiri' };
        if (sharedProdiIds.includes(parseInt(prodiId)) && sharedProdiIds.includes(dosen.prodi_id))
            return { label: '🔗 ' + (dosen.prodi?.kode || 'Sharing'), key: 'sharing' };
        return { label: '📌 ' + (dosen.prodi?.kode || 'Lainnya'), key: 'lainnya' };
    }

    function updateDosenCount() {
        const count = getSelectedDosenIds().length;
        document.getElementById('dosen-count').textContent = count + ' dosen dipilih';
    }

    function clearSelectedDosen() {
        dosenContainer.querySelectorAll('input[type=checkbox]').forEach(cb => cb.checked = false);
        updateDosenCount();
        cekKapasitasDosen();
        debouncedCekApi();
    }

    function filterDosenList() {
        const q = document.getElementById('dosen-search').value.toLowerCase();
        dosenContainer.querySelectorAll('.dosen-item').forEach(el => {
            const match = el.textContent.toLowerCase().includes(q);
            el.style.display = match ? '' : 'none';
        });
    }

    function filterDosenByProdi(prodiId) {
        const semester = getSelectedSemester();

        const sortedDosen = [...semuaDosen].sort((a, b) => {
            const aP = a.prodi_id;
            const bP = b.prodi_id;
            const isShared = sharedProdiIds.includes(parseInt(prodiId));
            const aScore = aP == prodiId ? 0 : (isShared && sharedProdiIds.includes(aP) ? 1 : (!aP ? 2 : 3));
            const bScore = bP == prodiId ? 0 : (isShared && sharedProdiIds.includes(bP) ? 1 : (!bP ? 2 : 3));
            return aScore - bScore || a.nama.localeCompare(b.nama);
        });

        if (!prodiId || sortedDosen.length === 0) {
            dosenContainer.innerHTML = '<p class="text-gray-400 text-sm text-center py-4">-- Pilih Prodi terlebih dahulu --</p>';
            updateDosenCount();
            return;
        }

        let html = '';
        let lastKey = '';

        sortedDosen.forEach(dosen => {
            const maxSlot = dosen.jumlah_slot_tersedia;
            const totalSksSem = semester === 'ganjil' ? (dosen.total_sks_ganjil || 0) : (dosen.total_sks_genap || 0);
            const sisa = maxSlot === null ? '∞' : maxSlot - totalSksSem;
            const isPenuh = maxSlot !== null && sisa <= 0;

            const group = getGroupLabel(dosen, prodiId);

            if (group.key !== lastKey) {
                lastKey = group.key;
                html += `<div class="text-xs font-semibold text-gray-500 px-1 pt-2 pb-1 border-b">${group.label}</div>`;
            }

            const labelText = maxSlot === null
                ? `${dosen.nama} (${dosen.nidn}) — ${semester}: ${totalSksSem} SKS (tanpa batasan)`
                : `${dosen.nama} (${dosen.nidn}) — ${semester}: ${totalSksSem}/${maxSlot} SKS | Sisa: ${sisa}`;

            html += `<label class="dosen-item flex items-center gap-2 px-2 py-1.5 rounded hover:bg-gray-50 cursor-pointer text-sm ${isPenuh ? 'opacity-50 bg-red-50' : ''}">
                <input type="checkbox" value="${dosen.id}"
                    data-max-slot="${maxSlot ?? 999}"
                    data-current-sks="${totalSksSem}"
                    data-sisa="${sisa}"
                    data-is-penuh="${isPenuh ? 'true' : 'false'}"
                    data-unlimited="${maxSlot === null ? 'true' : 'false'}"
                    ${isPenuh ? 'disabled' : ''}
                    class="shrink-0 dosen-checkbox"
                    onchange="updateDosenCount(); cekKapasitasDosen(); debouncedCekApi();">
                <span class="truncate ${isPenuh ? 'line-through text-red-500' : ''}">${labelText}</span>
                ${isPenuh ? '<span class="text-xs text-red-500 shrink-0">PENUH</span>' : ''}
            </label>`;
        });

        dosenContainer.innerHTML = html;
        document.getElementById('dosen-search').value = '';
        updateDosenCount();
    }

        function filterRuanganByProdi(prodiId) {
            if (!prodiId) {
                ruanganSelect.innerHTML = '<option value="">-- Random (pilih otomatis) --</option>';
                return;
            }

            // Filter ruangan berdasarkan prodi_id atau ruangan umum (prodi_id = null)
            const filteredRuangan = semuaRuangan.filter(ruangan =>
                ruangan.prodi_id == prodiId || ruangan.prodi_id === null
            );

            let options = '<option value="">-- Random (pilih otomatis) --</option>';
            filteredRuangan.forEach(ruangan => {
                const pemilik = ruangan.prodi_id ? ruangan.prodi?.nama || 'Prodi' : 'Umum';
                options += `<option value="${ruangan.id}">
                    ${ruangan.nama} (${ruangan.kode}) - Kapasitas: ${ruangan.kapasitas} - ${pemilik}
                </option>`;
            });
            ruanganSelect.innerHTML = options;
        }

        let cekTimeout = null;

        function cekKapasitasDosenViaApi() {
            const semester = getSelectedSemester();
            const dosenIds = getSelectedDosenIds();
            const sks = parseInt(sksSelect.value);
            const editId = editIdInput.value;

            if (!sks || dosenIds.length === 0 || !semester) {
                dosenWarning.classList.add('hidden');
                return;
            }

            fetch(`/matakuliah/cek-sks-dosen?dosen_ids=${JSON.stringify(dosenIds)}&sks=${sks}&semester=${semester}&except_id=${editId || ''}`)
                .then(r => r.json())
                .then(data => {
                    let hasWarning = false;
                    let warnings = [];
                    data.forEach(item => {
                        if (!item.is_valid) {
                            hasWarning = true;
                            warnings.push(
                                `⚠️ ${item.dosen_nama}: sudah ${item.current_sks}/${item.max_slot} SKS + ${sks} = ${item.total_sks} (MELEBIHI BATAS!)`
                            );
                        }
                    });
                    if (hasWarning) {
                        dosenWarning.innerHTML = warnings.join('<br>');
                        dosenWarning.classList.remove('hidden');
                    } else {
                        dosenWarning.classList.add('hidden');
                    }
                })
                .catch(() => {});
        }

        function cekKapasitasDosen() {
            const dosenIds = getSelectedDosenIds();
            const sks = parseInt(sksSelect.value);

            if (!sks || dosenIds.length === 0) {
                dosenWarning.classList.add('hidden');
                return true;
            }

            let hasWarning = false;
            let warnings = [];

            dosenIds.forEach(id => {
                const dosen = getDosenData(id);
                if (!dosen) return;

                const unlimited = dosen.jumlah_slot_tersedia === null;
                if (unlimited) return;

                const maxSlot = dosen.jumlah_slot_tersedia;
                const totalSksSem = semesterSelect.value === 'ganjil' ? (dosen.total_sks_ganjil || 0) : (dosen.total_sks_genap || 0);
                const totalSks = totalSksSem + sks;

                if (totalSks > maxSlot) {
                    hasWarning = true;
                    warnings.push(
                        `⚠️ ${dosen.nama}: sudah ${totalSksSem}/${maxSlot} SKS + ${sks} = ${totalSks} (MELEBIHI BATAS!)`
                    );
                }
            });

            if (hasWarning) {
                dosenWarning.innerHTML = warnings.join('<br>');
                dosenWarning.classList.remove('hidden');
                return false;
            } else {
                dosenWarning.classList.add('hidden');
                return true;
            }
        }

        function debouncedCekApi() {
            if (cekTimeout) clearTimeout(cekTimeout);
            cekTimeout = setTimeout(cekKapasitasDosenViaApi, 300);
        }

        function openCreateModal() {
            modalTitle.innerText = 'Tambah Mata Kuliah';
            methodInput.value = 'POST';
            editIdInput.value = '';
            clearForm();
            clearErrors();
            dosenWarning.classList.add('hidden');

            document.getElementById('semester').value = 'ganjil';
            prodiSelect.value = '';
            filterDosenByProdi('');
            filterRuanganByProdi('');

            modal.classList.remove('hidden');
        }

        function openEditModal(id, kode, nama, prodiId, sks, semesterKe, semester, dosenIds, ruanganId) {
            modalTitle.innerText = 'Edit Mata Kuliah';
            methodInput.value = 'PUT';
            editIdInput.value = id;

            document.getElementById('kode').value = kode;
            document.getElementById('nama').value = nama;
            document.getElementById('sks').value = sks;
            document.getElementById('semester_ke').value = semesterKe;
            document.getElementById('semester').value = semester;

            // Set prodi dulu
            prodiSelect.value = prodiId;

            // Filter dosen & ruangan berdasarkan prodi
            filterDosenByProdi(prodiId);
            filterRuanganByProdi(prodiId);

            // Set dosen yang dipilih
            if (dosenContainer && dosenIds) {
                setTimeout(() => {
                    let idsArray = [];
                    if (dosenIds && dosenIds !== '[]' && dosenIds !== 'null') {
                        idsArray = Array.isArray(dosenIds) ? dosenIds :
                            (typeof dosenIds === 'string' ? JSON.parse(dosenIds) : (dosenIds ? [dosenIds] : []));
                    }
                    dosenContainer.querySelectorAll('input[type=checkbox]').forEach(cb => {
                        cb.checked = idsArray.includes(parseInt(cb.value));
                    });
                    updateDosenCount();
                    cekKapasitasDosen();
                }, 100);
            }

            if (ruanganSelect) {
                setTimeout(() => {
                    ruanganSelect.value = ruanganId;
                }, 100);
            }

            clearErrors();
            modal.classList.remove('hidden');
        }

        function clearForm() {
            document.getElementById('kode').value = '';
            document.getElementById('nama').value = '';
            document.getElementById('sks').value = '';
            document.getElementById('semester_ke').value = '';
            document.getElementById('semester').value = '';
            prodiSelect.value = '';
            if (dosenContainer) dosenContainer.innerHTML = '<p class="text-gray-400 text-sm text-center py-4">-- Pilih Prodi terlebih dahulu --</p>';
            if (ruanganSelect) ruanganSelect.innerHTML = '<option value="">-- Random (pilih otomatis) --</option>';
        }

        function closeModal() {
            modal.classList.add('hidden');
        }

        function clearErrors() {
            document.querySelectorAll(
                '.error-kode, .error-nama, .error-prodi_id, .error-sks, .error-semester_ke, .error-semester, .error-dosen_id, .error-ruangan_id'
            ).forEach(el => el.innerText = '');
            document.querySelectorAll('#kode, #nama, #prodi_id, #sks, #semester_ke, #semester, #dosen_id, #ruangan_id')
                .forEach(el => el.classList.remove('border-red-500'));
        }

        function showAlert(message, type = 'success') {
            const alertDiv = document.getElementById('alert-container');
            const bgColor = type === 'success' ? 'bg-green-100 border-green-500 text-green-700' :
                'bg-red-100 border-red-500 text-red-700';
            alertDiv.innerHTML = `<div class="${bgColor} border-l-4 p-4 mb-6 rounded shadow-sm">${message}</div>`;
            setTimeout(() => alertDiv.innerHTML = '', 3000);
        }

        function applyFilter() {
            const semesterKe = document.getElementById('filter-semester').value;
            const semester = document.getElementById('filter-jenis-semester').value;
            const dosenId = document.getElementById('filter-dosen').value;

            let url = new URL(window.location.href);
            if (semesterKe) url.searchParams.set('semester_ke', semesterKe);
            else url.searchParams.delete('semester_ke');
            if (semester) url.searchParams.set('semester', semester);
            else url.searchParams.delete('semester');
            if (dosenId) url.searchParams.set('dosen_id', dosenId);
            else url.searchParams.delete('dosen_id');
            window.location.href = url.toString();
        }

        function resetFilter() {
            window.location.href = window.location.pathname;
        }

        window.onclick = function(event) {
            if (event.target == modal) closeModal();
        }

        // Event listener untuk prodi change
        prodiSelect.addEventListener('change', function() {
            const prodiId = this.value;
            filterDosenByProdi(prodiId);
            filterRuanganByProdi(prodiId);
        });

        document.getElementById('semester').addEventListener('change', function() {
            const prodiId = prodiSelect.value;
            if (prodiId) filterDosenByProdi(prodiId);
            debouncedCekApi();
        });

        dosenContainer.addEventListener('change', function(e) {
            if (e.target.classList.contains('dosen-checkbox')) {
                cekKapasitasDosen();
                debouncedCekApi();
            }
        });
        sksSelect.addEventListener('change', function() {
            cekKapasitasDosen();
            debouncedCekApi();
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (isSubmitting) return;

            if (!cekKapasitasDosen()) {
                showAlert('Mohon perbaiki kapasitas dosen yang melebihi batas!', 'error');
                return;
            }

            isSubmitting = true;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
            clearErrors();

            const formData = new FormData(form);
            const id = editIdInput.value;
            const method = methodInput.value;

            const dosenIds = getSelectedDosenIds();
            formData.delete('dosen_id[]');
            dosenIds.forEach(id => {
                formData.append('dosen_id[]', id.toString());
            });

            let url;
            if (method === 'PUT') {
                url = `/matakuliah/${id}`;
                formData.append('_method', 'PUT');
            } else {
                url = '/matakuliah';
            }

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content'),
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    showAlert(data.message, 'success');
                    closeModal();
                    setTimeout(() => location.reload(), 1500);
                } else if (response.status === 422 && data.errors) {
                    for (let field in data.errors) {
                        let errorEl = document.querySelector(`.error-${field}`);
                        let inputEl = document.getElementById(field);
                        if (errorEl) errorEl.innerText = data.errors[field][0];
                        if (inputEl && field !== 'dosen_id') inputEl.classList.add('border-red-500');
                    }
                    showAlert('Validasi gagal, periksa input Anda!', 'error');
                } else {
                    showAlert(data.message || 'Terjadi kesalahan', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('Kesalahan koneksi: ' + error.message, 'error');
            } finally {
                isSubmitting = false;
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Simpan';
            }
        });

        async function deleteMatkul(id, isActive) {
            const aksi = isActive ? 'nonaktifkan' : 'aktifkan';
            if (!confirm(`Yakin ingin ${aksi} mata kuliah ini?`)) return;
            try {
                const response = await fetch(`/matakuliah/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content'),
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                if (response.ok && data.success) {
                    showAlert(data.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert(data.message || 'Gagal mengubah status', 'error');
                }
            } catch (error) {
                showAlert('Kesalahan koneksi', 'error');
            }
        }
    </script>
@endsection
