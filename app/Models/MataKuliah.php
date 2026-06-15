<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MataKuliah extends Model
{
    protected $table = 'mata_kuliahs';

    protected $fillable = ['kode', 'nama', 'prodi_id', 'sks', 'semester_ke', 'semester', 'dosen_id', 'ruangan_id', 'is_active'];

    protected $casts = [
        'dosen_id' => 'array',
    ];

    public function prodi()
    {
        return $this->belongsTo(Prodi::class);
    }

    public function dosens()
    {
        if (empty($this->dosen_id)) {
            return collect();
        }
        return Dosen::whereIn('id', $this->dosen_id)->get();
    }

    public function ruangan()
    {
        return $this->belongsTo(Ruangan::class);
    }

    /**
     * Get nama-nama dosen pengampu
     */
    public function getDosenNamesAttribute()
    {
        $ids = $this->dosen_id ?? [];
        if (empty($ids)) return '-';

        $names = Dosen::whereIn('id', $ids)->pluck('nama')->toArray();
        return implode(', ', $names);
    }

    /**
     * Cek apakah dosen cukup kapasitas
     */
    public function isDosenCapacityValid()
    {
        $dosenIds = $this->dosen_id ?? [];
        if (empty($dosenIds)) return true;

        foreach ($dosenIds as $dosenId) {
            $dosen = Dosen::find($dosenId);
            if ($dosen && !$dosen->canAmbilSks($this->sks, $this->semester, $this->id)) {
                return false;
            }
        }
        return true;
    }
}
