<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenukaranJadwalLog extends Model
{
    protected $table = 'penukaran_jadwal_logs';

    protected $fillable = [
        'jadwal_1_id',
        'jadwal_2_id',
        'admin_id',
        'keterangan',
        'data_sebelum_tukar',
        'data_sesudah_tukar'
    ];

    protected $casts = [
        'data_sebelum_tukar' => 'array',
        'data_sesudah_tukar' => 'array'
    ];

    public function jadwal1()
    {
        return $this->belongsTo(Jadwal::class, 'jadwal_1_id');
    }

    public function jadwal2()
    {
        return $this->belongsTo(Jadwal::class, 'jadwal_2_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
