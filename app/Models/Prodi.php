<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prodi extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode',
        'nama',
        'is_validated',
        'validated_at',
        'validation_notes',
        'is_active',
    ];

    protected $casts = [
        'is_validated' => 'boolean',
        'is_active' => 'boolean',
        'validated_at' => 'datetime',
    ];

    public function checkKelengkapan()
    {
        $data = [
            'mata_kuliah_count' => MataKuliah::where('prodi_id', $this->id)->count(),
            'dosen_count' => Dosen::where('prodi_id', $this->id)->count(),
            'kelas_count' => Kelas::whereHas('angkatan', function ($q) {
                $q->where('prodi_id', $this->id);
            })->count(),
            'angkatan_count' => Angkatan::where('prodi_id', $this->id)->count(),
        ];

        $isComplete = $data['mata_kuliah_count'] > 0 &&
            $data['dosen_count'] > 0 &&
            $data['kelas_count'] > 0 &&
            $data['angkatan_count'] > 0;

        return [
            'is_complete' => $isComplete,
            'data' => $data,
        ];
    }

    public function user()
    {
        return $this->hasOne(User::class, 'prodi_id');
    }

    public function angkatans()
    {
        return $this->hasMany(Angkatan::class);
    }

    public function dosens()
    {
        return $this->hasMany(Dosen::class);
    }

    public function mataKuliahs()
    {
        return $this->hasMany(MataKuliah::class);
    }
}
