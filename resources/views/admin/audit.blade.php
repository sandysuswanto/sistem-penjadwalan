@extends('layouts_kaprodi.app')
@section('title', 'Audit Jadwal')
@section('header', 'Audit Jadwal')

@section('content')
    <div class="max-w-7xl mx-auto">
        <div class="bg-white rounded-xl shadow-md overflow-hidden p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-clipboard-check text-indigo-600"></i> Pengecekan & Audit Jadwal
                </h2>
            </div>

            {{-- Filter --}}
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <form method="GET" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Semester</label>
                        <select name="semester" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="ganjil" {{ $semester == 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                            <option value="genap" {{ $semester == 'genap' ? 'selected' : '' }}>Genap</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Tahun Ajaran</label>
                        <input type="number" name="tahun_ajaran" value="{{ $tahunAjaran }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Program Studi</label>
                        <select name="prodi_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">Semua Prodi</option>
                            @foreach ($prodis as $p)
                                <option value="{{ $p->id }}" {{ $prodiId == $p->id ? 'selected' : '' }}>
                                    {{ $p->nama }} ({{ $p->kode }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Cek Semester Ke</label>
                        <select name="semester_ke" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">Semua ({{ $semester == 'ganjil' ? '1,3,5,7' : '2,4,6,8' }})</option>
                            @for ($i = 1; $i <= 8; $i++)
                                <option value="{{ $i }}" {{ $semesterKe == $i ? 'selected' : '' }}>
                                    {{ $i }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Tampilan</label>
                        <select name="tampilan" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="normal" {{ $tampilan == 'normal' ? 'selected' : '' }}>Normal</option>
                            <option value="ramadan" {{ $tampilan == 'ramadan' ? 'selected' : '' }}>Ramadan</option>
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit"
                            class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700">
                            <i class="fas fa-search"></i> Tampilkan
                        </button>
                        <a href="{{ route('admin.audit') }}"
                            class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-400">
                            <i class="fas fa-undo"></i>
                        </a>
                    </div>
                </form>
            </div>

            {{-- Ringkasan --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="text-2xl font-bold text-blue-700">{{ number_format($conflicts['total_jadwal']) }}</div>
                    <div class="text-sm text-blue-600">Total Jadwal Terjadwal</div>
                </div>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="text-2xl font-bold {{ $conflicts['clean'] ? 'text-green-700' : 'text-red-700' }}">
                        {{ $conflicts['clean'] ? '✅ 0' : '⚠️ ' . ($conflicts['dosen']['count'] + $conflicts['ruangan']['count'] + $conflicts['kelas']['count']) }}
                    </div>
                    <div class="text-sm text-green-600">Total Konflik Ditemukan</div>
                </div>
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                    <div class="text-2xl font-bold text-amber-700">{{ number_format($completeness['total_missing']) }}
                    </div>
                    <div class="text-sm text-amber-600">Total Jadwal Tidak Terpenuhi</div>
                </div>
            </div>

            {{-- Tab Navigation --}}
            <div class="border-b border-gray-200 mb-6">
                <ul class="flex flex-wrap -mb-px text-sm font-medium">
                    <li class="mr-2">
                        <a href="#konflik"
                            class="tab-link active inline-block p-3 border-b-2 border-indigo-600 text-indigo-600"
                            data-tab="konflik">
                            <i class="fas fa-exclamation-triangle mr-1"></i> Cek Konflik
                        </a>
                    </li>
                    <li class="mr-2">
                        <a href="#kelengkapan"
                            class="tab-link inline-block p-3 border-b-2 border-transparent text-gray-500 hover:text-gray-700"
                            data-tab="kelengkapan">
                            <i class="fas fa-list-check mr-1"></i> Cek Kelengkapan Kelas
                        </a>
                    </li>
                </ul>
            </div>

            {{-- Tab: Konflik --}}
            <div id="tab-konflik" class="tab-content">
                @if ($conflicts['clean'])
                    <div class="bg-green-50 border border-green-200 rounded-lg p-6 text-center">
                        <i class="fas fa-check-circle text-5xl text-green-500 mb-3"></i>
                        <h3 class="text-lg font-semibold text-green-700">Tidak Ada Konflik</h3>
                        <p class="text-green-600 text-sm mt-1">
                            Algoritma ACO berhasil menghasilkan jadwal tanpa konflik dosen, ruangan, maupun waktu/kelas.
                        </p>
                    </div>
                @else
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                        <i class="fas fa-exclamation-circle text-red-500 mr-1"></i>
                        Ditemukan konflik pada jadwal!
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div
                        class="border rounded-lg p-4 {{ $conflicts['dosen']['count'] > 0 ? 'border-red-300 bg-red-50' : 'border-green-300 bg-green-50' }}">
                        <div
                            class="text-lg font-bold {{ $conflicts['dosen']['count'] > 0 ? 'text-red-700' : 'text-green-700' }}">
                            Konflik Dosen: {{ $conflicts['dosen']['count'] }}
                        </div>
                        <div class="text-sm text-gray-500">
                            {{ $conflicts['dosen']['count'] > 0 ? 'Terjadi' : 'Tidak terjadi' }}</div>
                    </div>
                    <div
                        class="border rounded-lg p-4 {{ $conflicts['ruangan']['count'] > 0 ? 'border-red-300 bg-red-50' : 'border-green-300 bg-green-50' }}">
                        <div
                            class="text-lg font-bold {{ $conflicts['ruangan']['count'] > 0 ? 'text-red-700' : 'text-green-700' }}">
                            Konflik Ruangan: {{ $conflicts['ruangan']['count'] }}
                        </div>
                        <div class="text-sm text-gray-500">
                            {{ $conflicts['ruangan']['count'] > 0 ? 'Terjadi' : 'Tidak terjadi' }}</div>
                    </div>
                    <div
                        class="border rounded-lg p-4 {{ $conflicts['kelas']['count'] > 0 ? 'border-red-300 bg-red-50' : 'border-green-300 bg-green-50' }}">
                        <div
                            class="text-lg font-bold {{ $conflicts['kelas']['count'] > 0 ? 'text-red-700' : 'text-green-700' }}">
                            Konflik Waktu/Kelas: {{ $conflicts['kelas']['count'] }}
                        </div>
                        <div class="text-sm text-gray-500">
                            {{ $conflicts['kelas']['count'] > 0 ? 'Terjadi' : 'Tidak terjadi' }}</div>
                    </div>
                </div>

                @if ($conflicts['dosen']['count'] > 0)
                    <div class="mt-4">
                        <h4 class="font-semibold text-red-700 mb-2">Detail Konflik Dosen</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-xs">
                                <thead>
                                    <tr class="bg-red-100">
                                        <th class="px-3 py-2 text-left">Hari</th>
                                        <th class="px-3 py-2 text-left">Dosen</th>
                                        <th class="px-3 py-2 text-left">Matkul 1</th>
                                        <th class="px-3 py-2 text-left">Kelas 1</th>
                                        <th class="px-3 py-2 text-left">Matkul 2</th>
                                        <th class="px-3 py-2 text-left">Kelas 2</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($conflicts['dosen']['items'] as $item)
                                        <tr class="border-b border-red-100">
                                            <td class="px-3 py-2">{{ $item['hari'] }}</td>
                                            <td class="px-3 py-2 font-medium">{{ $item['dosen'] }}</td>
                                            <td class="px-3 py-2">{{ $item['matkul1'] }}</td>
                                            <td class="px-3 py-2">{{ $item['kelas1'] }}</td>
                                            <td class="px-3 py-2">{{ $item['matkul2'] }}</td>
                                            <td class="px-3 py-2">{{ $item['kelas2'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                @if ($conflicts['ruangan']['count'] > 0)
                    <div class="mt-4">
                        <h4 class="font-semibold text-red-700 mb-2">Detail Konflik Ruangan</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-xs">
                                <thead>
                                    <tr class="bg-red-100">
                                        <th class="px-3 py-2 text-left">Hari</th>
                                        <th class="px-3 py-2 text-left">Ruangan</th>
                                        <th class="px-3 py-2 text-left">Matkul 1</th>
                                        <th class="px-3 py-2 text-left">Kelas 1</th>
                                        <th class="px-3 py-2 text-left">Matkul 2</th>
                                        <th class="px-3 py-2 text-left">Kelas 2</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($conflicts['ruangan']['items'] as $item)
                                        <tr class="border-b border-red-100">
                                            <td class="px-3 py-2">{{ $item['hari'] }}</td>
                                            <td class="px-3 py-2 font-medium">{{ $item['ruangan'] }}</td>
                                            <td class="px-3 py-2">{{ $item['matkul1'] }}</td>
                                            <td class="px-3 py-2">{{ $item['kelas1'] }}</td>
                                            <td class="px-3 py-2">{{ $item['matkul2'] }}</td>
                                            <td class="px-3 py-2">{{ $item['kelas2'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                @if ($conflicts['kelas']['count'] > 0)
                    <div class="mt-4">
                        <h4 class="font-semibold text-red-700 mb-2">Detail Konflik Waktu/Kelas</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-xs">
                                <thead>
                                    <tr class="bg-red-100">
                                        <th class="px-3 py-2 text-left">Hari</th>
                                        <th class="px-3 py-2 text-left">Kelas</th>
                                        <th class="px-3 py-2 text-left">Matkul 1</th>
                                        <th class="px-3 py-2 text-left">Matkul 2</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($conflicts['kelas']['items'] as $item)
                                        <tr class="border-b border-red-100">
                                            <td class="px-3 py-2">{{ $item['hari'] }}</td>
                                            <td class="px-3 py-2 font-medium">{{ $item['kelas'] }}</td>
                                            <td class="px-3 py-2">{{ $item['matkul1'] }}</td>
                                            <td class="px-3 py-2">{{ $item['matkul2'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Tab: Kelengkapan --}}
            <div id="tab-kelengkapan" class="tab-content hidden">
                @if (count($completeness['items']) > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Prodi
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Semester
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Jumlah
                                        Matkul</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Jumlah
                                        Kelas</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Target
                                        Jadwal</th>

                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Kurang
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Kelas
                                        Bermasalah</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($completeness['items'] as $item)
                                    <tr class="{{ $item['missing'] > 0 ? 'bg-red-50' : 'bg-green-50' }}">
                                        <td class="px-4 py-3 font-medium">{{ $item['prodi'] }}</td>
                                        <td class="px-4 py-3">Semester {{ $item['semester_ke'] }}</td>
                                        <td class="px-4 py-3 text-center">{{ $item['jml_matkul'] }}</td>
                                        <td class="px-4 py-3 text-center">{{ $item['jml_kelas'] }}</td>
                                        <td class="px-4 py-3 text-center font-medium">{{ $item['expected'] }}</td>

                                        <td
                                            class="px-4 py-3 text-center {{ $item['missing'] > 0 ? 'text-red-600 font-bold' : 'text-green-600' }}">
                                            {{ $item['missing'] > 0 ? $item['missing'] : '0' }}
                                        </td>
                                        <td class="px-4 py-3">
                                            @if (count($item['kelas_details']) > 0)
                                                @foreach ($item['kelas_details'] as $kd)
                                                    <div class="text-red-600 text-xs mb-2">
                                                        <strong>{{ $kd['kelas'] }}</strong>:
                                                        @foreach ($kd['missing_matkuls'] as $mk)
                                                            <div class="ml-2 flex items-start gap-1">
                                                                <span class="text-red-700">{{ $mk['nama'] }}</span>
                                                                <span class="text-gray-400 italic">—
                                                                    {{ $mk['reason'] }}</span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endforeach
                                            @else
                                                <span class="text-green-600 text-xs">✅ Lengkap</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-100 font-semibold">
                                <tr>
                                    <td colspan="4" class="px-4 py-3 text-right">Total</td>
                                    <td class="px-4 py-3 text-center">{{ $completeness['total_expected'] }}</td>
                                    <td
                                        class="px-4 py-3 text-center {{ $completeness['total_actual'] < $completeness['total_expected'] ? 'text-red-600' : 'text-green-600' }}">
                                        {{ $completeness['total_actual'] }}
                                    </td>
                                    <td
                                        class="px-4 py-3 text-center {{ $completeness['total_missing'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                                        {{ $completeness['total_missing'] }}
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="text-center py-8 text-gray-400">
                        <i class="fas fa-database fa-3x mb-2"></i>
                        <p>Tidak ada data untuk ditampilkan</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.tab-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('.tab-link').forEach(l => {
                    l.classList.remove('border-indigo-600', 'text-indigo-600');
                    l.classList.add('border-transparent', 'text-gray-500');
                });
                this.classList.remove('border-transparent', 'text-gray-500');
                this.classList.add('border-indigo-600', 'text-indigo-600');

                document.querySelectorAll('.tab-content').forEach(tc => tc.classList.add('hidden'));
                const tabId = 'tab-' + this.dataset.tab;
                document.getElementById(tabId).classList.remove('hidden');
            });
        });
    </script>
@endsection
