<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jadwal extends Model
{
    protected $fillable = ['mata_kuliah_id', 'kelas_id', 'dosen_id', 'ruangan_id', 'hari', 'slot_mulai'];
    public function mataKuliah()
    {
        return $this->belongsTo(MataKuliah::class);
    }
    public function kelas()
    {
        return $this->belongsTo(Kelas::class);
    }
    public function dosen()
    {
        return $this->belongsTo(Dosen::class);
    }
    public function ruangan()
    {
        return $this->belongsTo(Ruangan::class);
    }
}
