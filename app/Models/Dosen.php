<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dosen extends Model
{
    protected $table = 'dosens';

    protected $fillable = ['prodi_id', 'nidn', 'nama', 'slot_tersedia', 'is_active'];

    protected $casts = [
        'slot_tersedia' => 'array',
        'is_active' => 'boolean',
    ];

    protected $appends = ['jumlah_slot_tersedia', 'sisa_sks'];

    public function prodi()
    {
        return $this->belongsTo(Prodi::class);
    }

    /**
     * Get slots sebagai collection
     */
    public function getSlotsAttribute()
    {
        $slotIds = $this->slot_tersedia ?? [];

        if (empty($slotIds)) {
            return collect();
        }

        if (is_string($slotIds)) {
            $slotIds = json_decode($slotIds, true);
        }

        if (is_array($slotIds) && isset($slotIds[0]) && is_array($slotIds[0])) {
            $flattened = [];
            foreach ($slotIds as $item) {
                if (isset($item['id'])) {
                    $flattened[] = $item['id'];
                } elseif (isset($item[0])) {
                    $flattened = array_merge($flattened, $item);
                }
            }
            $slotIds = $flattened;
        }

        $slotIds = array_map('intval', (array) $slotIds);
        $slotIds = array_unique($slotIds);

        if (empty($slotIds)) {
            return collect();
        }

        return Slot::whereIn('id', $slotIds)->orderBy('hari')->orderBy('slot_ke')->get();
    }

    /**
     * Get jumlah slot tersedia (maksimal SKS)
     */
    public function getJumlahSlotTersediaAttribute()
    {
        $slotTersedia = $this->slot_tersedia;

        if (empty($slotTersedia)) {
            return null;
        }

        if (is_array($slotTersedia)) {
            return count($slotTersedia);
        }

        if (is_string($slotTersedia)) {
            $decoded = json_decode($slotTersedia, true);
            return is_array($decoded) ? count($decoded) : null;
        }

        return null;
    }

    /**
     * Get maksimal SKS
     */
    public function getMaxSksAttribute()
    {
        return $this->jumlah_slot_tersedia;
    }

    /**
     * Cek apakah dosen tersedia di slot tertentu (berdasarkan ID slot)
     */
    public function isAvailableAtSlotId($slotId)
    {
        $slotTersedia = $this->slot_tersedia ?? [];

        if (empty($slotTersedia)) {
            return true;
        }

        return in_array($slotId, $slotTersedia);
    }

    /**
     * Cek apakah dosen tersedia di hari dan slot_ke tertentu
     */
    public function isAvailableAt($hari, $slotKe)
    {
        $slot = Slot::where('hari', $hari)->where('slot_ke', $slotKe)->first();
        if (!$slot) return false;

        return $this->isAvailableAtSlotId($slot->id);
    }

    /**
     * Get total SKS yang diampu (SEMUA SEMESTER)
     */
    public function getTotalSksDiampu($semester = null, $exceptMatkulId = null)
    {
        $query = MataKuliah::query();

        if ($semester) {
            $query->where('semester', $semester);
        }

        if ($exceptMatkulId) {
            $query->where('id', '!=', $exceptMatkulId);
        }

        $allMatkuls = $query->get();
        $totalSks = 0;

        foreach ($allMatkuls as $matkul) {
            $dosenIds = $matkul->dosen_id;
            if (is_string($dosenIds)) {
                $dosenIds = json_decode($dosenIds, true);
            }
            if (is_array($dosenIds) && in_array($this->id, $dosenIds)) {
                $totalSks += $matkul->sks;
            }
        }

        return $totalSks;
    }

    /**
     * Get total SKS yang diampu di semester tertentu
     */
    public function getTotalSksDiampuPerSemester($semester, $exceptMatkulId = null)
    {
        return $this->getTotalSksDiampu($semester, $exceptMatkulId);
    }

    /**
     * Cek apakah bisa mengampu matkul baru
     */
    public function canAmbilSks($newSks, $semester = null, $exceptMatkulId = null)
    {
        $maxSks = $this->max_sks;

        if ($maxSks === null) {
            return true;
        }

        $currentSks = $this->getTotalSksDiampu($semester, $exceptMatkulId);

        return ($currentSks + $newSks) <= $maxSks;
    }

    /**
     * Format tampilan slot tersedia
     */
    public function getFormattedSlotTersediaAttribute()
    {
        $slotIds = $this->slot_tersedia ?? [];

        if (empty($slotIds)) {
            return '<span class="text-green-600 font-medium">✅ Semua Slot</span>';
        }

        if (is_string($slotIds)) {
            $slotIds = json_decode($slotIds, true);
        }

        if (is_array($slotIds) && isset($slotIds[0]) && is_array($slotIds[0])) {
            $flattened = [];
            foreach ($slotIds as $item) {
                if (isset($item['id'])) {
                    $flattened[] = $item['id'];
                } elseif (isset($item[0])) {
                    $flattened = array_merge($flattened, $item);
                }
            }
            $slotIds = $flattened;
        }

        $slotIds = array_map('intval', (array) $slotIds);
        $slotIds = array_unique($slotIds);

        if (empty($slotIds)) {
            return '<span class="text-green-600 font-medium">✅ Semua Slot</span>';
        }

        $slots = Slot::whereIn('id', $slotIds)->orderBy('hari')->orderBy('slot_ke')->get();
        $grouped = $slots->groupBy('hari');
        $result = [];

        foreach ($grouped as $hari => $daySlots) {
            $dayName = $daySlots->first()->hari_nama;
            $ranges = [];
            $start = $daySlots->first()->jam_mulai;
            $end = $daySlots->first()->jam_selesai;

            for ($i = 1; $i < $daySlots->count(); $i++) {
                if ($daySlots[$i]->jam_mulai == $daySlots[$i - 1]->jam_selesai) {
                    $end = $daySlots[$i]->jam_selesai;
                } else {
                    $ranges[] = $start . '-' . $end;
                    $start = $daySlots[$i]->jam_mulai;
                    $end = $daySlots[$i]->jam_selesai;
                }
            }
            $ranges[] = $start . '-' . $end;

            $result[] = $dayName . ' ' . implode(', ', $ranges);
        }

        $maxSks = $this->max_sks;
        return implode('; ', $result) . " <span class='text-gray-400 text-xs'>(max {$maxSks} SKS)</span>";
    }

    /**
     * Get sisa SKS
     */
    public function getSisaSksAttribute()
    {
        $maxSks = $this->max_sks;
        if ($maxSks === null) {
            return null;
        }
        $currentSks = $this->getTotalSksDiampu();
        return $maxSks - $currentSks;
    }
}
