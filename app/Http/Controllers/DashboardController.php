<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Dosen;
use App\Models\MataKuliah;
use App\Models\Ruangan;
use App\Models\Angkatan;
use App\Models\Prodi;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // ADMIN
        if ($user->role === 'admin') {
            $prodis = Prodi::all();
            $validatedCount = Prodi::where('is_validated', true)->count();
            $allValidated = ($validatedCount == $prodis->count() && $prodis->count() > 0);

            // HITUNG MATA KULIAH PER SEMESTER
            $matkulGanjil = MataKuliah::where('semester', 'ganjil')->count();
            $matkulGenap = MataKuliah::where('semester', 'genap')->count();

            // TOTAL PRODI
            $totalProdi = Prodi::count();

            return view('dashboard', [
                'kelas' => Kelas::latest()->take(5)->get(),
                'dosen' => Dosen::latest()->take(5)->get(),
                'matkul' => MataKuliah::latest()->take(5)->get(),
                'angkatan' => Angkatan::latest()->take(5)->get(),
                'totalKelas' => Kelas::count(),
                'totalDosen' => Dosen::count(),
                'totalMatkul' => MataKuliah::count(),
                'totalRuangan' => Ruangan::count(),
                'matkulGanjil' => $matkulGanjil,
                'matkulGenap' => $matkulGenap,
                'totalProdi' => $totalProdi,
                'prodis' => $prodis,
                'validatedCount' => $validatedCount,
                'allValidated' => $allValidated,
            ]);
        }

        // KAPRODI
        $prodi = Prodi::find($user->prodi_id);

        if (!$prodi) {
            return redirect()->back()->with('error', 'Prodi tidak ditemukan');
        }

        $kelengkapan = $prodi->checkKelengkapan();

        // CEK MATA KULIAH TANPA DOSEN PENGAMPU
        $matkulTanpaDosen = $this->checkMatkulTanpaDosen($prodi->id);

        $totalKelas = Kelas::whereHas('angkatan', function ($q) use ($user) {
            $q->where('prodi_id', $user->prodi_id);
        })->count();

        // HITUNG MATA KULIAH PER SEMESTER UNTUK KAPRODI
        $matkulGanjil = MataKuliah::where('prodi_id', $user->prodi_id)->where('semester', 'ganjil')->count();
        $matkulGenap = MataKuliah::where('prodi_id', $user->prodi_id)->where('semester', 'genap')->count();

        return view('dashboardkaprodi', [
            'kelas' => Kelas::whereHas('angkatan', function ($q) use ($user) {
                $q->where('prodi_id', $user->prodi_id);
            })->latest()->take(5)->get(),
            'dosen' => Dosen::where('prodi_id', $user->prodi_id)->latest()->take(5)->get(),
            'matkul' => MataKuliah::where('prodi_id', $user->prodi_id)->latest()->take(5)->get(),
            'angkatan' => Angkatan::where('prodi_id', $user->prodi_id)->latest()->take(5)->get(),
            'totalKelas' => $totalKelas,
            'totalDosen' => Dosen::where('prodi_id', $user->prodi_id)->count(),
            'totalMatkul' => MataKuliah::where('prodi_id', $user->prodi_id)->count(),
            'matkulGanjil' => $matkulGanjil,
            'matkulGenap' => $matkulGenap,
            'prodi' => $prodi,
            'kelengkapan' => $kelengkapan,
            'is_validated' => $prodi->is_validated,
            'validated_at' => $prodi->validated_at,
            'matkulTanpaDosen' => $matkulTanpaDosen,
        ]);
    }

    /**
     * CEK MATA KULIAH YANG TIDAK MEMILIKI DOSEN PENGAMPU
     */
    private function checkMatkulTanpaDosen($prodiId)
    {
        $matkuls = MataKuliah::where('prodi_id', $prodiId)->get();
        $matkulTanpaDosen = [];

        foreach ($matkuls as $matkul) {
            $dosenIds = $matkul->dosen_id;

            if (is_string($dosenIds)) {
                $dosenIds = json_decode($dosenIds, true);
            }

            if (empty($dosenIds) || (is_array($dosenIds) && count($dosenIds) == 0)) {
                $matkulTanpaDosen[] = [
                    'id' => $matkul->id,
                    'kode' => $matkul->kode,
                    'nama' => $matkul->nama,
                    'sks' => $matkul->sks,
                    'semester_ke' => $matkul->semester_ke,
                    'semester' => $matkul->semester
                ];
            }
        }

        return $matkulTanpaDosen;
    }
}
