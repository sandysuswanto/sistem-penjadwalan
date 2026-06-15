<?php

namespace App\Http\Controllers;

use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Dosen;
use App\Models\Ruangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class MataKuliahController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        $query = MataKuliah::with(['prodi', 'ruangan']);

        if ($request->filled('semester_ke')) {
            $query->where('semester_ke', $request->semester_ke);
        }

        if ($request->filled('semester')) {
            $query->where('semester', $request->semester);
        }

        // ========== FILTER DOSEN (LIKE '%2%') ==========
        if ($request->filled('dosen_id')) {
            $dosenId = $request->dosen_id;
            $query->where('dosen_id', 'LIKE', '%' . $dosenId . '%');
        }
        // ===============================================

        if ($user->role === 'kaprodi') {
            $query->where('prodi_id', $user->prodi_id);
            $prodis = Prodi::where('id', $user->prodi_id)->get();
            $ruangans = Ruangan::all();
        } else {
            $prodis = Prodi::all();
            $ruangans = Ruangan::all();
        }

        $dosens = Dosen::with('prodi')->get();

        // Tambah data SKS per semester untuk setiap dosen
        $semesters = ['ganjil', 'genap'];
        $sksPerDosen = [];
        foreach ($semesters as $sem) {
            $matkulsSem = MataKuliah::where('semester', $sem)->get();
            foreach ($dosens as $dosen) {
                $total = 0;
                foreach ($matkulsSem as $mk) {
                    $ids = $mk->dosen_id;
                    if (is_string($ids)) $ids = json_decode($ids, true);
                    if (is_array($ids) && in_array($dosen->id, $ids)) {
                        $total += $mk->sks;
                    }
                }
                $sksPerDosen[$dosen->id][$sem] = $total;
            }
        }
        foreach ($dosens as $dosen) {
            $dosen->total_sks_ganjil = $sksPerDosen[$dosen->id]['ganjil'] ?? 0;
            $dosen->total_sks_genap = $sksPerDosen[$dosen->id]['genap'] ?? 0;
        }

        $matkuls = $query->latest()->get();

        // Grup prodi yang berbagi dosen (IF dan SI)
        $sharedProdiIds = Prodi::whereIn('kode', ['IF', 'SIS'])->pluck('id')->toArray();

        return view('matakuliah.index', compact('matkuls', 'prodis', 'dosens', 'ruangans', 'sharedProdiIds'));
    }
    /**
     * Hitung total SKS yang diampu seorang dosen di semester tertentu
     */
    private function getTotalSksDosen($dosenId, $semester, $exceptMatkulId = null)
    {
        $query = MataKuliah::whereRaw('JSON_CONTAINS(dosen_id, ?)', [json_encode($dosenId)])
            ->where('semester', $semester);

        if ($exceptMatkulId) {
            $query->where('id', '!=', $exceptMatkulId);
        }

        return (int) $query->sum('sks');
    }

    /**
     * Dapatkan jumlah slot tersedia dosen dari tabel dosens
     */
    private function getJumlahSlotTersediaDosen($dosenId)
    {
        $dosen = Dosen::find($dosenId);
        if (!$dosen) {
            return 0;
        }

        $slotTersedia = $dosen->slot_tersedia;

        if (empty($slotTersedia)) {
            return 12;
        }

        if (is_array($slotTersedia)) {
            return count($slotTersedia);
        }

        if (is_string($slotTersedia)) {
            $decoded = json_decode($slotTersedia, true);
            return is_array($decoded) ? count($decoded) : 0;
        }

        return 0;
    }

    /**
     * Cek apakah dosen melebihi batas slot yang tersedia
     */
    private function isDosenOverCapacity($dosenId, $semester, $newSks, $exceptMatkulId = null)
    {
        $dosen = Dosen::find($dosenId);
        if (!$dosen) return false;

        $maxSlot = $dosen->getJumlahSlotTersediaAttribute();

        // Jika tidak ada batasan (slot_tersedia kosong), return false (tidak over)
        if ($maxSlot === null) {
            return false;
        }

        $currentSks = $dosen->getTotalSksDiampu($semester, $exceptMatkulId);
        $totalSks = $currentSks + $newSks;

        return $totalSks > $maxSlot;
    }

    private function isProdiLocked()
    {
        $user = Auth::user();
        if ($user->role === 'kaprodi' && $user->prodi && $user->prodi->is_validated) {
            return true;
        }
        return false;
    }

    public function store(Request $request)
    {
        if ($this->isProdiLocked()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak dapat ditambahkan karena prodi sudah divalidasi. Batalkan validasi terlebih dahulu.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'kode' => 'required|unique:mata_kuliahs|max:20',
            'nama' => 'required|max:200',
            'prodi_id' => 'required|exists:prodis,id',
            'sks' => 'required|in:2,3',
            'semester_ke' => 'required|integer|min:1|max:8',
            'semester' => 'required|in:ganjil,genap',
            'dosen_id' => 'nullable|array',
            'dosen_id.*' => 'exists:dosens,id',
            'ruangan_id' => 'nullable|exists:ruangans,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $dosenIds = $request->dosen_id ?? [];
        $sks = (int) $request->sks;
        $semester = $request->semester;
        $errors = [];

        // VALIDASI: Cek kapasitas setiap dosen (hanya jika ada dosen yang dipilih)
        foreach ($dosenIds as $dosenId) {
            if ($this->isDosenOverCapacity($dosenId, $semester, $sks)) {
                $dosen = Dosen::find($dosenId);
                $errors["dosen_id"] = [
                    "❌ Dosen {$dosen->nama} slotnya terbatas, Tidak bisa menambah mata kuliah dengan SKS {$sks}."
                ];
                break;
            }
        }

        if (!empty($errors)) {
            return response()->json(['success' => false, 'errors' => $errors], 422);
        }

        $data = $request->all();
        $data['dosen_id'] = !empty($dosenIds) ? json_encode($dosenIds) : json_encode([]);

        $matkul = MataKuliah::create($data);
        $matkul->load(['prodi', 'ruangan']);

        return response()->json([
            'success' => true,
            'message' => 'Mata kuliah berhasil ditambahkan',
            'data' => $matkul
        ], 200);
    }

    public function update(Request $request, MataKuliah $matakuliah)
    {
        if ($this->isProdiLocked()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak dapat diubah karena prodi sudah divalidasi. Batalkan validasi terlebih dahulu.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'kode' => 'required|max:20|unique:mata_kuliahs,kode,' . $matakuliah->id,
            'nama' => 'required|max:200',
            'prodi_id' => 'required|exists:prodis,id',
            'sks' => 'required|in:2,3',
            'semester_ke' => 'required|integer|min:1|max:8',
            'semester' => 'required|in:ganjil,genap',
            'dosen_id' => 'nullable|array',
            'dosen_id.*' => 'exists:dosens,id',
            'ruangan_id' => 'nullable|exists:ruangans,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $dosenIds = $request->dosen_id ?? [];
        $sks = (int) $request->sks;
        $semester = $request->semester;
        $errors = [];

        // VALIDASI: Cek kapasitas setiap dosen (hanya jika ada dosen yang dipilih)
        foreach ($dosenIds as $dosenId) {
            if ($this->isDosenOverCapacity($dosenId, $semester, $sks, $matakuliah->id)) {
                $dosen = Dosen::find($dosenId);
                $errors["dosen_id"] = [
                    "❌ Dosen {$dosen->nama} slot terbatas. Tidak bisa mengupdate mata kuliah dengan SKS {$sks}."
                ];
                break;
            }
        }

        if (!empty($errors)) {
            return response()->json(['success' => false, 'errors' => $errors], 422);
        }

        $data = $request->all();
        $data['dosen_id'] = !empty($dosenIds) ? json_encode($dosenIds) : json_encode([]);

        $matakuliah->update($data);
        $matakuliah->load(['prodi', 'ruangan']);

        return response()->json([
            'success' => true,
            'message' => 'Mata kuliah berhasil diupdate',
            'data' => $matakuliah
        ], 200);
    }

    public function destroy(MataKuliah $matakuliah)
    {
        if ($this->isProdiLocked()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak dapat diubah karena prodi sudah divalidasi. Batalkan validasi terlebih dahulu.'
            ], 403);
        }

        try {
            $matakuliah->is_active = !$matakuliah->is_active;
            $matakuliah->save();

            $status = $matakuliah->is_active ? 'diaktifkan' : 'dinonaktifkan';
            return response()->json([
                'success' => true,
                'message' => "Mata kuliah berhasil {$status}"
            ], 200);
        } catch (\Exception $e) {
            Log::error('Gagal toggle matkul ID ' . $matakuliah->id . ': ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Get statistik SKS per dosen berdasarkan slot tersedia
     */
    public function getDosenSksStatistik(Request $request)
    {
        $semester = $request->get('semester', 'ganjil');
        $dosens = Dosen::with('prodi')->get();
        $statistik = [];

        foreach ($dosens as $dosen) {
            $totalSks = $this->getTotalSksDosen($dosen->id, $semester);
            $maxSlot = $this->getJumlahSlotTersediaDosen($dosen->id);
            $statistik[] = [
                'id' => $dosen->id,
                'nama' => $dosen->nama,
                'prodi' => $dosen->prodi->nama ?? '-',
                'slot_tersedia' => $maxSlot,
                'slot_detail' => $dosen->slot_tersedia,
                'total_sks_diampu' => $totalSks,
                'sisa_slot' => $maxSlot - $totalSks,
                'is_over' => $totalSks > $maxSlot,
                'is_full' => $totalSks >= $maxSlot,
            ];
        }

        return response()->json($statistik);
    }

    /**
     * API: Cek SKS dosen sebelum submit (AJAX)
     */
    public function cekSksDosen(Request $request)
    {
        $dosenIds = $request->dosen_ids;
        $sks = (int) $request->sks;
        $semester = $request->semester;
        $exceptId = $request->except_id;

        $results = [];
        foreach ($dosenIds as $dosenId) {
            $currentSks = $this->getTotalSksDosen($dosenId, $semester, $exceptId);
            $totalSks = $currentSks + $sks;
            $maxSlot = $this->getJumlahSlotTersediaDosen($dosenId);
            $dosen = Dosen::find($dosenId);

            $results[] = [
                'dosen_id' => $dosenId,
                'dosen_nama' => $dosen->nama ?? '-',
                'current_sks' => $currentSks,
                'new_sks' => $sks,
                'total_sks' => $totalSks,
                'max_slot' => $maxSlot,
                'is_valid' => $totalSks <= $maxSlot,
                'sisa_slot' => $maxSlot - $totalSks
            ];
        }

        return response()->json($results);
    }
}
