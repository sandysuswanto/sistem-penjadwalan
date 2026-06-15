<?php

namespace App\Services;

use App\Models\MataKuliah;
use App\Models\Kelas;
use App\Models\Dosen;
use App\Models\Ruangan;
use App\Models\Jadwal;
use App\Models\Slot;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AntColonyScheduler
{
    private $alpha = 1.0;
    private $beta = 2.0;
    private $rho = 0.5;
    private $q = 100;
    private $antCount = 8;
    private $iterations = 20;
    private $pheromone;
    private $slots;
    private $slotsPerDay = 0;           // PERUBAHAN: dihitung dari DB
    private $slotListByDay = [];         // PERUBAHAN: daftar slot per hari

    private $eligibleKelasCache = [];
    private $availableRoomsCache = [];
    private $dosenSlotCache = [];
    private $slotIdMap = [];
    private $tahunAjaran = 2025;
    private $failedMatkuls = [];
    private $failedReasons = [];
    private $failedDetails = [];
    public function __construct($params = [])
    {
        foreach ($params as $key => $val) {
            if (property_exists($this, $key)) {
                $this->$key = $val;
            }
        }
        if (!isset($this->tahunAjaran)) {
            $this->tahunAjaran = (int) date('Y');
        }
        // Ambil semua slot (aktif & non-aktif) agar hari Sabtu tetap tersedia
        $this->slots = Slot::orderBy('hari')->orderBy('slot_ke')->get();
        // PERUBAHAN: Buat mapping slot dan hitung slotsPerDay dari database
        $maxSlotKe = 0;
        foreach ($this->slots as $slot) {
            $this->slotIdMap[$slot->hari][$slot->slot_ke] = $slot->id;
            if (!isset($this->slotListByDay[$slot->hari])){
                $this->slotListByDay[$slot->hari] = [];
            }
            $this->slotListByDay[$slot->hari][] = $slot->slot_ke;
            if ($slot->slot_ke > $maxSlotKe) {
                $maxSlotKe = $slot->slot_ke;
            }
        }
        // PERUBAHAN: slotsPerDay diambil dari slot_ke maksimum di database
        $this->slotsPerDay = $maxSlotKe;
        // Urutkan slot per hari
        foreach ($this->slotListByDay as $hari => $slots) {
            sort($this->slotListByDay[$hari]);
        }
        $allDosen = Dosen::where('is_active', true)->get();
        foreach ($allDosen as $dosen) {
            $slotTersedia = $dosen->slot_tersedia ?? [];
            $this->dosenSlotCache[$dosen->id] = is_array($slotTersedia) ? $slotTersedia : [];
        }
        Log::info("Loaded " . $this->slots->count() . " slots");
        Log::info("Slots per day max: " . $this->slotsPerDay);
        Log::info("Slot list per day: " . json_encode($this->slotListByDay));
    }
    public function run($semester = 'ganjil', $filterMatkulIds = null, $existingJadwals = null)
    {
        set_time_limit(600);
        $startTime = microtime(true);

        $this->failedMatkuls = [];
        $this->failedReasons = [];
        $this->failedDetails = [];

        $totalMatkuls = MataKuliah::where('semester', $semester)->where('is_active', true)->count();
        $totalKelas = Kelas::where('is_active', true)->count();
        $totalDosen = Dosen::where('is_active', true)->count();
        $totalRuangan = Ruangan::where('is_active', true)->count();

        Log::info("=== DATA AWAL ===");
        Log::info("Mata Kuliah: {$totalMatkuls}, Kelas: {$totalKelas}, Dosen: {$totalDosen}, Ruangan: {$totalRuangan}");

        if ($totalMatkuls == 0 || $totalKelas == 0 || $totalDosen == 0 || $totalRuangan == 0) {
            Log::error("Data tidak lengkap");
            return null;
        }

        $matkulsQuery = MataKuliah::with(['ruangan', 'prodi'])->where('semester', $semester)->where('is_active', true);
        if ($filterMatkulIds && $filterMatkulIds->count()) {
            $matkulsQuery->whereIn('id', $filterMatkulIds);
        }
        $matkuls = $matkulsQuery->get();
        if ($matkuls->isEmpty()) {
            Log::warning("Tidak ada mata kuliah untuk semester: {$semester}");
            return null;
        }
        $kelas = Kelas::with('angkatan.prodi')->where('is_active', true)
            ->whereHas('angkatan', fn($q) => $q->where('is_active', true))->get();
        $dosens = Dosen::where('is_active', true)->get()->keyBy('id');
        $ruangans = Ruangan::with('prodi')->where('is_active', true)->get()->keyBy('id');
        foreach ($matkuls as $matkul) {
            $eligibleKelas = $this->findAllEligibleKelas($matkul, $kelas);
            $this->eligibleKelasCache[$matkul->id] = $eligibleKelas;
            if ($eligibleKelas->isEmpty()) {
                $this->addFailedMatkul($matkul, "Kelas tidak tersedia (mungkin kelas untuk semester ini belum ditambahkan)");
                continue;
            }
            $availableRooms = $this->getAvailableRoomsWithCapacityCheck($matkul, $ruangans, $kelas);
            $this->availableRoomsCache[$matkul->id] = $availableRooms;

            if ($availableRooms->isEmpty()) {
                $maxCapacity = $this->getMaxKelasCapacityForMatkul($matkul, $kelas);
                $this->addFailedMatkul($matkul, "Ruangan kurang: tidak ada ruangan dengan kapasitas minimal {$maxCapacity}");
            }
        }
        $this->initPheromone($matkuls);
        $bestSchedule = null;
        $bestFitness = -INF;
        for ($iter = 0; $iter < $this->iterations; $iter++) {
            $allSolutions = [];
            for ($ant = 0; $ant < $this->antCount; $ant++) {
                $solution = $this->constructSolution($matkuls, $dosens, $ruangans, $kelas, $existingJadwals);
                if ($solution && count($solution) > 0) {
                    $fitness = $this->evaluate($solution);
                    $allSolutions[] = ['solution' => $solution, 'fitness' => $fitness];
                    if ($fitness > $bestFitness) {
                        $bestFitness = $fitness;
                        $bestSchedule = $solution;
                    }
                }
            }
            if (count($allSolutions) > 0) {
                $this->updatePheromone($allSolutions);
            }
            if (($iter + 1) % 5 == 0) {
                Log::info("Iterasi " . ($iter + 1) . "/{$this->iterations}, best fitness: {$bestFitness}");
            }
        }

        $duration = round((microtime(true) - $startTime) * 1000, 2);

        if ($bestSchedule && count($bestSchedule) > 0) {
            // Fallback: coba jadwalkan matkul yang gagal dengan PINJAM ruangan prodi lain
            $bestSchedule = $this->greedyFallbackSchedule($bestSchedule, $matkuls, $dosens, $ruangans, $kelas);
        }

        if (!$bestSchedule || count($bestSchedule) == 0) {
            Log::error("Gagal generate jadwal setelah {$this->iterations} iterasi");
            $this->logFailedMatkuls();
            return null;
        }

        $terjadwalIds = collect($bestSchedule)->pluck('mata_kuliah_id')->unique()->toArray();
        $allMatkulIds = $matkuls->pluck('id')->toArray();
        $notScheduled = array_diff($allMatkulIds, $terjadwalIds);

        foreach ($notScheduled as $matkulId) {
            $matkul = $matkuls->find($matkulId);
            if ($matkul && !in_array($matkul->kode, $this->failedMatkuls)) {
                $this->addFailedMatkul($matkul, "Slot tidak tersedia (dosen/ruangan bentrok atau slot penuh)");
            }
        }

        // Log distribusi kelas
        $kelasDistribution = [];
        foreach ($bestSchedule as $item) {
            $kelasId = $item->kelas_id;
            if (!isset($kelasDistribution[$kelasId])) {
                $kelasDistribution[$kelasId] = 0;
            }
            $kelasDistribution[$kelasId]++;
        }
        Log::info("Distribusi jadwal per kelas: " . json_encode($kelasDistribution));

        Log::info("✅ Selesai dalam {$duration} ms, menghasilkan " . count($bestSchedule) . " jadwal, fitness: {$bestFitness}");
        $this->logFailedMatkuls();

        return $bestSchedule;
    }

    private function logFailedMatkuls()
    {
        if (!empty($this->failedMatkuls)) {
            Log::warning("=== MATA KULIAH GAGAL DIJADWALKAN (" . count($this->failedMatkuls) . " matkul) ===");
            foreach ($this->failedMatkuls as $matkulKode) {
                $detail = $this->failedDetails[$matkulKode] ?? [];
                $prodi = $detail['prodi'] ?? '-';
                $semester = ($detail['semester_ke'] ?? '') . ' ' . ($detail['semester'] ?? '');
                $reason = $detail['reason'] ?? $this->failedReasons[$matkulKode] ?? "Unknown reason";
                Log::warning("  - {$matkulKode}: {$reason} | Prodi: {$prodi}, Semester: {$semester}");
            }
        } else {
            Log::info("✅ Semua mata kuliah berhasil dijadwalkan!");
        }
    }

    private function addFailedMatkul($matkul, $reason)
    {
        if (!in_array($matkul->kode, $this->failedMatkuls)) {
            $this->failedMatkuls[] = $matkul->kode;
            $this->failedReasons[$matkul->kode] = $reason;
            $this->failedDetails[$matkul->kode] = [
                'kode' => $matkul->kode,
                'nama' => $matkul->nama,
                'prodi' => $matkul->prodi->nama ?? '-',
                'semester' => $matkul->semester ?? '-',
                'semester_ke' => $matkul->semester_ke ?? '-',
                'sks' => $matkul->sks ?? 0,
                'reason' => $reason,
            ];
        }
    }

    public function getFailedMatkuls()
    {
        return [
            'list' => $this->failedMatkuls,
            'reasons' => $this->failedReasons,
            'details' => $this->failedDetails,
            'count' => count($this->failedMatkuls)
        ];
    }

    private function initPheromone($matkuls)
    {
        foreach ($matkuls as $matkul) {
            foreach ($this->slots as $slot) {
                if (!isset($this->pheromone[$matkul->id])) {
                    $this->pheromone[$matkul->id] = [];
                }
                if (!isset($this->pheromone[$matkul->id][$slot->hari])) {
                    $this->pheromone[$matkul->id][$slot->hari] = [];
                }
                $this->pheromone[$matkul->id][$slot->hari][$slot->slot_ke] = 0.1;
            }
        }
    }

    private function constructSolution($matkuls, $dosens, $ruangans, $kelas, $existingJadwals = null)
    {
        // PERUBAHAN: Acak urutan mata kuliah agar distribusi lebih merata
        $ordered = $matkuls->shuffle();
        $schedule = [];
        $usedDosen = [];
        $usedRuang = [];
        $usedKelas = [];

        if ($existingJadwals) {
            foreach ($existingJadwals as $j) {
                $sks = $j->mataKuliah ? $j->mataKuliah->sks : 3;
                $this->markUsed($usedDosen, $j->dosen_id, $j->hari, $j->slot_mulai, $sks);
                $this->markUsed($usedRuang, $j->ruangan_id, $j->hari, $j->slot_mulai, $sks);
                $this->markUsed($usedKelas, $j->kelas_id, $j->hari, $j->slot_mulai, $sks);
            }
        }

        $dosenClassCount = [];

        foreach ($ordered as $matkul) {
            if (in_array($matkul->kode, $this->failedMatkuls)) {
                continue;
            }

            $sks = $matkul->sks;
            $eligibleKelas = $this->eligibleKelasCache[$matkul->id] ?? collect();

            if ($eligibleKelas->isEmpty()) {
                $this->addFailedMatkul($matkul, "Kelas tidak tersedia untuk semester ini");
                continue;
            }

            $dosenIds = $this->parseDosenIds($matkul->dosen_id);
            if (empty($dosenIds)) {
                $this->addFailedMatkul($matkul, "Dosen pengampu belum dipilih");
                continue;
            }

            $availableRooms = $this->availableRoomsCache[$matkul->id] ?? collect();
            if ($availableRooms->isEmpty()) {
                $this->addFailedMatkul($matkul, "Ruangan kurang: semua ruangan sudah terpakai di slot tersebut");
                continue;
            }

            // PERUBAHAN: Tracking jumlah kelas per dosen secara GLOBAL (lintas semua matkul)
            foreach ($dosenIds as $did) {
                if (!isset($dosenClassCount[$did])) {
                    $dosenClassCount[$did] = 0;
                }
            }

            // PERUBAHAN: Per-matakuliah counter — pastikan tiap dosen dapet bagian dlm matkul ini
            $matkulDosenCount = array_fill_keys($dosenIds, 0);

            // PERUBAHAN: Loop semua kelas, jangan break setelah satu berhasil
            foreach ($eligibleKelas as $kelasItem) {
                $kapasitasKelas = $kelasItem->kapasitas ?? 0;

                $roomsWithCapacity = $availableRooms->filter(function ($ruang) use ($kapasitasKelas) {
                    return ($ruang->kapasitas ?? 0) >= $kapasitasKelas;
                });

                if ($roomsWithCapacity->isEmpty()) {
                    continue;
                }

                // PERUBAHAN: Urutkan dosen — prioritas per-matakuliah dulu, baru global
                $sortedDosen = $dosenIds;
                usort($sortedDosen, function ($a, $b) use ($matkulDosenCount, $dosenClassCount) {
                    $localA = $matkulDosenCount[$a] ?? 0;
                    $localB = $matkulDosenCount[$b] ?? 0;
                    if ($localA !== $localB) return $localA <=> $localB;
                    return ($dosenClassCount[$a] ?? 0) <=> ($dosenClassCount[$b] ?? 0);
                });

                foreach ($sortedDosen as $dosenId) {
                    $candidates = [];

                    // PERUBAHAN: Loop semua hari (1-6), Sabtu tetap diikutkan dengan preferensi lebih rendah
                    foreach ($this->slots as $slot) {
                        $hari = $slot->hari;
                        $slotKe = $slot->slot_ke;

                        $availableSlotsForDay = $this->slotListByDay[$hari] ?? [];
                        if (!in_array($slotKe, $availableSlotsForDay)) continue;

                        $neededSlots = range($slotKe, $slotKe + $sks - 1);
                        $allSlotsAvailable = true;
                        foreach ($neededSlots as $checkSlot) {
                            if (!in_array($checkSlot, $availableSlotsForDay)) {
                                $allSlotsAvailable = false;
                                break;
                            }
                        }
                        if (!$allSlotsAvailable) continue;

                        foreach ($roomsWithCapacity as $ruang) {
                            if (!$this->isDosenAvailableAtSlotCached($dosenId, $hari, $slotKe)) continue;

                            $conflict = false;
                            for ($i = 0; $i < $sks; $i++) {
                                if (
                                    $this->isConflict($usedDosen, $dosenId, $hari, $slotKe + $i, 1) ||
                                    $this->isConflict($usedRuang, $ruang->id, $hari, $slotKe + $i, 1) ||
                                    $this->isConflict($usedKelas, $kelasItem->id, $hari, $slotKe + $i, 1)
                                ) {
                                    $conflict = true;
                                    break;
                                }
                            }
                            if ($conflict) continue;

                            $heuristik = $this->calculateHeuristic($slot, $ruang, $matkul, $kelasItem, $dosens[$dosenId] ?? null);
                            $phero = $this->pheromone[$matkul->id][$hari][$slotKe] ?? 0.1;
                            $prob = pow($phero, $this->alpha) * pow($heuristik, $this->beta);

                            $candidates[] = [
                                'hari' => $hari,
                                'slot' => $slotKe,
                                'ruang_id' => $ruang->id,
                                'dosen_id' => $dosenId,
                                'kelas_id' => $kelasItem->id,
                                'prob' => $prob,
                                'sks' => $sks
                            ];
                        }
                    }

                    if (!empty($candidates)) {
                        $selected = $this->rouletteWheel($candidates);
                        if ($selected) {
                            $schedule[] = (object)[
                                'mata_kuliah_id' => $matkul->id,
                                'kelas_id' => $selected['kelas_id'],
                                'dosen_id' => $selected['dosen_id'],
                                'ruangan_id' => $selected['ruang_id'],
                                'hari' => $selected['hari'],
                                'slot_mulai' => $selected['slot']
                            ];

                            for ($i = 0; $i < $sks; $i++) {
                                $this->markUsed($usedDosen, $selected['dosen_id'], $selected['hari'], $selected['slot'] + $i, 1);
                                $this->markUsed($usedRuang, $selected['ruang_id'], $selected['hari'], $selected['slot'] + $i, 1);
                                $this->markUsed($usedKelas, $selected['kelas_id'], $selected['hari'], $selected['slot'] + $i, 1);
                            }

                            $matkulDosenCount[$dosenId] = ($matkulDosenCount[$dosenId] ?? 0) + 1;
                            $dosenClassCount[$dosenId] = ($dosenClassCount[$dosenId] ?? 0) + 1;
                            $assigned = true;

                            Log::info("Jadwalkan {$matkul->kode} untuk kelas {$kelasItem->nama} dosen {$selected['dosen_id']} di hari {$selected['hari']} slot {$selected['slot']}");
                            break;
                        }
                    }
                }

                // PERUBAHAN: Jika tidak ada dosen yang bisa, skip (post-check di run() yang handle)
            }
        }

        return empty($schedule) ? null : $schedule;
    }

    private function isDosenAvailableAtSlotCached($dosenId, $hari, $slotKe)
    {
        $slotTersedia = $this->dosenSlotCache[$dosenId] ?? [];

        if (empty($slotTersedia)) {
            return true;
        }

        $slotId = $this->slotIdMap[$hari][$slotKe] ?? null;
        if (!$slotId) return false;

        return in_array($slotId, $slotTersedia);
    }

    private function parseDosenIds($dosenIdField)
    {
        $ids = $dosenIdField ?? [];
        if (is_string($ids)) {
            $ids = json_decode($ids, true) ?? [];
        }
        if (!is_array($ids)) {
            $ids = $ids ? [$ids] : [];
        }
        return $ids;
    }

    private function calculateHeuristic($slot, $ruang, $matkul, $kelasItem, $dosen)
    {
        $heuristik = 1.0;

        // Slot pagi lebih baik (slot_ke 1-4)
        if ($slot->slot_ke <= 4) $heuristik *= 1.2;

        // Prioritas ruangan: khusus > sendiri > umum > lain
        $matkulRuanganId = $matkul->ruangan_id ?? null;

        if ($matkulRuanganId && $ruang->id == $matkulRuanganId) {
            $heuristik *= 5.0;
        } elseif ($ruang->prodi_id == $matkul->prodi_id) {
            $heuristik *= 2.0;
        } elseif (is_null($ruang->prodi_id)) {
            $heuristik *= 1.5;
        }

        // Kapasitas cukup
        if ($ruang->kapasitas >= $kelasItem->kapasitas) {
            $heuristik *= 1.2;
        }

        // Utamakan ketersediaan dosen (slot_tersedia), bukan preferensi hari
        // Jika dosen hanya tersedia Sabtu, ya Sabtu; jika semua hari, ya terserah ACO

        return $heuristik;
    }

    private function isConflict(&$used, $id, $hari, $slotMulai, $sks)
    {
        for ($i = 0; $i < $sks; $i++) {
            if (isset($used[$id][$hari][$slotMulai + $i])) {
                return true;
            }
        }
        return false;
    }

    private function markUsed(&$used, $id, $hari, $slotMulai, $sks)
    {
        for ($i = 0; $i < $sks; $i++) {
            $used[$id][$hari][$slotMulai + $i] = true;
        }
    }

    private function rouletteWheel($candidates)
    {
        $total = array_sum(array_column($candidates, 'prob'));
        if ($total <= 0) return null;
        $rand = mt_rand() / mt_getrandmax() * $total;
        $accum = 0;
        foreach ($candidates as $cand) {
            $accum += $cand['prob'];
            if ($accum >= $rand) return $cand;
        }
        return $candidates[0];
    }

    private function greedyFallbackSchedule($existingSchedule, $matkuls, $dosens, $ruangans, $kelas)
    {
        $scheduleMap = [];
        $usedDosen = [];
        $usedRuang = [];
        $usedKelas = [];

        foreach ($existingSchedule as $item) {
            $scheduleMap[$item->mata_kuliah_id][] = $item;
            $sks = $matkuls->find($item->mata_kuliah_id)->sks ?? 3;
            $this->markUsed($usedDosen, $item->dosen_id, $item->hari, $item->slot_mulai, $sks);
            $this->markUsed($usedRuang, $item->ruangan_id, $item->hari, $item->slot_mulai, $sks);
            $this->markUsed($usedKelas, $item->kelas_id, $item->hari, $item->slot_mulai, $sks);
        }

        $terjadwalIds = collect($existingSchedule)->pluck('mata_kuliah_id')->unique()->toArray();
        $allMatkulIds = $matkuls->pluck('id')->toArray();
        $failedIds = array_diff($allMatkulIds, $terjadwalIds);

        if (empty($failedIds)) {
            return $existingSchedule;
        }

        Log::info("Fallback: mencoba menjadwalkan " . count($failedIds) . " matkul gagal dengan ruangan prodi lain");

        foreach ($failedIds as $matkulId) {
            $matkul = $matkuls->find($matkulId);
            if (!$matkul) continue;

            $sks = $matkul->sks;
            $eligibleKelas = $this->findAllEligibleKelas($matkul, $kelas);
            if ($eligibleKelas->isEmpty()) continue;

            $dosenIds = $this->parseDosenIds($matkul->dosen_id);
            if (empty($dosenIds)) continue;

            // Pakai SEMUA ruangan yang muat (termasuk prodi lain)
            $allRooms = $ruangans->filter(function ($ruang) use ($matkul, $kelas) {
                $maxCap = $this->getMaxKelasCapacityForMatkul($matkul, $kelas);
                return ($ruang->kapasitas ?? 0) >= $maxCap;
            });

            if ($allRooms->isEmpty()) continue;

            foreach ($eligibleKelas as $kelasItem) {
                foreach ($dosenIds as $dosenId) {
                    foreach ($this->slots as $slot) {
                        $hari = $slot->hari;
                        $slotKe = $slot->slot_ke;

                        $availableSlotsForDay = $this->slotListByDay[$hari] ?? [];
                        if (!in_array($slotKe, $availableSlotsForDay)) continue;

                        $neededSlots = range($slotKe, $slotKe + $sks - 1);
                        $allSlotsAvailable = true;
                        foreach ($neededSlots as $checkSlot) {
                            if (!in_array($checkSlot, $availableSlotsForDay)) {
                                $allSlotsAvailable = false;
                                break;
                            }
                        }
                        if (!$allSlotsAvailable) continue;

                        if (!$this->isDosenAvailableAtSlotCached($dosenId, $hari, $slotKe)) continue;

                        foreach ($allRooms as $ruang) {
                            $conflict = false;
                            for ($i = 0; $i < $sks; $i++) {
                                if (
                                    $this->isConflict($usedDosen, $dosenId, $hari, $slotKe + $i, 1) ||
                                    $this->isConflict($usedRuang, $ruang->id, $hari, $slotKe + $i, 1) ||
                                    $this->isConflict($usedKelas, $kelasItem->id, $hari, $slotKe + $i, 1)
                                ) {
                                    $conflict = true;
                                    break;
                                }
                            }
                            if ($conflict) continue;

                            $scheduleEntry = (object)[
                                'mata_kuliah_id' => $matkul->id,
                                'kelas_id' => $kelasItem->id,
                                'dosen_id' => $dosenId,
                                'ruangan_id' => $ruang->id,
                                'hari' => $hari,
                                'slot_mulai' => $slotKe
                            ];

                            $existingSchedule[] = $scheduleEntry;

                            for ($i = 0; $i < $sks; $i++) {
                                $this->markUsed($usedDosen, $dosenId, $hari, $slotKe + $i, 1);
                                $this->markUsed($usedRuang, $ruang->id, $hari, $slotKe + $i, 1);
                                $this->markUsed($usedKelas, $kelasItem->id, $hari, $slotKe + $i, 1);
                            }

                            Log::info("Fallback: {$matkul->kode} terjadwal di {$ruang->nama} (prodi {$ruang->prodi_id})");
                            break 4;
                        }
                    }
                }
            }
        }

        return $existingSchedule;
    }

    public function evaluate($schedule)
    {
        $score = count($schedule) * 2;
        foreach ($schedule as $item) {
            if ($item->slot_mulai <= 4) $score += 0.5;
        }
        return $score;
    }

    private function updatePheromone($allSolutions)
    {
        foreach ($this->pheromone as $mid => $days) {
            foreach ($days as $h => $slots) {
                foreach ($slots as $s => $val) {
                    $this->pheromone[$mid][$h][$s] *= (1 - $this->rho);
                }
            }
        }

        foreach ($allSolutions as $sol) {
            $fitness = max(0.1, $sol['fitness']);
            foreach ($sol['solution'] as $item) {
                if (isset($this->pheromone[$item->mata_kuliah_id][$item->hari][$item->slot_mulai])) {
                    $this->pheromone[$item->mata_kuliah_id][$item->hari][$item->slot_mulai] += $this->q * $fitness;
                }
            }
        }
    }

    private function findAllEligibleKelas($matkul, $kelasCollection)
    {
        $targetTahun = $this->tahunAjaran - ceil($matkul->semester_ke / 2) + 1;
        $isUmum = ($matkul->prodi && $matkul->prodi->kode == 'UMUM');

        if ($isUmum) {
            return $kelasCollection->filter(function ($kelas) use ($targetTahun) {
                return $kelas->angkatan->tahun == $targetTahun;
            })->values();
        }

        return $kelasCollection->filter(function ($kelas) use ($matkul, $targetTahun) {
            return $kelas->angkatan->prodi_id == $matkul->prodi_id
                && $kelas->angkatan->tahun == $targetTahun;
        })->values();
    }

    private function getAvailableRoomsWithCapacityCheck($matkul, $ruangans, $kelasCollection)
    {
        $maxKelasCapacity = $this->getMaxKelasCapacityForMatkul($matkul, $kelasCollection);
        if ($maxKelasCapacity == 0) {
            return collect();
        }

        // Kembalikan semua ruangan yang muat — heuristic di calculateHeuristic() yang urutkan prioritas
        return $ruangans->filter(function ($ruang) use ($maxKelasCapacity) {
            return ($ruang->kapasitas ?? 0) >= $maxKelasCapacity;
        });
    }

    private function getMaxKelasCapacityForMatkul($matkul, $kelasCollection)
    {
        $eligibleKelas = $this->findAllEligibleKelas($matkul, $kelasCollection);
        if ($eligibleKelas->isEmpty()) return 0;
        return $eligibleKelas->max(function ($kelas) {
            return $kelas->kapasitas ?? 0;
        });
    }
}
