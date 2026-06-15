<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Angkatan;
use App\Models\Ruangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class KelasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // KAPRODI
        if ($user->role === 'kaprodi') {

            $kelasList = Kelas::with([
                'angkatan',
                'angkatan.prodi'
            ])
                ->whereHas('angkatan', function ($q) use ($user) {
                    $q->where('prodi_id', $user->prodi_id);
                })
                ->orderBy('id', 'desc')
                ->get();

            $angkatans = Angkatan::with('prodi')
                ->where('prodi_id', $user->prodi_id)
                ->orderBy('tahun', 'desc')
                ->get();
        }

        // ADMIN
        else {

            $kelasList = Kelas::with([
                'angkatan',
                'angkatan.prodi'
            ])
                ->orderBy('id', 'desc')
                ->get();

            $angkatans = Angkatan::with('prodi')
                ->orderBy('tahun', 'desc')
                ->get();
        }

        return view('kelas.index', compact(
            'kelasList',
            'angkatans'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
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
                'message' => 'Data tidak dapat diubah karena prodi sudah divalidasi. Batalkan validasi terlebih dahulu.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [

            'angkatan_id' => 'required|exists:angkatans,id',

            'nama' => [
                'required',
                'max:10',
                Rule::unique('kelas')->where(function ($query) use ($request) {
                    return $query->where('angkatan_id', $request->angkatan_id);
                }),
            ],

            'kapasitas' => 'required|integer|min:1|max:200',

        ], [

            'nama.unique' => 'Kelas sudah ada pada angkatan ini.',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Ambil data angkatan + prodi
        $angkatan = Angkatan::with('prodi')
            ->find($request->angkatan_id);

        // Validasi kapasitas terhadap ruangan
        if ($angkatan) {

            // Ruangan milik prodi
            $maxKapasitasRuanganProdi = Ruangan::where(
                'prodi_id',
                $angkatan->prodi_id
            )->max('kapasitas') ?? 0;

            // Ruangan umum
            $maxKapasitasRuanganUmum = Ruangan::whereNull(
                'prodi_id'
            )->max('kapasitas') ?? 0;

            // Maks kapasitas tersedia
            $maxKapasitasTersedia = max(
                $maxKapasitasRuanganProdi,
                $maxKapasitasRuanganUmum
            );

            // Validasi
            if ($request->kapasitas > $maxKapasitasTersedia) {

                return response()->json([
                    'success' => false,
                    'errors' => [
                        'kapasitas' => [
                            "Kapasitas kelas tidak boleh melebihi kapasitas ruangan tertinggi. Maksimal kapasitas tersedia: {$maxKapasitasTersedia} mahasiswa."
                        ]
                    ]
                ], 422);
            }
        }

        // Simpan data
        $kelas = Kelas::create([
            'angkatan_id' => $request->angkatan_id,
            'nama' => strtoupper($request->nama),
            'kapasitas' => $request->kapasitas,
        ]);

        $kelas->load([
            'angkatan',
            'angkatan.prodi'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kelas berhasil ditambahkan',
            'data' => $kelas
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    /**
     * Update the specified resource in storage.
     */
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        if ($this->isProdiLocked()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak dapat diubah karena prodi sudah divalidasi. Batalkan validasi terlebih dahulu.'
            ], 403);
        }

        // CARI DULU KELASNYA
        $kelas = Kelas::find($id);

        if (!$kelas) {
            return response()->json([
                'success' => false,
                'message' => 'Data kelas tidak ditemukan'
            ], 404);
        }

        Log::info('UPDATE KELAS', [
            'id' => $id,
            'request_data' => $request->all(),
            'kelas_ditemukan' => $kelas->toArray()
        ]);

        $validator = Validator::make($request->all(), [
            'angkatan_id' => 'required|exists:angkatans,id',
            'nama' => 'required|max:10',
            'kapasitas' => 'required|integer|min:1|max:200',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Ambil angkatan + prodi
        $angkatan = Angkatan::with('prodi')->find($request->angkatan_id);

        // Validasi kapasitas
        if ($angkatan) {
            $maxKapasitasRuanganProdi = Ruangan::where('prodi_id', $angkatan->prodi_id)->max('kapasitas') ?? 0;
            $maxKapasitasRuanganUmum = Ruangan::whereNull('prodi_id')->max('kapasitas') ?? 0;
            $maxKapasitasTersedia = max($maxKapasitasRuanganProdi, $maxKapasitasRuanganUmum);

            if ($request->kapasitas > $maxKapasitasTersedia) {
                return response()->json([
                    'success' => false,
                    'errors' => [
                        'kapasitas' => ["Kapasitas maksimal tersedia: {$maxKapasitasTersedia} mahasiswa."]
                    ]
                ], 422);
            }
        }

        // Update data
        $kelas->angkatan_id = $request->angkatan_id;
        $kelas->nama = strtoupper($request->nama);
        $kelas->kapasitas = (int) $request->kapasitas;
        $kelas->save();

        $kelas->load(['angkatan', 'angkatan.prodi']);

        return response()->json([
            'success' => true,
            'message' => 'Kelas berhasil diupdate',
            'data' => $kelas
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        if ($this->isProdiLocked()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak dapat diubah karena prodi sudah divalidasi. Batalkan validasi terlebih dahulu.'
            ], 403);
        }

        try {
            $kelas = Kelas::find($id);

            if (!$kelas) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data kelas tidak ditemukan'
                ], 404);
            }

            $kelas->is_active = !$kelas->is_active;
            $kelas->save();

            $status = $kelas->is_active ? 'diaktifkan' : 'dinonaktifkan';
            return response()->json([
                'success' => true,
                'message' => "Kelas berhasil {$status}"
            ], 200);
        } catch (\Exception $e) {
            Log::error('Gagal toggle kelas ID ' . $id . ': ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * API Max Kapasitas Ruangan
     */
    public function getMaxRuanganCapacity(Request $request)
    {
        $prodiId = $request->prodi_id;

        // Jika prodi kosong
        if (!$prodiId) {

            $maxUmum = Ruangan::whereNull('prodi_id')
                ->max('kapasitas') ?? 0;

            return response()->json([
                'max_kapasitas_prodi' => 0,
                'max_kapasitas_umum' => $maxUmum,
                'max_kapasitas_tersedia' => $maxUmum,
                'has_own_rooms' => false
            ]);
        }

        // Kapasitas ruangan prodi
        $maxKapasitasProdi = Ruangan::where(
            'prodi_id',
            $prodiId
        )->max('kapasitas') ?? 0;

        // Kapasitas ruangan umum
        $maxKapasitasUmum = Ruangan::whereNull(
            'prodi_id'
        )->max('kapasitas') ?? 0;

        // Maks tersedia
        $maxKapasitasTersedia = max(
            $maxKapasitasProdi,
            $maxKapasitasUmum
        );

        return response()->json([

            'max_kapasitas_prodi' => $maxKapasitasProdi,

            'max_kapasitas_umum' => $maxKapasitasUmum,

            'max_kapasitas_tersedia' => $maxKapasitasTersedia,

            'has_own_rooms' => $maxKapasitasProdi > 0

        ]);
    }
}
