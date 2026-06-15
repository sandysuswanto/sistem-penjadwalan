@extends('layouts_kaprodi.app')

@section('title', 'Jadwal Kuliah')
@section('header', 'Manajemen Jadwal')

@push('styles')
    <style>
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-active {
            display: flex;
        }
    </style>
@endpush

@section('content')
    <div class="container mx-auto">
        @if (session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded shadow">
                <p>{{ session('success') }}</p>
            </div>
        @endif
        @if (session('error'))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded shadow">
                <p>{{ session('error') }}</p>
            </div>
        @endif

        @if (auth()->user()->role == 'admin')
            <div class="mb-6 flex justify-end gap-2">
                <button onclick="openGenerateModal()"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg shadow transition flex items-center gap-2">
                    <i class="fas fa-sync-alt"></i> Generate Jadwal Baru (Normal)
                </button>
                <button onclick="openRamadanModal()"
                    class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2 px-4 rounded-lg shadow transition flex items-center gap-2">
                    <i class="fas fa-moon"></i> Generate Jadwal Ramadan (Tabel Terpisah)
                </button>
            </div>
        @endif

        {{-- Form Filter --}}
        <div class="bg-white rounded-lg shadow-md p-5 mb-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-3 flex items-center gap-2">
                <i class="fas fa-filter"></i> Filter Jadwal
            </h3>
            <form method="GET" action="{{ route('jadwalauth.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                @if (auth()->user()->role == 'admin')
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Program Studi</label>
                        <select name="prodi_id" class="w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">-- Semua Prodi --</option>
                            @foreach ($prodiList as $prodi)
                                <option value="{{ $prodi->id }}"
                                    {{ request('prodi_id') == $prodi->id ? 'selected' : '' }}>
                                    {{ $prodi->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <input type="hidden" name="prodi_id" value="{{ $userProdiId ?? '' }}">
                @endif

                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Kelas</label>
                    <select name="kelas_id" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">-- Semua Kelas --</option>
                        @foreach ($kelasList as $kelas)
                            <option value="{{ $kelas->id }}" {{ request('kelas_id') == $kelas->id ? 'selected' : '' }}>
                                {{ $kelas->angkatan->tahun }}{{ $kelas->nama }}
                                ({{ $kelas->angkatan->prodi->nama ?? '' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Dosen</label>
                    <select name="dosen_id" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">-- Semua Dosen --</option>
                        @foreach ($dosenList as $dosen)
                            <option value="{{ $dosen->id }}" {{ request('dosen_id') == $dosen->id ? 'selected' : '' }}>
                                {{ $dosen->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Tampilan</label>
                    <select name="tampilan" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="normal" {{ request('tampilan', 'normal') == 'normal' ? 'selected' : '' }}>Jadwal
                            Normal</option>
                        <option value="ramadan" {{ request('tampilan') == 'ramadan' ? 'selected' : '' }}>Jadwal Ramadan
                        </option>
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="btn btn-primary flex-1">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <button type="reset" class="btn btn-ghost px-3"">
                        <i class="fas fa-undo"></i>
                    </button>
                    <a href="{{ route('jadwalauth.cetak', request()->all()) }}" target="_blank"
                        class="btn btn-success px-3">
                        <i class="fas fa-print"></i>
                    </a>
                </div>
            </form>
        </div>

        {{-- Tabel Jadwal --}}
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-700">
                    @if ($tampilan == 'ramadan')
                        <i class="fas fa-moon text-emerald-600"></i> Jadwal Ramadan
                    @else
                        <i class="fas fa-calendar-alt text-indigo-600"></i> Jadwal Normal
                    @endif
                </h3>
                <span class="text-sm text-gray-500">Total: {{ $jadwals->count() }} jadwal</span>
            </div>
            <div class="overflow-x-auto">
                @if ($jadwals->count() > 0)
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hari & Jam</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mata Kuliah</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kelas</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ruangan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dosen</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKS</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Semester</th>
                                @if (auth()->user()->role == 'admin')
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prodi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach ($jadwals as $jadwal)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        {{ $jadwal->hari_nama ?? '-' }}<br>
                                        <span class="text-xs text-gray-500">{{ $jadwal->jam_mulai ?? '' }} -
                                            {{ $jadwal->jam_selesai ?? '' }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $jadwal->mataKuliah->nama ?? '-' }}</div>
                                        <div class="text-xs text-gray-500">{{ $jadwal->mataKuliah->kode ?? '' }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        {{ $jadwal->kelas->angkatan->tahun ?? '' }}{{ $jadwal->kelas->nama ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $jadwal->ruangan->nama ?? '-' }}</td>

                                    {{-- KOLOM DOSEN - MENAMPILKAN DOSEN DARI JADWAL --}}
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        {{ $jadwal->dosen->nama ?? '-' }}
                                    </td>

                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $jadwal->mataKuliah->sks ?? '-' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        {{ ucfirst($jadwal->semester ?? ($jadwal->mataKuliah->semester ?? '-')) }}
                                    </td>
                                    @if (auth()->user()->role == 'admin')
                                        <td class="px-6 py-4 text-sm text-gray-700">
                                            {{ $jadwal->kelas->angkatan->prodi->nama ?? '-' }}
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="text-center py-12">
                        <i class="fas fa-calendar-times text-5xl text-gray-300 mb-3"></i>
                        <p class="text-gray-500">Belum ada jadwal yang tersedia.</p>
                        @if (auth()->user()->role == 'admin')
                            <p class="text-sm text-gray-400 mt-1">Silakan klik tombol "Generate Jadwal Baru" untuk membuat
                                jadwal normal, atau "Generate Jadwal Ramadan" untuk membuat jadwal Ramadan.</p>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Modal Generate --}}
    @if (auth()->user()->role == 'admin')
        <div id="generateModal" class="modal">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-800">Generate Jadwal Baru (Normal)</h3>
                    <button onclick="closeGenerateModal()" class="text-gray-400 hover:text-gray-600"><i
                            class="fas fa-times text-xl"></i></button>
                </div>
                <form action="{{ route('jadwalauth.regenerate') }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Semester</label>
                        <select name="semester" class="w-full border-gray-300 rounded-md shadow-sm" required>
                            <option value="ganjil">Ganjil</option>
                            <option value="genap">Genap</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tahun Ajaran</label>
                        <input type="number" name="tahun_ajaran" value="{{ date('Y') }}"
                            class="w-full border-gray-300 rounded-md shadow-sm" required>
                        <p class="text-xs text-gray-500 mt-1">Contoh: 2025</p>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" onclick="closeGenerateModal()"
                            class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Batal</button>
                        <button type="submit"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Generate</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="ramadanModal" class="modal">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-800">Generate Jadwal Ramadan</h3>
                    <button onclick="closeRamadanModal()" class="text-gray-400 hover:text-gray-600"><i
                            class="fas fa-times text-xl"></i></button>
                </div>
                <form action="{{ route('jadwalauth.generateRamadan') }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Semester</label>
                        <select name="semester" class="w-full border-gray-300 rounded-md shadow-sm" required>
                            <option value="ganjil">Ganjil</option>
                            <option value="genap">Genap</option>
                        </select>
                    </div>
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 mb-4 text-sm text-yellow-700">
                        <i class="fas fa-exclamation-triangle mr-1"></i> Perhatian: Jam mulai 07:30, maksimal 15:00, 35
                        menit/SKS.
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" onclick="closeRamadanModal()"
                            class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Batal</button>
                        <button type="submit"
                            class="px-4 py-2 bg-emerald-600 text-white rounded-md hover:bg-emerald-700">Generate
                            Ramadan</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function openGenerateModal() {
                document.getElementById('generateModal').classList.add('modal-active');
            }

            function closeGenerateModal() {
                document.getElementById('generateModal').classList.remove('modal-active');
            }
            document.getElementById('generateModal').addEventListener('click', function(e) {
                if (e.target === this) closeGenerateModal();
            });

            function openRamadanModal() {
                document.getElementById('ramadanModal').classList.add('modal-active');
            }

            function closeRamadanModal() {
                document.getElementById('ramadanModal').classList.remove('modal-active');
            }
            document.getElementById('ramadanModal').addEventListener('click', function(e) {
                if (e.target === this) closeRamadanModal();
            });
        </script>
    @endif
@endsection
