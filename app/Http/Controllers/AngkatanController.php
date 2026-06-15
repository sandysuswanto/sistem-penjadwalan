<?php

namespace App\Http\Controllers;

use App\Models\Angkatan;
use App\Models\Kelas;
use App\Models\Prodi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AngkatanController extends Controller
{
    public function index()
    {
        $angkatans = Angkatan::with('prodi')
            ->orderByRaw("FIELD(prodi_id, (SELECT id FROM prodis WHERE kode='IF'), (SELECT id FROM prodis WHERE kode='SI'), (SELECT id FROM prodis WHERE kode='SIS'))")
            ->orderBy('tahun', 'desc')
            ->get();
        $prodis = Prodi::all(); // tambahkan ini
        return view('angkatan.index', compact('angkatans', 'prodis'));
    }

    public function create()
    {
        $prodis = Prodi::all();
        return view('angkatan.create', compact('prodis'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'prodi_id' => 'required|exists:prodis,id',
            'tahun' => 'required|integer|min:2000|max:2100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $angkatan = Angkatan::create($request->only('prodi_id', 'tahun'));
        return response()->json(['message' => 'Angkatan berhasil ditambahkan', 'data' => $angkatan->load('prodi')], 200);
    }

    public function update(Request $request, Angkatan $angkatan)
    {
        $validator = Validator::make($request->all(), [
            'prodi_id' => 'required|exists:prodis,id',
            'tahun' => 'required|integer|min:2000|max:2100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $angkatan->update($request->only('prodi_id', 'tahun'));
        return response()->json(['message' => 'Angkatan berhasil diupdate', 'data' => $angkatan->load('prodi')], 200);
    }
}
