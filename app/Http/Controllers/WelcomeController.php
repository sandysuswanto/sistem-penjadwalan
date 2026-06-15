<?php
// app/Http/Controllers/WelcomeController.php

namespace App\Http\Controllers;

use App\Models\MataKuliah;
use App\Models\Dosen;
use App\Models\Slot;
use App\Models\Prodi;
use App\Models\Ruangan;
use App\Models\Kelas; // TAMBAHKAN INI

class WelcomeController extends Controller
{
    public function index()
    {
        // LANGSUNG AMBIL DARI DATABASE
        $totalMatkul = MataKuliah::count();
        $totalDosen = Dosen::count();
        $totalKelas = Kelas::count(); // TAMBAHKAN INI

        // Hari Operasional dari tabel slots
        $hariSlots = Slot::select('hari')->distinct()->orderBy('hari')->get();
        $hariMap = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu'
        ];

        $hariOperasional = $hariSlots->map(function ($slot) use ($hariMap) {
            return $hariMap[$slot->hari] ?? "Hari {$slot->hari}";
        });

        $totalHari = $hariOperasional->count();
        $totalSlot = Slot::count();
        $totalRuangan = Ruangan::count();

        // SKS per semester
        $totalSksGanjil = MataKuliah::where('semester', 'ganjil')->sum('sks');
        $totalSksGenap = MataKuliah::where('semester', 'genap')->sum('sks');
        $totalSks = $totalSksGanjil + $totalSksGenap;

        // Prodi
        $totalProdi = Prodi::count();
        $totalProdiTerverifikasi = Prodi::where('is_validated', true)->count();

        // Hitung rata-rata
        $rataSksPerMatkul = $totalMatkul > 0 ? round($totalSks / $totalMatkul, 1) : 0;
        $rasioDosenPerMatkul = $totalMatkul > 0 ? round($totalDosen / $totalMatkul, 2) : 0;
        $slotPerHari = $totalHari > 0 ? round($totalSlot / $totalHari, 1) : 0;

        // Mata kuliah per semester
        $matkulGanjil = MataKuliah::where('semester', 'ganjil')->count();
        $matkulGenap = MataKuliah::where('semester', 'genap')->count();

        // KIRIM SEMUA VARIABEL KE VIEW
        return view('welcome', [
            'totalMatkul' => $totalMatkul,
            'totalDosen' => $totalDosen,
            'totalKelas' => $totalKelas, // TAMBAHKAN INI
            'hariOperasional' => $hariOperasional,
            'totalHari' => $totalHari,
            'totalSlot' => $totalSlot,
            'totalRuangan' => $totalRuangan,
            'totalSksGanjil' => $totalSksGanjil,
            'totalSksGenap' => $totalSksGenap,
            'totalSks' => $totalSks,
            'totalProdi' => $totalProdi,
            'totalProdiTerverifikasi' => $totalProdiTerverifikasi,
            'rataSksPerMatkul' => $rataSksPerMatkul,
            'rasioDosenPerMatkul' => $rasioDosenPerMatkul,
            'slotPerHari' => $slotPerHari,
            'matkulGanjil' => $matkulGanjil,
            'matkulGenap' => $matkulGenap,
        ]);
    }
}
