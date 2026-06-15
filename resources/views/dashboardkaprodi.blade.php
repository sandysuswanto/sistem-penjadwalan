@extends('layouts_kaprodi.app')

@section('title', 'Dashboard Kaprodi')
@section('header', 'Dashboard Kaprodi')

@section('content')

    {{-- ALERT VALIDASI --}}
    @if ($is_validated)
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
            <i class="fas fa-check-circle mr-2"></i>
            ✅ Data prodi Anda sudah divalidasi pada
            {{ $validated_at ? \Carbon\Carbon::parse($validated_at)->format('d/m/Y H:i') : '-' }}.
            <br>Admin sekarang dapat mengenerate jadwal.
        </div>
    @else
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6 rounded">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            ⚠️ Data prodi Anda belum divalidasi. Silakan lengkapi data dan lakukan validasi agar admin bisa mengenerate
            jadwal.
        </div>
    @endif

    {{-- STATUS KELENGKAPAN --}}
    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">📊 Status Kelengkapan Data</h3>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-4">
            <div class="text-center p-3 bg-gray-50 rounded-lg">
                <p
                    class="text-2xl font-bold {{ $kelengkapan['data']['mata_kuliah_count'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ $kelengkapan['data']['mata_kuliah_count'] }}
                </p>
                <p class="text-sm text-gray-500">📚 Mata Kuliah</p>
            </div>
            <div class="text-center p-3 bg-gray-50 rounded-lg">
                <p
                    class="text-2xl font-bold {{ $kelengkapan['data']['dosen_count'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ $kelengkapan['data']['dosen_count'] }}
                </p>
                <p class="text-sm text-gray-500">👨‍🏫 Dosen</p>
            </div>
            <div class="text-center p-3 bg-gray-50 rounded-lg">
                <p
                    class="text-2xl font-bold {{ $kelengkapan['data']['kelas_count'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ $kelengkapan['data']['kelas_count'] }}
                </p>
                <p class="text-sm text-gray-500">🏫 Kelas</p>
            </div>
            <div class="text-center p-3 bg-gray-50 rounded-lg">
                <p
                    class="text-2xl font-bold {{ $kelengkapan['data']['angkatan_count'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ $kelengkapan['data']['angkatan_count'] }}
                </p>
                <p class="text-sm text-gray-500">🎓 Angkatan</p>
            </div>
        </div>

    </div>

    {{-- TOMBOL VALIDASI --}}
    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">🔐 Validasi Data</h3>

        @if (!$is_validated)
            @if ($kelengkapan['is_complete'])
                @if (empty($matkulTanpaDosen))
                    <form action="{{ route('prodi.validate') }}" method="POST" id="form-validate">
                        @csrf
                        <input type="hidden" name="notes" value="Data sudah lengkap dan siap dijadwalkan">
                        <button type="submit"
                            class="bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-8 rounded-lg transition">
                            <i class="fas fa-check-circle mr-2"></i> Validasi Data (Selesai)
                        </button>
                    </form>
                    <p class="text-sm text-gray-500 mt-3">
                        Dengan melakukan validasi, Anda menyatakan bahwa semua data prodi sudah lengkap dan siap
                        dijadwalkan.
                    </p>
                @else
                    <button disabled class="bg-gray-400 cursor-not-allowed text-white font-semibold py-3 px-8 rounded-lg">
                        <i class="fas fa-lock mr-2"></i> Validasi Tidak Bisa Dilakukan
                    </button>
                    <div class="mt-3 p-3 bg-red-50 rounded border border-red-200">
                        <i class="fas fa-times-circle text-red-600 mr-2"></i>
                        <span class="text-red-700">❌ Tidak bisa validasi karena ada

                            <strong>{{ count($matkulTanpaDosen) }}</strong> mata kuliah tanpa dosen pengampu!
                        </span>
                    </div>
                    <div class="bg-white rounded-lg p-4 mb-3">
                        @foreach ($matkulTanpaDosen as $index => $mk)
                            <div class="py-2 {{ !$loop->last ? 'border-b border-gray-200' : '' }}">
                                • <strong>{{ $mk['nama'] }}</strong> (Semester {{ $mk['semester_ke'] }})
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-4 p-3 bg-yellow-100 rounded border border-yellow-300">
                        <i class="fas fa-lightbulb mr-2"></i>
                        <strong>Solusi:</strong> Silakan edit mata kuliah di atas dan tambahkan minimal 1 dosen pengampu.
                    </div>
                @endif
            @else
                <button disabled class="bg-gray-400 cursor-not-allowed text-white font-semibold py-3 px-8 rounded-lg">
                    <i class="fas fa-lock mr-2"></i> Lengkapi Data Terlebih Dahulu
                </button>
            @endif
        @else
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <span class="text-green-600 font-semibold"><i class="fas fa-check-circle mr-2"></i> Data sudah
                        divalidasi</span>
                    <p class="text-sm text-gray-500 mt-1">Validasi terakhir:
                        {{ $validated_at ? \Carbon\Carbon::parse($validated_at)->format('d/m/Y H:i') : '-' }}</p>
                </div>
                <form action="{{ route('prodi.unvalidate') }}" method="POST">
                    @csrf
                    <input type="hidden" name="notes" value="Ada revisi data">
                    <button type="submit"
                        class="bg-yellow-600 hover:bg-yellow-700 text-white font-semibold py-2 px-6 rounded-lg transition">
                        <i class="fas fa-undo-alt mr-2"></i> Batalkan Validasi
                    </button>
                </form>
            </div>
        @endif
    </div>

    {{-- SCRIPT UNTUK KONFIRMASI VALIDASI --}}
    <script>
        document.getElementById('form-validate')?.addEventListener('submit', function(e) {
            e.preventDefault();

            // Konfirmasi sebelum validasi
            if (confirm(
                    '⚠️ PERHATIAN!\n\nSetelah melakukan validasi:\n1. Data prodi Anda akan dikunci\n2. Admin dapat mengenerate jadwal\n3. Anda tidak bisa mengubah data kecuali membatalkan validasi\n\nApakah Anda yakin data sudah lengkap dan benar?'
                )) {
                this.submit();
            }
        });
    </script>
@endsection
