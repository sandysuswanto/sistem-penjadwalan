<?php

namespace App\Http\Controllers;

use App\Models\Jadwal;
use App\Models\JadwalRamadan;
use App\Models\MataKuliah;
use App\Models\Kelas;
use App\Models\Dosen;
use App\Models\Ruangan;
use App\Models\Prodi;
use App\Models\Angkatan;
use App\Models\Slot;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function audit(Request $request)
    {
        $semester = $request->get('semester', 'ganjil');
        $tahunAjaran = (int) $request->get('tahun_ajaran', 0);
        $prodiId = $request->get('prodi_id');
        $tampilan = $request->get('tampilan', 'normal');
        $semesterKe = $request->get('semester_ke');

        // Deteksi tahun_ajaran dari data jadwal yang ada
        if (!$tahunAjaran) {
            $tahunAjaran = Jadwal::query()
                ->whereHas('kelas.angkatan')
                ->whereHas('mataKuliah')
                ->get()
                ->map(function ($j) {
                    $t = $j->kelas->angkatan->tahun ?? null;
                    $s = $j->mataKuliah->semester_ke ?? null;
                    return ($t && $s) ? $t + (int)ceil($s / 2) - 1 : null;
                })
                ->filter()
                ->max() ?: (int) date('Y');
        }

        $prodis = Prodi::all();

        // Ambil jadwals berdasarkan semester + tampilan
        if ($tampilan == 'ramadan') {
            $jadwalsQuery = JadwalRamadan::with([
                'mataKuliah',
                'kelas.angkatan.prodi',
                'dosen',
                'ruangan'
            ])->whereHas('mataKuliah', function ($q) use ($semester) {
                $q->where('semester', $semester);
            });
        } else {
            $jadwalsQuery = Jadwal::with([
                'mataKuliah',
                'kelas.angkatan.prodi',
                'dosen',
                'ruangan'
            ])->whereHas('mataKuliah', function ($q) use ($semester) {
                $q->where('semester', $semester);
            });
        }

        if ($prodiId) {
            $jadwalsQuery->whereHas('kelas.angkatan.prodi', function ($q) use ($prodiId) {
                $q->where('id', $prodiId);
            });
        }

        $jadwals = $jadwalsQuery->get();

        $conflicts = $this->analyzeConflicts($jadwals);
        $completeness = $this->analyzeCompleteness($jadwals, $semester, $tahunAjaran, $prodiId, $semesterKe);

        return view('admin.audit', compact(
            'prodis',
            'semester',
            'tahunAjaran',
            'prodiId',
            'tampilan',
            'semesterKe',
            'conflicts',
            'completeness',
            'jadwals'
        ));
    }

    private function analyzeConflicts($jadwals)
    {
        $result = [
            'dosen' => ['count' => 0, 'items' => []],
            'ruangan' => ['count' => 0, 'items' => []],
            'kelas' => ['count' => 0, 'items' => []],
            'total_jadwal' => $jadwals->count(),
            'clean' => true,
        ];

        $items = [];
        foreach ($jadwals as $j) {
            $isRamadan = isset($j->jam_mulai);
            if ($isRamadan) {
                $startMin = strtotime($j->jam_mulai) / 60;
                $endMin = strtotime($j->jam_selesai) / 60;
            } else {
                $sks = $j->mataKuliah->sks ?? 1;
                $startMin = 7 * 60 + 30 + $j->slot_mulai * 50;
                $endMin = $startMin + $sks * 50;
            }
            $items[] = (object)[
                'id' => $j->id,
                'hari' => $j->hari,
                'start' => $startMin,
                'end' => $endMin,
                'dosen_id' => $j->dosen_id,
                'ruangan_id' => $j->ruangan_id,
                'kelas_id' => $j->kelas_id,
                'hariNama' => ['', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'][$j->hari] ?? '-',
                'dosenNama' => $j->dosen->nama ?? '-',
                'ruanganKode' => $j->ruangan->kode ?? '-',
                'kelasNama' => ($j->kelas->angkatan->tahun ?? '') . ($j->kelas->nama ?? ''),
                'matkulNama' => $j->mataKuliah->nama ?? '-',
            ];
        }

        $seen = ['dosen' => [], 'ruangan' => [], 'kelas' => []];

        for ($i = 0; $i < count($items); $i++) {
            for ($j = $i + 1; $j < count($items); $j++) {
                $a = $items[$i];
                $b = $items[$j];
                if ($a->hari != $b->hari) continue;

                $overlap = $a->start < $b->end && $b->start < $a->end;
                if (!$overlap) continue;

                $key = null;

                if ($a->dosen_id === $b->dosen_id) {
                    $key = "dosen_{$a->dosen_id}_{$a->hari}_" . min($a->start, $b->start);
                    if (!isset($seen['dosen'][$key])) {
                        $seen['dosen'][$key] = true;
                        $result['dosen']['count']++;
                        $result['dosen']['items'][] = [
                            'hari' => $a->hariNama,
                            'dosen' => $a->dosenNama,
                            'matkul1' => $a->matkulNama,
                            'kelas1' => $a->kelasNama,
                            'matkul2' => $b->matkulNama,
                            'kelas2' => $b->kelasNama,
                        ];
                        $result['clean'] = false;
                    }
                }

                if ($a->ruangan_id === $b->ruangan_id) {
                    $key = "ruang_{$a->ruangan_id}_{$a->hari}_" . min($a->start, $b->start);
                    if (!isset($seen['ruangan'][$key])) {
                        $seen['ruangan'][$key] = true;
                        $result['ruangan']['count']++;
                        $result['ruangan']['items'][] = [
                            'hari' => $a->hariNama,
                            'ruangan' => $a->ruanganKode,
                            'matkul1' => $a->matkulNama,
                            'kelas1' => $a->kelasNama,
                            'matkul2' => $b->matkulNama,
                            'kelas2' => $b->kelasNama,
                        ];
                        $result['clean'] = false;
                    }
                }

                if ($a->kelas_id === $b->kelas_id) {
                    $key = "kelas_{$a->kelas_id}_{$a->hari}_" . min($a->start, $b->start);
                    if (!isset($seen['kelas'][$key])) {
                        $seen['kelas'][$key] = true;
                        $result['kelas']['count']++;
                        $result['kelas']['items'][] = [
                            'hari' => $a->hariNama,
                            'kelas' => $a->kelasNama,
                            'matkul1' => $a->matkulNama,
                            'matkul2' => $b->matkulNama,
                        ];
                        $result['clean'] = false;
                    }
                }
            }
        }

        return $result;
    }

    private function analyzeCompleteness($jadwals, $semester, $tahunAjaran, $prodiId, $semesterKe = null)
    {
        $jadwalByKelas = [];
        foreach ($jadwals as $j) {
            $key = ($j->kelas->angkatan->tahun ?? '') . ($j->kelas->nama ?? '');
            if (!isset($jadwalByKelas[$key])) {
                $jadwalByKelas[$key] = 0;
            }
            $jadwalByKelas[$key]++;
        }

        $matkulQuery = MataKuliah::where('semester', $semester);
        if ($prodiId) {
            $matkulQuery->where('prodi_id', $prodiId);
        }
        if ($semesterKe) {
            $matkulQuery->where('semester_ke', $semesterKe);
        }
        $semuaMatkul = $matkulQuery->get();

        $prodiFilter = $prodiId ? [$prodiId] : Prodi::pluck('id')->toArray();

        $angkatans = Angkatan::with('prodi')->whereIn('prodi_id', $prodiFilter)->get();

        $result = [];
        $totalExpected = 0;
        $totalActual = 0;
        $totalMissing = 0;

        foreach ($angkatans as $angkatan) {
            $prodi = $angkatan->prodi;

            $range = $semesterKe ? [$semesterKe] : [];
            if (empty($range)) {
                $start = ($semester == 'ganjil') ? 1 : 2;
                $range = range($start, 8, 2);
            }
            foreach ($range as $smtKe) {
                $targetTahun = $tahunAjaran - ceil($smtKe / 2) + 1;
                if ($angkatan->tahun != $targetTahun) continue;

                $matkulsSmts = $semuaMatkul->where('prodi_id', $prodi->id)->where('semester_ke', $smtKe);
                if ($matkulsSmts->isEmpty()) continue;

                $kelasList = Kelas::whereHas('angkatan', function ($q) use ($angkatan) {
                    $q->where('id', $angkatan->id);
                })->get();

                $jmlMatkul = $matkulsSmts->count();
                $jmlKelas = $kelasList->count();
                $expected = $jmlMatkul * $jmlKelas;

                $jadwalTerpakai = 0;
                $kelasTanpaJadwal = [];

                foreach ($kelasList as $kelas) {
                    $kelasKey = ($angkatan->tahun ?? '') . ($kelas->nama ?? '');
                    $countJadwalKelas = $jadwalByKelas[$kelasKey] ?? 0;
                    $jadwalTerpakai += $countJadwalKelas;

                    $matkulIdsKelas = $jadwals->where('kelas_id', $kelas->id)->pluck('mata_kuliah_id')->unique()->toArray();
                    $missingMatkul = $matkulsSmts->whereNotIn('id', $matkulIdsKelas);

                    if ($missingMatkul->isNotEmpty()) {
                        $missingWithReasons = [];
                        foreach ($missingMatkul as $mk) {
                            $missingWithReasons[] = [
                                'nama' => $mk->nama,
                                'kode' => $mk->kode,
                                'sks' => $mk->sks,
                                'reason' => $this->determineFailureReason($mk, $kelas, $semester, $tahunAjaran, $jadwals),
                            ];
                        }
                        $kelasTanpaJadwal[] = [
                            'kelas' => $kelasKey,
                            'missing_count' => $missingMatkul->count(),
                            'missing_matkuls' => $missingWithReasons,
                        ];
                    }
                }

                $totalExpected += $expected;
                $totalActual += $jadwalTerpakai;
                $totalMissing += array_sum(array_column($kelasTanpaJadwal, 'missing_count'));

                $result[] = [
                    'prodi' => $prodi->nama,
                    'semester_ke' => $smtKe,
                    'jml_matkul' => $jmlMatkul,
                    'jml_kelas' => $jmlKelas,
                    'expected' => $expected,
                    'actual' => $jadwalTerpakai,
                    'missing' => $expected - $jadwalTerpakai,
                    'kelas_details' => $kelasTanpaJadwal,
                ];
            }
        }

        usort($result, function ($a, $b) {
            return strcmp($a['prodi'] . $a['semester_ke'], $b['prodi'] . $b['semester_ke']);
        });

        return [
            'items' => $result,
            'total_expected' => $totalExpected,
            'total_actual' => $totalActual,
            'total_missing' => $totalMissing,
        ];
    }

    private function determineFailureReason($matkul, $kelas, $semester, $tahunAjaran, $jadwals)
    {
        // 1. Cek dosen pengampu
        $dosenIds = $matkul->dosen_id;
        if (is_string($dosenIds)) $dosenIds = json_decode($dosenIds, true);
        if (empty($dosenIds)) {
            return 'Dosen pengampu belum dipilih';
        }

        $allDosenFull = true;
        $dosenOverCapacityNames = [];
        foreach ((array) $dosenIds as $did) {
            $dosen = Dosen::find($did);
            if (!$dosen) {
                $dosenOverCapacityNames[] = "Dosen ID {$did} tidak ditemukan";
                continue;
            }
            $maxSks = $dosen->max_sks;
            if ($maxSks === null) {
                $allDosenFull = false;
                continue;
            }
            // Hitung SKS hanya untuk tahun_ajaran ini
            $currentSks = $jadwals->filter(function ($j) use ($did) {
                return $j->dosen_id == $did;
            })->sum(function ($j) {
                return $j->mataKuliah->sks ?? 0;
            });
            if ($matkul->id && !$jadwals->contains('mata_kuliah_id', $matkul->id)) {
                $sksAfter = $currentSks + $matkul->sks;
            } else {
                $sksAfter = $currentSks;
            }
            if ($sksAfter <= $maxSks) {
                $allDosenFull = false;
            } else {
                $dosenOverCapacityNames[] = "{$dosen->nama} (maks {$maxSks} SKS, sudah {$currentSks} SKS)";
            }
        }

        if ($allDosenFull && !empty($dosenOverCapacityNames)) {
            return 'Seluruh dosen pengampu sudah penuh kapasitas: ' . implode(', ', $dosenOverCapacityNames);
        }

        // 2. Cek ketersediaan ruangan dengan kapasitas cukup
        $kelasKapasitas = $kelas->kapasitas ?? 0;
        $prodiId = $matkul->prodi_id;
        $ruanganIds = $matkul->ruangan_id;
        if (!empty($ruanganIds)) {
            $ruanganTersedia = Ruangan::whereIn('id', (array) $ruanganIds)
                ->where('kapasitas', '>=', $kelasKapasitas)
                ->exists();
            if (!$ruanganTersedia) {
                return "Ruangan khusus matkul ini tidak muat untuk kelas {$kelas->nama} (kapasitas kelas {$kelasKapasitas})";
            }
        } else {
            $adaRuangan = Ruangan::where(function ($q) use ($prodiId) {
                $q->where('prodi_id', $prodiId)->orWhereNull('prodi_id');
            })
                ->where('kapasitas', '>=', $kelasKapasitas)
                ->exists();
            if (!$adaRuangan) {
                return "Tidak ada ruangan dengan kapasitas mencukupi (kelas butuh minimal {$kelasKapasitas})";
            }
        }

        // 3. Fallback
        return 'Kemungkinan slot ruanngan slot tersedia dosen tidak tersedia';
    }
}
