@extends('layouts_kaprodi.app')

@section('title', 'Dashboard')
@section('header', 'Dashboard')

@section('content')
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {{-- Card: Total Kelas --}}
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm font-medium">Total Kelas</p>
                    <h2 class="text-3xl font-bold mt-1">{{ number_format($totalKelas ?? 0, 0, ',', '.') }}</h2>
                </div>
                <div class="bg-white/20 rounded-full p-3">
                    <i class="fas fa-users text-2xl"></i>
                </div>
            </div>
            <div class="mt-3 text-blue-100 text-xs">
                <i class="fas fa-chart-line mr-1"></i> Kelas aktif semester ini
            </div>
        </div>

        {{-- Card: Total Dosen --}}
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm font-medium">Total Dosen</p>
                    <h2 class="text-3xl font-bold mt-1">{{ number_format($totalDosen ?? 0, 0, ',', '.') }}</h2>
                </div>
                <div class="bg-white/20 rounded-full p-3">
                    <i class="fas fa-chalkboard-user text-2xl"></i>
                </div>
            </div>
            <div class="mt-3 text-green-100 text-xs">
                <i class="fas fa-user-graduate mr-1"></i> Tenaga pengajar aktif
            </div>
        </div>

        {{-- Card: Total Mata Kuliah --}}
        <div class="bg-gradient-to-br from-yellow-500 to-orange-500 rounded-xl shadow-lg p-6 text-white card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-yellow-100 text-sm font-medium">Mata Kuliah</p>
                    <h2 class="text-3xl font-bold mt-1">{{ number_format($totalMatkul ?? 0, 0, ',', '.') }}</h2>
                </div>
                <div class="bg-white/20 rounded-full p-3">
                    <i class="fas fa-book text-2xl"></i>
                </div>
            </div>
            <div class="mt-3 text-yellow-100 text-xs">
                <i class="fas fa-graduation-cap mr-1"></i> Kurikulum aktif
            </div>
        </div>

        {{-- Card untuk Admin atau Kaprodi --}}
        @if (auth()->user()->role == 'admin')
            <div class="bg-gradient-to-br from-red-500 to-pink-500 rounded-xl shadow-lg p-6 text-white card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-red-100 text-sm font-medium">Ruangan (Global)</p>
                        <h2 class="text-3xl font-bold mt-1">{{ number_format($totalRuangan ?? 0, 0, ',', '.') }}</h2>
                    </div>
                    <div class="bg-white/20 rounded-full p-3">
                        <i class="fas fa-building text-2xl"></i>
                    </div>
                </div>
                <div class="mt-3 text-red-100 text-xs">
                    <i class="fas fa-door-open mr-1"></i> Fasilitas tersedia
                </div>
            </div>
        @else
            <div class="bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl shadow-lg p-6 text-white card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-purple-100 text-sm font-medium">Angkatan Aktif</p>
                        <h2 class="text-3xl font-bold mt-1">{{ number_format($totalAngkatan ?? 0, 0, ',', '.') }}</h2>
                    </div>
                    <div class="bg-white/20 rounded-full p-3">
                        <i class="fas fa-layer-group text-2xl"></i>
                    </div>
                </div>
                <div class="mt-3 text-purple-100 text-xs">
                    <i class="fas fa-calendar-alt mr-1"></i> Tahun akademik berjalan
                </div>
            </div>
        @endif
    </div>

    {{-- Statistik Tambahan --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
        {{-- Distribusi Mata Kuliah per Semester --}}
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-chart-pie text-indigo-500 mr-2"></i>
                Distribusi Mata Kuliah per Semester
            </h3>
            <div class="space-y-4">
                {{-- Semester Ganjil --}}
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600">Semester Ganjil</span>
                        <span class="font-semibold text-gray-800">{{ number_format($matkulGanjil ?? 0, 0, ',', '.') }} Mata
                            Kuliah</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-500 rounded-full h-2 transition-all duration-1000"
                            style="width: {{ ($matkulGanjil ?? 0) + ($matkulGenap ?? 0) > 0 ? (($matkulGanjil ?? 0) / (($matkulGanjil ?? 0) + ($matkulGenap ?? 0))) * 100 : 0 }}%">
                        </div>
                    </div>
                </div>

                {{-- Semester Genap --}}
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600">Semester Genap</span>
                        <span class="font-semibold text-gray-800">{{ number_format($matkulGenap ?? 0, 0, ',', '.') }} Mata
                            Kuliah</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-500 rounded-full h-2 transition-all duration-1000"
                            style="width: {{ ($matkulGanjil ?? 0) + ($matkulGenap ?? 0) > 0 ? (($matkulGenap ?? 0) / (($matkulGanjil ?? 0) + ($matkulGenap ?? 0))) * 100 : 0 }}%">
                        </div>
                    </div>
                </div>

                {{-- Total --}}
                <div class="pt-3 border-t border-gray-200">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-700 font-semibold">Total Mata Kuliah</span>
                        <span
                            class="text-2xl font-bold text-indigo-600">{{ number_format(($matkulGanjil ?? 0) + ($matkulGenap ?? 0), 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Status Validasi Prodi (untuk Admin) atau Info Prodi (untuk Kaprodi) --}}
        @if (auth()->user()->role == 'admin')
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-check-circle text-green-500 mr-2"></i>
                    Status Validasi Program Studi
                </h3>
                <div class="space-y-3 max-h-60 overflow-y-auto">
                    @foreach ($prodis ?? [] as $prodi)
                        <div class="flex items-center justify-between py-2 border-b border-gray-100">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-university text-gray-400 text-sm"></i>
                                <span class="text-gray-700 text-sm">{{ $prodi->nama }}</span>
                            </div>
                            @if ($prodi->is_validated)
                                <span class="text-xs bg-green-100 text-green-600 px-2 py-1 rounded-full">
                                    <i class="fas fa-check-circle mr-1"></i> Terverifikasi
                                </span>
                            @else
                                <span class="text-xs bg-yellow-100 text-yellow-600 px-2 py-1 rounded-full">
                                    <i class="fas fa-clock mr-1"></i> Menunggu
                                </span>
                            @endif
                        </div>
                    @endforeach
                </div>
                <div class="mt-4 pt-3 border-t border-gray-200">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Terverifikasi</span>
                        <span
                            class="font-semibold text-green-600">{{ $validatedCount ?? 0 }}/{{ $totalProdi ?? 0 }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                        <div class="bg-green-500 rounded-full h-2 transition-all duration-1000"
                            style="width: {{ ($totalProdi ?? 0) > 0 ? (($validatedCount ?? 0) / ($totalProdi ?? 0)) * 100 : 0 }}%">
                        </div>
                    </div>
                </div>
            </div>
        @else
            {{-- Kaprodi: Info Prodi --}}
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                    Informasi Program Studi
                </h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between py-2 border-b border-gray-100">
                        <span class="text-gray-600">Nama Prodi</span>
                        <span class="font-semibold text-gray-800">{{ $prodi->nama ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-gray-100">
                        <span class="text-gray-600">Kode Prodi</span>
                        <span class="font-semibold text-gray-800">{{ $prodi->kode ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-gray-100">
                        <span class="text-gray-600">Jenjang</span>
                        <span class="font-semibold text-gray-800">{{ $prodi->jenjang ?? 'S1' }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2">
                        <span class="text-gray-600">Status Validasi</span>
                        @if ($prodi->is_validated ?? false)
                            <span class="text-xs bg-green-100 text-green-600 px-2 py-1 rounded-full">
                                <i class="fas fa-check-circle mr-1"></i> Terverifikasi
                            </span>
                        @else
                            <span class="text-xs bg-yellow-100 text-yellow-600 px-2 py-1 rounded-full">
                                <i class="fas fa-clock mr-1"></i> Belum Diverifikasi
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>

    <style>
        .card-hover {
            transition: all 0.3s ease;
        }

        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -12px rgba(0, 0, 0, 0.2);
        }
    </style>
@endsection
