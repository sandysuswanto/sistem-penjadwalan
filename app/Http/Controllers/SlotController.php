<?php

namespace App\Http\Controllers;

use App\Models\Slot;
use App\Models\Jadwal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class SlotController extends Controller
{
    public function index()
    {
        $slots = Slot::latest()->get();
        return view('slot.index', compact('slots'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'hari' => 'required|integer|between:1,6',
            'slot_ke' => 'required|integer|between:0,11',
            'jam_mulai' => 'required|max:10',
            'jam_selesai' => 'required|max:10',
            'durasi_sks' => 'required|integer|min:1|max:3',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $exists = Slot::where('hari', $request->hari)
            ->where('slot_ke', $request->slot_ke)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Slot pada hari dan urutan tersebut sudah ada.'
            ], 409);
        }

        $slot = Slot::create([
            'hari' => $request->hari,
            'slot_ke' => $request->slot_ke,
            'jam_mulai' => $request->jam_mulai,
            'jam_selesai' => $request->jam_selesai,
            'durasi_sks' => $request->durasi_sks,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Slot berhasil ditambahkan',
            'data' => $slot
        ], 200);
    }

    public function update(Request $request, Slot $slot)
    {
        $validator = Validator::make($request->all(), [
            'hari' => 'required|integer|between:1,6',
            'slot_ke' => 'required|integer|between:0,11',
            'jam_mulai' => 'required|max:10',
            'jam_selesai' => 'required|max:10',
            'durasi_sks' => 'required|integer|min:1|max:3',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $exists = Slot::where('hari', $request->hari)
            ->where('slot_ke', $request->slot_ke)
            ->where('id', '!=', $slot->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Slot pada hari dan urutan tersebut sudah ada.'
            ], 409);
        }

        $slot->update([
            'hari' => $request->hari,
            'slot_ke' => $request->slot_ke,
            'jam_mulai' => $request->jam_mulai,
            'jam_selesai' => $request->jam_selesai,
            'durasi_sks' => $request->durasi_sks,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Slot berhasil diupdate',
            'data' => $slot
        ], 200);
    }

    public function destroy(Slot $slot)
    {
        try {
            $slot->is_active = !$slot->is_active;
            $slot->save();

            $status = $slot->is_active ? 'diaktifkan' : 'dinonaktifkan';
            return response()->json([
                'success' => true,
                'message' => "Slot berhasil {$status}"
            ], 200);
        } catch (\Exception $e) {
            Log::error('Gagal toggle slot ID ' . $slot->id . ': ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status: ' . $e->getMessage()
            ], 500);
        }
    }
}
