<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Slot extends Model
{
    protected $table = 'slots';

    protected $fillable = ['hari', 'slot_ke', 'jam_mulai', 'jam_selesai', 'durasi_sks', 'is_active'];

    public function getHariNamaAttribute()
    {
        $hari = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];
        return $hari[$this->hari] ?? '-';
    }

    public static function getActiveSlots()
    {
        return self::where('is_active', true)->orderBy('hari')->orderBy('slot_ke')->get();
    }

    public static function getByHari($hari)
    {
        return self::where('hari', $hari)->where('is_active', true)->orderBy('slot_ke')->get();
    }
}
