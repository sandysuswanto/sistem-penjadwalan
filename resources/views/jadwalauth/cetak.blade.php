<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Jadwal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            @page { size: landscape; margin: 15mm; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
        }
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-white p-6">
    <div class="no-print flex justify-end mb-4">
        <button onclick="window.print()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg shadow transition">
            <i class="fas fa-print"></i> Cetak / Print
        </button>
        <button onclick="window.close()" class="ml-2 bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg shadow transition">
            Tutup
        </button>
    </div>

    <div class="text-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Jadwal Kuliah</h1>
        <p class="text-gray-600">Fakultas Teknik Universitas Wiraraja Sumenep Madura</p>
        @if (!empty($filterLabel))
            <p class="text-sm text-gray-500 mt-1">{{ implode(' | ', $filterLabel) }}</p>
        @endif
        <p class="text-sm text-gray-400 mt-1">jadwal: {{ $tampilan == 'ramadan' ? 'Jadwal Ramadan' : 'Jadwal Normal' }}</p>
    </div>

    @if ($jadwals->count() > 0)
        <table class="w-full border-collapse border border-gray-300 text-sm">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-700">No</th>
                    <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-700">Hari & Jam</th>
                    <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-700">Mata Kuliah</th>
                    <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-700">Kode</th>
                    <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-700">Kelas</th>
                    <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-700">Ruangan</th>
                    <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-700">Dosen</th>
                    <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-700">SKS</th>
                    <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-700">Semester</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($jadwals as $index => $jadwal)
                    <tr class="{{ $index % 2 == 0 ? 'bg-white' : 'bg-gray-50' }}">
                        <td class="border border-gray-300 px-3 py-2 text-gray-700">{{ $index + 1 }}</td>
                        <td class="border border-gray-300 px-3 py-2 text-gray-700">
                            {{ $jadwal->hari_nama ?? '-' }}<br>
                            <span class="text-xs">{{ $jadwal->jam_mulai ?? '' }} - {{ $jadwal->jam_selesai ?? '' }}</span>
                        </td>
                        <td class="border border-gray-300 px-3 py-2 text-gray-700">{{ $jadwal->mataKuliah->nama ?? '-' }}</td>
                        <td class="border border-gray-300 px-3 py-2 text-gray-700">{{ $jadwal->mataKuliah->kode ?? '-' }}</td>
                        <td class="border border-gray-300 px-3 py-2 text-gray-700">
                            {{ $jadwal->kelas->angkatan->tahun ?? '' }}{{ $jadwal->kelas->nama ?? '-' }}
                        </td>
                        <td class="border border-gray-300 px-3 py-2 text-gray-700">{{ $jadwal->ruangan->nama ?? '-' }}</td>
                        <td class="border border-gray-300 px-3 py-2 text-gray-700">{{ $jadwal->dosen->nama ?? '-' }}</td>
                        <td class="border border-gray-300 px-3 py-2 text-gray-700">{{ $jadwal->mataKuliah->sks ?? '-' }}</td>
                        <td class="border border-gray-300 px-3 py-2 text-gray-700">
                            {{ ucfirst($jadwal->semester ?? ($jadwal->mataKuliah->semester ?? '-')) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-6 text-xs text-gray-400 text-center">
            Dicetak pada {{ now()->format('d/m/Y H:i') }} | Sistem Penjadwalan ACO - Fakultas Teknik Universitas Wiraraja
        </div>
    @else
        <div class="text-center py-12 text-gray-500">
            <p>Tidak ada jadwal yang tersedia.</p>
        </div>
    @endif

    <script>
        window.onload = function() { setTimeout(() => window.print(), 500); };
    </script>
</body>
</html>
