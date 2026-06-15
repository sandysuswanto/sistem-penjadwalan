<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Angkatan extends Model
{
    protected $fillable = ['prodi_id', 'tahun', 'is_active'];
    public function prodi()
    {
        return $this->belongsTo(Prodi::class);
    }
}
