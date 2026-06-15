<?php

namespace App\Http\Controllers;

use App\Models\Jadwal;
use App\Models\JadwalRamadan; // tambahkan ini
use App\Models\Kelas;
use App\Models\MataKuliah;
use App\Models\Dosen;
use App\Models\Prodi;
use App\Services\AntColonyScheduler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class jadwalglobalController extends Controller
{
    public function cetak(Request $request)
    {
        $tampilan = $request->get('tampilan', 'normal');

        if ($tampilan == 'ramadan') {
            $query = JadwalRamadan::with(['mataKuliah', 'kelas.angkatan.prodi', 'dosen', 'ruangan']);
        } else {
            $query = Jadwal::with(['mataKuliah', 'kelas.angkatan.prodi', 'dosen', 'ruangan']);
        }

        if ($request->filled('prodi')) {
            $query->whereHas('kelas.angkatan.prodi', function ($q) use ($request) {
                $q->where('nama', $request->prodi);
            });
        }

        if ($request->filled('kelas')) {
            $query->whereHas('kelas', function ($q) use ($request) {
                $q->whereRaw("CONCAT((SELECT tahun FROM angkatans WHERE id = kelas.angkatan_id), nama) = ?", [$request->kelas]);
            });
        }

        if ($request->filled('dosen')) {
            $query->whereHas('dosen', function ($q) use ($request) {
                $q->where('nama', $request->dosen);
            });
        }

        if ($request->filled('ruangan')) {
            $query->whereHas('ruangan', function ($q) use ($request) {
                $q->where('nama', $request->ruangan);
            });
        }

        if ($tampilan == 'ramadan') {
            $jadwals = $query->orderBy('hari')->orderBy('jam_mulai')->get();
        } else {
            $jadwals = $query->orderBy('hari')->orderBy('slot_mulai')->get();
        }

        $filterLabel = [];
        if ($request->filled('prodi')) $filterLabel[] = 'Prodi: ' . $request->prodi;
        if ($request->filled('kelas')) $filterLabel[] = 'Kelas: ' . $request->kelas;
        if ($request->filled('dosen')) $filterLabel[] = 'Dosen: ' . $request->dosen;
        if ($request->filled('ruangan')) $filterLabel[] = 'Ruangan: ' . $request->ruangan;
        if ($request->filled('tampilan')) $filterLabel[] = 'Tampilan: ' . ucfirst($request->tampilan);

        return view('jadwalglobal.cetak', compact('jadwals', 'tampilan', 'filterLabel'));
    }

    public function listSchedule(Request $request)
    {
        // Ambil parameter tampilan (normal/ramadan)
        $tampilan = $request->get('tampilan', 'normal');

        if ($tampilan == 'ramadan') {
            $query = JadwalRamadan::with(['mataKuliah', 'kelas.angkatan.prodi', 'dosen', 'ruangan']);
        } else {
            $query = Jadwal::with(['mataKuliah', 'kelas.angkatan.prodi', 'dosen', 'ruangan']);
        }

        // Filter Prodi (berdasarkan nama prodi)
        if ($request->filled('prodi')) {
            $query->whereHas('kelas.angkatan.prodi', function ($q) use ($request) {
                $q->where('nama', $request->prodi);
            });
        }

        // Filter Kelas (concat tahun + nama)
        if ($request->filled('kelas')) {
            $query->whereHas('kelas', function ($q) use ($request) {
                $q->whereRaw("CONCAT((SELECT tahun FROM angkatans WHERE id = kelas.angkatan_id), nama) = ?", [$request->kelas]);
            });
        }

        // Filter Dosen
        if ($request->filled('dosen')) {
            $query->whereHas('dosen', function ($q) use ($request) {
                $q->where('nama', $request->dosen);
            });
        }

        // Filter Ruangan
        if ($request->filled('ruangan')) {
            $query->whereHas('ruangan', function ($q) use ($request) {
                $q->where('nama', $request->ruangan);
            });
        }

        // Ambil data sesuai urutan
        if ($tampilan == 'ramadan') {
            $jadwals = $query->orderBy('hari')->orderBy('jam_mulai')->get();
        } else {
            $jadwals = $query->orderBy('hari')->orderBy('slot_mulai')->get();
        }

        // Siapkan data unik untuk dropdown filter (dari jadwal yang sudah difilter)
        // Untuk Ramadan, gunakan relasi yang sama; untuk normal juga
        $prodiList = $jadwals->pluck('kelas.angkatan.prodi.nama')->unique();
        $kelasList = $jadwals->map(fn($j) => ($j->kelas->angkatan->tahun ?? '') . ($j->kelas->nama ?? ''))->unique();
        $dosenList = $jadwals->pluck('dosen.nama')->unique();
        $ruanganList = $jadwals->pluck('ruangan.nama')->unique();

        return view('jadwalglobal.index', compact(
            'jadwals',
            'prodiList',
            'kelasList',
            'dosenList',
            'ruanganList',
            'tampilan' // kirim ke view
        ));
    }
}
