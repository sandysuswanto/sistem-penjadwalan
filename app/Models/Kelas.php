<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kelas extends Model
{
    protected $table = 'kelas';

    protected $fillable = ['angkatan_id', 'nama', 'kapasitas', 'is_active'];

    public function angkatan()
    {
        return $this->belongsTo(Angkatan::class);
    }

    public function jadwals()
    {
        return $this->hasMany(Jadwal::class);
    }
}
