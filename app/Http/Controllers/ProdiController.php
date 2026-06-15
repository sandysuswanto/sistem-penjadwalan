<?php

namespace App\Http\Controllers;

use App\Models\Prodi;
use App\Models\MataKuliah;
use App\Models\Dosen;
use App\Models\Kelas;
use App\Models\Angkatan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProdiController extends Controller
{
    public function index()
    {
        $prodis = Prodi::latest()->get();
        return view('prodi.index', compact('prodis'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kode' => 'required|unique:prodis|max:10',
            'nama' => 'required|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $prodi = Prodi::create($request->only('kode', 'nama'));

        return response()->json([
            'message' => 'Prodi berhasil ditambahkan',
            'data' => $prodi,
        ], 200);
    }

    public function update(Request $request, Prodi $prodi)
    {
        $validator = Validator::make($request->all(), [
            'kode' => 'required|max:10|unique:prodis,kode,' . $prodi->id,
            'nama' => 'required|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $prodi->update($request->only('kode', 'nama'));

        return response()->json([
            'message' => 'Prodi berhasil diupdate',
            'data' => $prodi,
        ], 200);
    }

  

    public function edit(Prodi $prodi)
    {
        return response()->json([
            'id' => $prodi->id,
            'kode' => $prodi->kode,
            'nama' => $prodi->nama,
            'is_validated' => $prodi->is_validated,
            'validated_at' => $prodi->validated_at,
            'validation_notes' => $prodi->validation_notes,
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

            // Parse dosen_id jika string JSON
            if (is_string($dosenIds)) {
                $dosenIds = json_decode($dosenIds, true);
            }

            // Jika tidak ada dosen (empty array atau null)
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

    /**
     * VALIDASI DATA OLEH KAPRODI
     */
    public function validateData(Request $request)
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'kaprodi') {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses');
        }

        $prodi = Prodi::find($user->prodi_id);

        if (!$prodi) {
            return redirect()->back()->with('error', 'Prodi tidak ditemukan');
        }

        // [NEW] CEK MATA KULIAH TANPA DOSEN PENGAMPU
        $matkulTanpaDosen = $this->checkMatkulTanpaDosen($prodi->id);

        if (!empty($matkulTanpaDosen)) {
            $errorMsg = "❌ TIDAK BISA VALIDASI! Terdapat " . count($matkulTanpaDosen) . " mata kuliah tanpa dosen pengampu:\n\n";
            foreach ($matkulTanpaDosen as $mk) {
                $errorMsg .= "- {$mk['kode']} - {$mk['nama']} (Semester {$mk['semester_ke']})\n";
            }
            $errorMsg .= "\nSilakan tambahkan dosen pengampu terlebih dahulu.";

            return redirect()->back()->with('error', $errorMsg);
        }

        // Cek kelengkapan data
        $mataKuliahCount = MataKuliah::where('prodi_id', $prodi->id)->count();
        $dosenCount = Dosen::where('prodi_id', $prodi->id)->count();
        $kelasCount = Kelas::whereHas('angkatan', function ($q) use ($prodi) {
            $q->where('prodi_id', $prodi->id);
        })->count();
        $angkatanCount = Angkatan::where('prodi_id', $prodi->id)->count();

        $isComplete = $mataKuliahCount > 0 && $dosenCount > 0 && $kelasCount > 0 && $angkatanCount > 0;

        if (!$isComplete) {
            $missingFields = [];
            if ($mataKuliahCount == 0) $missingFields[] = 'Mata Kuliah';
            if ($dosenCount == 0) $missingFields[] = 'Dosen';
            if ($kelasCount == 0) $missingFields[] = 'Kelas';
            if ($angkatanCount == 0) $missingFields[] = 'Angkatan';

            return redirect()->back()->with('error', '❌ Data belum lengkap! Silakan tambahkan: ' . implode(', ', $missingFields));
        }

        $prodi->update([
            'is_validated' => true,
            'validated_at' => now(),
            'validation_notes' => $request->notes ?? 'Validasi oleh Kaprodi'
        ]);

        return redirect()->back()->with('success', '✅ Validasi berhasil! Data prodi telah divalidasi.');
    }

    /**
     * BATALKAN VALIDASI
     */
    public function unvalidateData(Request $request)
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'kaprodi') {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses');
        }

        $prodi = Prodi::find($user->prodi_id);

        if (!$prodi) {
            return redirect()->back()->with('error', 'Prodi tidak ditemukan');
        }

        $prodi->update([
            'is_validated' => false,
            'validated_at' => null,
            'validation_notes' => $request->notes ?? 'Validasi dibatalkan'
        ]);

        return redirect()->back()->with('success', '✅ Validasi dibatalkan. Data prodi dapat diedit kembali.');
    }
}
