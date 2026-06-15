<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Jadwal Publik - Sistem Penjadwalan ACO</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 35%, #bfdbfe 100%);
            min-height: 100vh;
        }

        .glass {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(18px);
            border: 1px solid rgba(255, 255, 255, 0.7);
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.08);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            border-radius: 0.75rem;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            justify-content: center;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-primary {
            background: #2563eb;
            color: white;
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.18);
        }

        .btn-primary:hover {
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.35);
        }

        .btn-success {
            background: #059669;
            color: white;
        }

        .btn-ghost {
            background: white;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        .btn-ghost:hover {
            background: #f8fafc;
        }

        th {
            @apply px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider bg-gray-50;
        }

        td {
            @apply px-4 py-3 text-sm text-gray-700;
        }

        tr:hover {
            @apply bg-blue-50/50;
        }

        select,
        input {
            @apply border border-gray-300 rounded-lg px-4 py-2.5 w-full text-sm;
            transition: all 0.3s ease;
        }

        select:focus,
        input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
            outline: none;
        }

        @media (min-width: 768px) {
            .filter-grid {
                grid-template-columns: repeat(6, 1fr);
            }
        }
    </style>
</head>

<body class="antialiased">

    {{-- Navbar --}}
    <nav class="sticky top-0 z-50"
        style="background: rgba(255,255,255,0.92); backdrop-filter: blur(14px); border-bottom: 2px solid rgba(37,99,235,0.15); box-shadow: 0 4px 20px rgba(37,99,235,0.06);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('image/logo.png') }}" alt="Logo" class="w-9 h-9 object-contain">
                    <div>
                        <h1 class="font-bold text-sm text-blue-900">Sistem Penjadwalan</h1>
                        <p class="text-[10px] text-slate-500">Fakultas Teknik Universitas Wiraraja</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('welcome') }}" class="btn btn-ghost text-xs px-3 py-2">
                        <i class="fas fa-home"></i> Beranda
                    </a>
                    <a href="{{ route('login') }}" class="btn btn-primary text-xs px-3 py-2">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        {{-- Header --}}
        <div class="text-center mb-8">
            <h1 class="text-3xl font-extrabold text-blue-900">
                <i class="fas fa-calendar-alt text-blue-500 mr-2"></i> Jadwal Kuliah
            </h1>
            <p class="text-slate-500 mt-1 text-sm">Fakultas Teknik Universitas Wiraraja Sumenep Madura</p>
        </div>

        {{-- Filter --}}
        <div class="glass rounded-2xl p-5 mb-6">
            <form method="GET" class="grid grid-cols-2 md:grid-cols-6 gap-3">
                <select name="tampilan">
                    <option value="normal" {{ request('tampilan', 'normal') == 'normal' ? 'selected' : '' }}>Jadwal
                        Normal</option>
                    <option value="ramadan" {{ request('tampilan') == 'ramadan' ? 'selected' : '' }}>Jadwal Ramadan
                    </option>
                </select>
                <select name="prodi">
                    <option value="">Semua Prodi</option>
                    @foreach ($prodiList as $p)
                        <option value="{{ $p }}" {{ request('prodi') == $p ? 'selected' : '' }}>
                            {{ $p }}</option>
                    @endforeach
                </select>
                <select name="kelas">
                    <option value="">Semua Kelas</option>
                    @foreach ($kelasList as $k)
                        <option value="{{ $k }}" {{ request('kelas') == $k ? 'selected' : '' }}>
                            {{ $k }}</option>
                    @endforeach
                </select>
                <select name="dosen">
                    <option value="">Semua Dosen</option>
                    @foreach ($dosenList as $d)
                        <option value="{{ $d }}" {{ request('dosen') == $d ? 'selected' : '' }}>
                            {{ $d }}</option>
                    @endforeach
                </select>
                <select name="ruangan">
                    <option value="">Semua Ruangan</option>
                    @foreach ($ruanganList as $r)
                        <option value="{{ $r }}" {{ request('ruangan') == $r ? 'selected' : '' }}>
                            {{ $r }}</option>
                    @endforeach
                </select>
                <div class="flex gap-2">
                    <button type="submit" class="btn btn-primary flex-1">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <a href="{{ route('jadwalglobal.list') }}" class="btn btn-ghost px-3">
                        <i class="fas fa-undo"></i>
                    </a>
                    <a href="{{ route('jadwalglobal.cetak', request()->all()) }}" target="_blank"
                        class="btn btn-success px-3">
                        <i class="fas fa-print"></i>
                    </a>
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="glass rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th>Hari</th>
                            <th>Jam</th>
                            <th>Mata Kuliah</th>
                            <th>Kelas</th>
                            <th>Prodi</th>
                            <th>Dosen</th>
                            <th>Ruangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($jadwals as $j)
                            @php
                                if ($tampilan == 'ramadan') {
                                    $jamMulai = \Carbon\Carbon::parse($j->jam_mulai)->format('H:i');
                                    $jamSelesai = \Carbon\Carbon::parse($j->jam_selesai)->format('H:i');
                                    $hariNama =
                                        ['', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'][$j->hari] ?? '-';
                                } else {
                                    $start = 7 * 60 + 30 + $j->slot_mulai * 50;
                                    $end = $start + $j->mataKuliah->sks * 50;
                                    $jamMulai = sprintf('%02d:%02d', floor($start / 60), $start % 60);
                                    $jamSelesai = sprintf('%02d:%02d', floor($end / 60), $end % 60);
                                    $hariNama =
                                        ['', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'][$j->hari] ?? '-';
                                }
                            @endphp
                            <tr class="border-t border-gray-100">
                                <td class="font-medium text-black-700">{{ $hariNama }}</td>
                                <td>{{ $jamMulai }} - {{ $jamSelesai }}</td>
                                <td>{{ $j->mataKuliah->nama ?? '-' }}</td>
                                <td>{{ ($j->kelas->angkatan->tahun ?? '') . ($j->kelas->nama ?? '') }}</td>
                                <td>{{ $j->kelas->angkatan->prodi->nama ?? '-' }}</td>
                                <td>{{ $j->dosen->nama ?? '-' }}</td>
                                <td>{{ $j->ruangan->kode ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-12 text-gray-400">
                                    <i class="fas fa-calendar-times text-4xl mb-3 block"></i>
                                    <p>Tidak ada data jadwal yang tersedia</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Footer --}}
        <footer class="text-center py-6 text-xs text-slate-400">
            &copy; {{ date('Y') }} Sistem Penjadwalan Mata Kuliah - ACO &mdash; Fakultas Teknik Universitas
            Wiraraja Sumenep Madura
        </footer>
    </div>

</body>

</html>
