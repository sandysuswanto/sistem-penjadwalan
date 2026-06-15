<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Slot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class DosenController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (!$user) return redirect()->route('login');

        // Grup prodi yang berbagi dosen (IF dan SI)
        $sharedProdiIds = Prodi::whereIn('kode', ['IF', 'SIS'])->pluck('id')->toArray();

        $dosens = Dosen::with('prodi');
        if ($user->role === 'kaprodi') {
            $dosens->where(function ($q) use ($user, $sharedProdiIds) {
                $q->where('prodi_id', $user->prodi_id);
                if (in_array($user->prodi_id, $sharedProdiIds)) {
                    $q->orWhereIn('prodi_id', $sharedProdiIds);
                }
            });
            $prodis = Prodi::where('id', $user->prodi_id)->get();
        } else {
            $dosens->whereNull('prodi_id');
            $prodis = Prodi::all();
        }

        $slots = Slot::getActiveSlots();

        return view('dosen.index', compact('dosens', 'prodis', 'slots'))->with('dosens', $dosens->latest()->get());
    }

    private function isProdiLocked()
    {
        $user = Auth::user();
        if ($user->role === 'kaprodi' && $user->prodi && $user->prodi->is_validated) {
            return true;
        }
        return false;
    }

    private function validateConsecutiveSlots($slotIds)
    {
        if (empty($slotIds)) return null;

        $slots = Slot::whereIn('id', $slotIds)->orderBy('hari')->orderBy('slot_ke')->get();
        $grouped = $slots->groupBy('hari');

        foreach ($grouped as $hari => $daySlots) {
            $slotKes = $daySlots->pluck('slot_ke')->values()->toArray();
            for ($i = 1; $i < count($slotKes); $i++) {
                if ($slotKes[$i] != $slotKes[$i - 1] + 1) {
                    $dayName = $daySlots->first()->hari_nama;
                    $gapSlot = Slot::where('hari', $hari)->where('slot_ke', $slotKes[$i - 1] + 1)->first();
                    $gapName = $gapSlot ? "{$gapSlot->jam_mulai}-{$gapSlot->jam_selesai}" : "slot ke-" . ($slotKes[$i - 1] + 1);
                    return "Slot {$dayName} harus berurutan. Ada slot {$gapName} yang terlewat.";
                }
            }
        }

        return null;
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
            'prodi_id' => 'nullable|exists:prodis,id',
            'nidn'     => 'required|unique:dosens',
            'nama'     => 'required',
            'slot_tersedia' => 'nullable|array',
            'slot_tersedia.*' => 'integer|exists:slots,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $slotTerpilih = $request->slot_tersedia ?? [];

        $slotError = $this->validateConsecutiveSlots($slotTerpilih);
        if ($slotError) {
            return response()->json([
                'success' => false,
                'errors' => ['slot_tersedia' => [$slotError]]
            ], 422);
        }

        foreach ($slotTerpilih as $slotId) {
            $jumlahDosen = Dosen::whereRaw('JSON_CONTAINS(slot_tersedia, ?)', [json_encode($slotId)])->count();
            if ($jumlahDosen >= 50) {
                $slot = Slot::find($slotId);
                return response()->json([
                    'success' => false,
                    'errors' => [
                        'slot_tersedia' => ["Slot {$slot->hari_nama} {$slot->jam_mulai}-{$slot->jam_selesai} sudah penuh (maksimal 50 dosen)."]
                    ]
                ], 422);
            }
        }

        $dosen = Dosen::create([
            'prodi_id'       => $request->prodi_id,
            'nidn'           => $request->nidn,
            'nama'           => $request->nama,
            'slot_tersedia'  => $slotTerpilih,
        ]);

        $dosen->load('prodi');

        return response()->json([
            'success' => true,
            'message' => 'Dosen berhasil ditambahkan',
            'data' => $dosen
        ], 200);
    }

    public function update(Request $request, Dosen $dosen)
    {
        if ($this->isProdiLocked()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak dapat diubah karena prodi sudah divalidasi. Batalkan validasi terlebih dahulu.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'prodi_id' => 'nullable|exists:prodis,id',
            'nidn'     => 'required|unique:dosens,nidn,' . $dosen->id,
            'nama'     => 'required',
            'slot_tersedia' => 'nullable|array',
            'slot_tersedia.*' => 'integer|exists:slots,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $slotTerpilih = $request->slot_tersedia ?? [];

        $slotError = $this->validateConsecutiveSlots($slotTerpilih);
        if ($slotError) {
            return response()->json([
                'success' => false,
                'errors' => ['slot_tersedia' => [$slotError]]
            ], 422);
        }

        foreach ($slotTerpilih as $slotId) {
            $jumlahDosen = Dosen::whereRaw('JSON_CONTAINS(slot_tersedia, ?)', [json_encode($slotId)])
                ->where('id', '!=', $dosen->id)
                ->count();
            if ($jumlahDosen >= 50) {
                $slot = Slot::find($slotId);
                return response()->json([
                    'success' => false,
                    'errors' => [
                        'slot_tersedia' => ["Slot {$slot->hari_nama} {$slot->jam_mulai}-{$slot->jam_selesai} sudah penuh (maksimal 50 dosen)."]
                    ]
                ], 422);
            }
        }

        $dosen->update([
            'prodi_id'       => $request->prodi_id,
            'nidn'           => $request->nidn,
            'nama'           => $request->nama,
            'slot_tersedia'  => $slotTerpilih,
        ]);

        $dosen->load('prodi');

        return response()->json([
            'success' => true,
            'message' => 'Dosen berhasil diupdate',
            'data' => $dosen
        ], 200);
    }

    public function destroy(Dosen $dosen)
    {
        if ($this->isProdiLocked()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak dapat diubah karena prodi sudah divalidasi. Batalkan validasi terlebih dahulu.'
            ], 403);
        }

        // Cegah nonaktifkan kalau masih dipakai di mata kuliah
        if ($dosen->is_active) {
            $semuaMatkul = MataKuliah::all();
            $dipakai = false;
            foreach ($semuaMatkul as $mk) {
                $dosenIds = $mk->dosen_id ?? [];
                if (in_array($dosen->id, $dosenIds)) {
                    $dipakai = true;
                    break;
                }
            }
            if ($dipakai) {
                return response()->json([
                    'success' => false,
                    'errors' => ['dosen_id' => ['Dosen masih terpakai di mata kuliah, tidak bisa dinonaktifkan.']]
                ], 409);
            }
        }

        try {
            $dosen->is_active = !$dosen->is_active;
            $dosen->save();

            $status = $dosen->is_active ? 'diaktifkan' : 'dinonaktifkan';
            return response()->json([
                'success' => true,
                'message' => "Dosen berhasil {$status}"
            ], 200);
        } catch (\Exception $e) {
            Log::error('Gagal toggle dosen ID ' . $dosen->id . ': ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status: ' . $e->getMessage()
            ], 500);
        }
    }
}
