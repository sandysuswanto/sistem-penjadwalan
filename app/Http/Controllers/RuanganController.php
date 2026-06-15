<?php
// app/Http/Controllers/RuanganController.php

namespace App\Http\Controllers;

use App\Models\Ruangan;
use App\Models\MataKuliah;
use App\Models\Prodi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class RuanganController extends Controller
{
    public function index()
    {
        $ruangans = Ruangan::with('prodi')->latest()->get();
        $prodis = Prodi::orderBy('nama')->get();
        return view('ruangan.index', compact('ruangans', 'prodis'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kode' => 'required|unique:ruangans|max:10',
            'nama' => 'required|max:100',
            'kapasitas' => 'required|integer|min:1|max:200',
            'is_lab' => 'boolean',
            'prodi_id' => 'nullable|exists:prodis,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $ruangan = Ruangan::create([
            'kode' => $request->kode,
            'nama' => $request->nama,
            'kapasitas' => $request->kapasitas,
            'is_lab' => $request->is_lab ?? false,
            'prodi_id' => $request->prodi_id ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ruangan berhasil ditambahkan',
            'data' => $ruangan->load('prodi')
        ], 200);
    }

    public function update(Request $request, Ruangan $ruangan)
    {
        $validator = Validator::make($request->all(), [
            'kode' => 'required|max:10|unique:ruangans,kode,' . $ruangan->id,
            'nama' => 'required|max:100',
            'kapasitas' => 'required|integer|min:1|max:200',
            'is_lab' => 'boolean',
            'prodi_id' => 'nullable|exists:prodis,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $ruangan->update([
            'kode' => $request->kode,
            'nama' => $request->nama,
            'kapasitas' => $request->kapasitas,
            'is_lab' => $request->is_lab ?? false,
            'prodi_id' => $request->prodi_id ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ruangan berhasil diupdate',
            'data' => $ruangan->load('prodi')
        ], 200);
    }

    public function destroy(Ruangan $ruangan)
    {
        // Cegah nonaktifkan kalau masih dipakai di mata kuliah
        if ($ruangan->is_active) {
            $used = MataKuliah::where('ruangan_id', $ruangan->id)->exists();
            if ($used) {
                return response()->json([
                    'success' => false,
                    'errors' => ['ruangan_id' => ['Ruangan masih terpakai di mata kuliah, tidak bisa dinonaktifkan.']]
                ], 409);
            }
        }

        try {
            $ruangan->is_active = !$ruangan->is_active;
            $ruangan->save();

            $status = $ruangan->is_active ? 'diaktifkan' : 'dinonaktifkan';
            return response()->json([
                'success' => true,
                'message' => "Ruangan berhasil {$status}"
            ], 200);
        } catch (\Exception $e) {
            Log::error('Gagal toggle ruangan ID ' . $ruangan->id . ': ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status: ' . $e->getMessage()
            ], 500);
        }
    }
}
