<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JadwalRamadan extends Model
{
    use HasFactory;

    protected $table = 'jadwal_ramadan';

    protected $fillable = [
        'jadwal_asli_id',
        'mata_kuliah_id',
        'kelas_id',
        'ruangan_id',
        'dosen_id',
        'hari',
        'jam_mulai',
        'jam_selesai',
        'semester',
        'tahun_ajaran',
        'durasi_per_sks'
    ];

    public function jadwalAsli()
    {
        return $this->belongsTo(Jadwal::class, 'jadwal_asli_id');
    }

    public function mataKuliah()
    {
        return $this->belongsTo(MataKuliah::class);
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class);
    }

    public function ruangan()
    {
        return $this->belongsTo(Ruangan::class);
    }

    public function dosen()
    {
        return $this->belongsTo(Dosen::class);
    }
}
