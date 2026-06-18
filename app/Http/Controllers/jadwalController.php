<?php

namespace App\Http\Controllers;

use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\MataKuliah;
use App\Models\Dosen;
use App\Models\JadwalRamadan;
use App\Models\Prodi;
use App\Models\Ruangan;
use App\Models\Slot;
use App\Services\AntColonyScheduler;
use Carbon\Carbon;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class jadwalController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $role = $user->role;
        $tampilan = $request->get('tampilan', 'normal');

        if ($role == 'kaprodi') {
            $userProdiId = $user->prodi_id;
            $kelasList = Kelas::with('angkatan.prodi')
                ->whereHas('angkatan.prodi', function ($q) use ($userProdiId) {
                    $q->where('id', $userProdiId);
                })->get();
            $dosenList = Dosen::where('prodi_id', $userProdiId)->get();
            $prodiList = collect();
        } else {
            $userProdiId = null;
            $kelasList = Kelas::with('angkatan.prodi')->get();
            $dosenList = Dosen::all();
            $prodiList = Prodi::all();
        }

        if ($tampilan == 'ramadan') {
            $query = JadwalRamadan::with(['mataKuliah', 'kelas.angkatan.prodi', 'dosen', 'ruangan']);
        } else {
            $query = Jadwal::with(['mataKuliah', 'kelas.angkatan.prodi', 'dosen', 'ruangan']);
        }

        if ($role == 'kaprodi') {
            $query->whereHas('kelas.angkatan.prodi', function ($q) use ($userProdiId) {
                $q->where('id', $userProdiId);
            });
        } elseif ($role == 'admin' && $request->filled('prodi_id')) {
            $query->whereHas('kelas.angkatan.prodi', function ($q) use ($request) {
                $q->where('id', $request->prodi_id);
            });
        }

        if ($request->filled('semester')) {
            $query->whereHas('mataKuliah', function ($q) use ($request) {
                $q->where('semester', $request->semester);
            });
        }

        if ($request->filled('kelas_id')) {
            $query->where('kelas_id', $request->kelas_id);
        }

        if ($request->filled('dosen_id')) {
            $query->where('dosen_id', $request->dosen_id);
        }

        // ========== PERBAIKAN ==========
        // DEFINISIKAN $jadwals di SEMUA kondisi
        if ($tampilan == 'normal') {
            $jadwals = $query->orderBy('hari')->orderBy('slot_mulai')->get();
            $hariNama = ['', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

            foreach ($jadwals as $j) {
                $j->hari_nama = $hariNama[$j->hari] ?? 'Unknown';

                $slot = Slot::where('hari', $j->hari)
                    ->where('slot_ke', $j->slot_mulai)
                    ->first();

                if ($slot) {
                    $j->jam_mulai = $slot->jam_mulai;
                    $start = Carbon::createFromTimeString($slot->jam_mulai);
                    $end = $start->addMinutes($j->mataKuliah->sks * 50);
                    $j->jam_selesai = $end->format('H:i');
                } else {
                    $startMinutes = 7 * 60 + 30 + ($j->slot_mulai * 50);
                    $endMinutes = $startMinutes + ($j->mataKuliah->sks * 50);
                    $j->jam_mulai = sprintf('%02d:%02d', floor($startMinutes / 60), $startMinutes % 60);
                    $j->jam_selesai = sprintf('%02d:%02d', floor($endMinutes / 60), $endMinutes % 60);
                }
            }
        } else {
            // TAMPILAN RAMADAN
            $jadwals = $query->orderBy('hari')->orderBy('jam_mulai')->get();
            $hariNama = ['', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

            foreach ($jadwals as $j) {
                $j->hari_nama = $hariNama[$j->hari] ?? 'Unknown';
                // Format jam dari database (sudah dalam format H:i:s)
                $j->jam_mulai = substr($j->jam_mulai, 0, 5);
                $j->jam_selesai = substr($j->jam_selesai, 0, 5);
            }
        }
        // ==================================

        return view('jadwalauth.index', compact('jadwals', 'prodiList', 'kelasList', 'dosenList', 'role', 'userProdiId', 'tampilan'));
    }
    public function cetak(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $role = $user->role;
        $tampilan = $request->get('tampilan', 'normal');

        if ($role == 'kaprodi') {
            $userProdiId = $user->prodi_id;
        } else {
            $userProdiId = null;
        }

        if ($tampilan == 'ramadan') {
            $query = JadwalRamadan::with(['mataKuliah', 'kelas.angkatan.prodi', 'dosen', 'ruangan']);
        } else {
            $query = Jadwal::with(['mataKuliah', 'kelas.angkatan.prodi', 'dosen', 'ruangan']);
        }

        if ($role == 'kaprodi') {
            $query->whereHas('kelas.angkatan.prodi', function ($q) use ($userProdiId) {
                $q->where('id', $userProdiId);
            });
        } elseif ($role == 'admin' && $request->filled('prodi_id')) {
            $query->whereHas('kelas.angkatan.prodi', function ($q) use ($request) {
                $q->where('id', $request->prodi_id);
            });
        }

        if ($request->filled('semester')) {
            $query->whereHas('mataKuliah', function ($q) use ($request) {
                $q->where('semester', $request->semester);
            });
        }

        if ($request->filled('kelas_id')) {
            $query->where('kelas_id', $request->kelas_id);
        }

        if ($request->filled('dosen_id')) {
            $query->where('dosen_id', $request->dosen_id);
        }

        if ($tampilan == 'normal') {
            $jadwals = $query->orderBy('hari')->orderBy('slot_mulai')->get();
            $hariNama = ['', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

            foreach ($jadwals as $j) {
                $j->hari_nama = $hariNama[$j->hari] ?? 'Unknown';
                $slot = Slot::where('hari', $j->hari)
                    ->where('slot_ke', $j->slot_mulai)
                    ->first();
                if ($slot) {
                    $j->jam_mulai = $slot->jam_mulai;
                    $start = Carbon::createFromTimeString($slot->jam_mulai);
                    $end = $start->addMinutes($j->mataKuliah->sks * 50);
                    $j->jam_selesai = $end->format('H:i');
                } else {
                    $startMinutes = 7 * 60 + 30 + ($j->slot_mulai * 50);
                    $endMinutes = $startMinutes + ($j->mataKuliah->sks * 50);
                    $j->jam_mulai = sprintf('%02d:%02d', floor($startMinutes / 60), $startMinutes % 60);
                    $j->jam_selesai = sprintf('%02d:%02d', floor($endMinutes / 60), $endMinutes % 60);
                }
            }
        } else {
            $jadwals = $query->orderBy('hari')->orderBy('jam_mulai')->get();
            $hariNama = ['', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            foreach ($jadwals as $j) {
                $j->hari_nama = $hariNama[$j->hari] ?? 'Unknown';
                $j->jam_mulai = substr($j->jam_mulai, 0, 5);
                $j->jam_selesai = substr($j->jam_selesai, 0, 5);
            }
        }

        $prodiList = Prodi::all();
        $kelasList = Kelas::with('angkatan.prodi')->get();
        $dosenList = Dosen::all();

        $filterLabel = [];
        if ($request->filled('prodi_id')) {
            $prodi = Prodi::find($request->prodi_id);
            $filterLabel[] = 'Prodi: ' . ($prodi->nama ?? '-');
        }
        if ($request->filled('kelas_id')) {
            $kelas = Kelas::with('angkatan')->find($request->kelas_id);
            $filterLabel[] = 'Kelas: ' . (($kelas->angkatan->tahun ?? '') . ($kelas->nama ?? ''));
        }
        if ($request->filled('dosen_id')) {
            $dosen = Dosen::find($request->dosen_id);
            $filterLabel[] = 'Dosen: ' . ($dosen->nama ?? '-');
        }
        if ($request->filled('semester')) {
            $filterLabel[] = 'Semester: ' . ucfirst($request->semester);
        }
        if ($request->filled('tampilan')) {
            $filterLabel[] = 'Tampilan: ' . ucfirst($request->tampilan);
        }

        return view('jadwalauth.cetak', compact('jadwals', 'tampilan', 'filterLabel'));
    }

    public function regenerate(Request $request)
    {
        // CEK APAKAH SEMUA PRODI SUDAH DIVALIDASI
        $pendingProdi = Prodi::where('is_validated', false)->count();
        if ($pendingProdi > 0) {
            $pendingNames = Prodi::where('is_validated', false)->pluck('nama')->implode(', ');
            return back()->with('error', "Jadwal tidak bisa digenerate. Prodi berikut belum melakukan validasi: {$pendingNames}");
        }

        // CEK MATA KULIAH TANPA DOSEN PENGAMPU
        $matkulTanpaDosen = $this->checkMatkulTanpaDosen();
        if (!empty($matkulTanpaDosen)) {
            $errorMsg = "Tidak bisa generate jadwal! Terdapat " . count($matkulTanpaDosen) . " mata kuliah tanpa dosen pengampu:<br>";
            foreach ($matkulTanpaDosen as $mk) {
                $errorMsg .= "- {$mk->kode} - {$mk->nama} (Semester {$mk->semester_ke})<br>";
            }
            return back()->with('error', $errorMsg);
        }

        set_time_limit(300);
        ini_set('memory_limit', '512M');

        $request->validate([
            'semester'      => 'required|in:ganjil,genap',
            'tahun_ajaran'  => 'nullable|integer|min:2000|max:2100',
        ]);

        $semester = $request->semester;
        $tahunAjaran = (int) $request->input('tahun_ajaran', date('Y'));

        // ANALISIS KEBUTUHAN SLOT SEBELUM GENERATE
        $analisis = $this->analisisKebutuhanSlot($semester, $tahunAjaran);

        // JIKA KAPASITAS KURANG, TOLAK GENERATE
        if (!$analisis['is_cukup']) {
            $errorMsg = "❌ TIDAK BISA GENERATE JADWAL! Kapasitas ruangan tidak mencukupi.<br><br>";
            $errorMsg .= "📊 <strong>Analisis Kebutuhan Slot:</strong><br>";
            $errorMsg .= "- Total kebutuhan slot (berdasarkan SKS): <strong>" . number_format($analisis['total_kebutuhan']) . "</strong><br>";
            $errorMsg .= "- Total kapasitas tersedia: <strong>" . number_format($analisis['total_kapasitas']) . "</strong><br>";
            $errorMsg .= "- Kekurangan: <strong>" . number_format(abs($analisis['selisih'])) . " slot</strong><br>";
            $errorMsg .= "- Persentase kebutuhan: <strong>{$analisis['persentase']}%</strong><br><br>";

            $errorMsg .= "📋 <strong>Detail per Program Studi:</strong><br>";
            foreach ($analisis['detail_prodi'] as $prodiNama => $kebutuhan) {
                $errorMsg .= "- {$prodiNama}: " . number_format($kebutuhan) . " slot<br>";
            }

            $errorMsg .= "<br>📋 <strong>Detail per Hari:</strong><br>";
            foreach ($analisis['slots_per_day'] as $hari => $jumlah) {
                $namaHari = $this->getNamaHari($hari);
                $errorMsg .= "- {$namaHari}: {$jumlah} slot<br>";
            }

            $errorMsg .= "<br>💡 <strong>Solusi:</strong><br>";
            $errorMsg .= "- Tambah ruangan baru<br>";
            $errorMsg .= "- Kurangi jumlah kelas<br>";
            $errorMsg .= "- Kurangi jumlah mata kuliah atau SKS per semester<br>";
            $errorMsg .= "- Aktifkan hari Sabtu sebagai hari efektif";

            return back()->with('error', $errorMsg);
        }

        // CEK KELAS OVER KAPASITAS
        $kelasOverCapacity = $this->checkKelasCapacityPerProdi();
        if ($kelasOverCapacity->isNotEmpty()) {
            $errorMessages = [];
            foreach ($kelasOverCapacity as $item) {
                $errorMessages[] = "⚠️ Kelas {$item->kelas_nama} (Prodi: {$item->prodi_nama}) memiliki kapasitas {$item->kapasitas_kelas} mahasiswa, melebihi kapasitas ruangan terbesar di prodi tersebut ({$item->max_kapasitas_prodi})" .
                    ($item->max_kapasitas_umum > 0 ? " (Ruangan umum tersedia: {$item->max_kapasitas_umum})" : "");
            }
            return back()->with('error', 'Tidak bisa generate jadwal! ' . implode('; ', $errorMessages));
        }

        // CEK DOSEN OVER KAPASITAS PER SEMESTER
        $dosenOverCapacity = $this->checkDosenOverCapacity($semester);
        if ($dosenOverCapacity->isNotEmpty()) {
            $errorMessages = [];
            foreach ($dosenOverCapacity as $item) {
                $errorMessages[] = "⚠️ Dosen {$item->dosen_nama} sudah mengampu {$item->total_sks} SKS di semester {$semester}, melebihi kapasitas slot ({$item->max_slot} SKS)";
            }
            return back()->with('error', 'Tidak bisa generate jadwal! ' . implode('; ', $errorMessages));
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        \App\Models\Jadwal::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $scheduler = new AntColonyScheduler([
            'antCount'    => $request->input('ant_count', 10),
            'iterations'  => $request->input('iterations', 30),
            'tahunAjaran' => $tahunAjaran,
        ]);

        $schedule = $scheduler->run($semester);
        $failed = $scheduler->getFailedMatkuls();

        if (!$schedule || count($schedule) == 0) {
            $errorMsg = "Penjadwalan gagal! Tidak ada jadwal yang berhasil digenerate.<br><br>";
            if ($failed['count'] > 0) {
                $errorMsg .= "<strong>📋 Mata kuliah yang gagal ({$failed['count']}):</strong><br>";
                $details = isset($failed['details']) ? $failed['details'] : [];
                if (!empty($details)) {
                    $reasonGroups = [];
                    foreach ($details as $kode => $detail) {
                        if (!is_array($detail) || !isset($detail['reason'])) {
                            continue;
                        }
                        $reasonKey = is_string($detail['reason']) ? $detail['reason'] : json_encode($detail['reason']);
                        $reasonGroups[$reasonKey][] = $detail;
                    }
                    foreach ($reasonGroups as $reason => $items) {
                        $errorMsg .= "<br><strong>🔴 {$reason} ({count($items)} matkul):</strong><br>";
                        foreach ($items as $d) {
                            $errorMsg .= "&nbsp;&nbsp;- {$d['kode']} {$d['nama']} — Prodi: {$d['prodi']}, Semester {$d['semester_ke']} {$d['semester']}<br>";
                        }
                    }
                } else {
                    foreach ($failed['reasons'] as $kode => $reason) {
                        $errorMsg .= "- <strong>{$kode}</strong>: {$reason}<br>";
                    }
                }
            }
            return back()->with('error', $errorMsg);
        }

        // VALIDASI ULANG HASIL SCHEDULE
        $violations = [];
        $violationDetails = [];

        foreach ($schedule as $item) {
            $kelas = Kelas::with('angkatan.prodi')->find($item->kelas_id);
            $ruangan = Ruangan::find($item->ruangan_id);

            if ($kelas && $ruangan) {
                $kapasitasKelas = $kelas->kapasitas ?? 0;
                $kapasitasRuangan = $ruangan->kapasitas ?? 0;

                if ($kapasitasKelas > $kapasitasRuangan) {
                    $key = "kelas_{$kelas->id}_ruangan_{$ruangan->id}";
                    if (!isset($violationDetails[$key])) {
                        $violationDetails[$key] = [
                            'kelas_nama' => $kelas->nama,
                            'prodi_nama' => $kelas->angkatan->prodi->nama ?? 'Unknown',
                            'kapasitas_kelas' => $kapasitasKelas,
                            'ruangan_nama' => $ruangan->nama,
                            'kapasitas_ruangan' => $kapasitasRuangan
                        ];
                    }
                }
            }
        }

        if (!empty($violationDetails)) {
            $errorMessages = [];
            foreach ($violationDetails as $violation) {
                $errorMessages[] = "Kelas {$violation['kelas_nama']} (Prodi: {$violation['prodi_nama']}) kapasitas {$violation['kapasitas_kelas']} melebihi kapasitas ruangan {$violation['ruangan_nama']} ({$violation['kapasitas_ruangan']})";
            }
            return back()->with('error', 'Hasil penjadwalan melanggar kapasitas ruangan: ' . implode('; ', $errorMessages));
        }

        foreach ($schedule as $item) {
            \App\Models\Jadwal::create((array) $item);
        }

        $successMsg = "✅ Jadwal berhasil digenerate!<br>";
        $successMsg .= "📊 Analisis Slot: " . number_format($analisis['total_kebutuhan']) . "/" . number_format($analisis['total_kapasitas']) . " slot terpakai (" . $analisis['persentase'] . "%)";

        if ($failed['count'] > 0) {
            $warningMsg = "⚠️ Jadwal berhasil digenerate, tetapi <strong>{$failed['count']}</strong> mata kuliah tidak terjadwal:<br>";

            $details = isset($failed['details']) ? $failed['details'] : [];
            if (!empty($details)) {
                $reasonGroups = [];
                foreach ($details as $kode => $detail) {
                    if (!is_array($detail) || !isset($detail['reason'])) {
                        continue;
                    }
                    $reasonKey = is_string($detail['reason']) ? $detail['reason'] : json_encode($detail['reason']);
                    $reasonGroups[$reasonKey][] = $detail;
                }
                foreach ($reasonGroups as $reason => $items) {
                    $reasonStr = is_array($reason) ? json_encode($reason) : (string) $reason;
                    $warningMsg .= "<br><strong>🔴 {$reasonStr} (" . count($items) . " matkul):</strong><br>";
                    foreach ($items as $d) {
                        $kode = is_array($d['kode'] ?? null) ? json_encode($d['kode']) : ($d['kode'] ?? '-');
                        $nama = is_array($d['nama'] ?? null) ? json_encode($d['nama']) : ($d['nama'] ?? '-');
                        $prodi = is_array($d['prodi'] ?? null) ? json_encode($d['prodi']) : ($d['prodi'] ?? '-');
                        $smtKe = $d['semester_ke'] ?? '';
                        $smt = is_array($d['semester'] ?? null) ? json_encode($d['semester']) : ($d['semester'] ?? '-');
                        $warningMsg .= "&nbsp;&nbsp;- {$kode} {$nama} — Prodi: {$prodi}, Semester {$smtKe} {$smt}<br>";
                    }
                }
            } else {
                foreach ($failed['reasons'] as $kode => $reason) {
                    $warningMsg .= "- <strong>{$kode}</strong>: {$reason}<br>";
                }
            }

            return redirect()->route('jadwalauth.index')->with('warning', $warningMsg . '<br>' . $successMsg);
        }

        return redirect()->route('jadwalauth.index')->with('success', $successMsg);
    }

    public function listSchedule(Request $request)
    {
        $prodiList = Prodi::all();

        $query = Jadwal::with([
            'mataKuliah',
            'kelas.angkatan.prodi',
            'dosen',
            'ruangan'
        ]);

        if ($request->filled('semester')) {
            $query->whereHas('mataKuliah', function ($q) use ($request) {
                $q->where('semester', $request->semester);
            });
        }

        if ($request->filled('prodi_id')) {
            $query->whereHas('kelas.angkatan.prodi', function ($q) use ($request) {
                $q->where('id', $request->prodi_id);
            });
        }

        $jadwals = $query->orderBy('hari')
            ->orderBy('slot_mulai')
            ->get();

        $scheduleList = [];
        $hariNama = ['', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

        foreach ($jadwals as $index => $j) {
            $startMinutes = 7 * 60 + 30 + ($j->slot_mulai * 50);
            $endMinutes = $startMinutes + ($j->mataKuliah->sks * 50);

            $jamMulai = sprintf('%02d:%02d', floor($startMinutes / 60), $startMinutes % 60);
            $jamSelesai = sprintf('%02d:%02d', floor($endMinutes / 60), $endMinutes % 60);

            $scheduleList[] = (object)[
                'no'       => $index + 1,
                'jadwal'   => ($hariNama[$j->hari] ?? '-') . ' ' . $jamMulai . ' - ' . $jamSelesai,
                'kode_mk'  => $j->mataKuliah->kode ?? '-',
                'nama_mk'  => $j->mataKuliah->nama ?? '-',
                'prodi'    => $j->kelas->angkatan->prodi->nama ?? '-',
                'smt'      => $j->mataKuliah->semester_ke ?? '-',
                'sks'      => $j->mataKuliah->sks ?? '-',
                'kelas'    => ($j->kelas->angkatan->tahun ?? '') . ($j->kelas->nama ?? ''),
                'ruangan'  => $j->ruangan->nama ?? '-',
                'dosen'    => $j->dosen->nama ?? '-',
            ];
        }

        return view('jadwalglobal.index', [
            'scheduleList' => $scheduleList,
            'prodiList'    => $prodiList,
        ]);
    }

    /**
     * GENERATE JADWAL RAMADAN
     *
     * ATURAN:
     * - 1 SKS = 35 MENIT
     * - SETIAP RUANGAN dimulai dari jam 08:00 (bukan 07:30)
     * - SUSUNAN JADWAL PER RUANGAN TETAP (urutan jam mulai asli)
     * - DOSEN TIDAK BENTROK karena diatur per ruangan
     */
    public function generateRamadan(Request $request)
    {
        $request->validate([
            'semester' => 'required|in:ganjil,genap',
        ]);

        // CEK MATA KULIAH TANPA DOSEN PENGAMPU
        $matkulTanpaDosen = $this->checkMatkulTanpaDosen();
        if (!empty($matkulTanpaDosen)) {
            $errorMsg = "Tidak bisa generate jadwal Ramadan! Terdapat " . count($matkulTanpaDosen) . " mata kuliah tanpa dosen pengampu:<br>";
            foreach ($matkulTanpaDosen as $mk) {
                $errorMsg .= "- {$mk->kode} - {$mk->nama} (Semester {$mk->semester_ke})<br>";
            }
            return redirect()->route('jadwalauth.index')->with('error', $errorMsg);
        }

        $kelasOverCapacity = $this->checkKelasCapacityPerProdi();
        if ($kelasOverCapacity->isNotEmpty()) {
            $errorMessages = [];
            foreach ($kelasOverCapacity as $item) {
                $errorMessages[] = "⚠️ Kelas {$item->kelas_nama} (Prodi: {$item->prodi_nama}) memiliki kapasitas {$item->kapasitas_kelas} mahasiswa, melebihi kapasitas ruangan terbesar di prodi tersebut ({$item->max_kapasitas_prodi})";
            }
            return redirect()->route('jadwalauth.index')
                ->with('error', 'Tidak bisa generate jadwal Ramadan! ' . implode('; ', $errorMessages));
        }

        // AMBIL JADWAL NORMAL BERDASARKAN SEMESTER
        $jadwalsNormal = Jadwal::whereHas('mataKuliah', function ($q) use ($request) {
            $q->where('semester', $request->semester);
        })
            ->with(['mataKuliah', 'kelas', 'dosen', 'ruangan'])
            ->orderBy('hari')
            ->orderBy('ruangan_id')
            ->orderBy('slot_mulai')
            ->get();

        if ($jadwalsNormal->isEmpty()) {
            return redirect()->route('jadwalauth.index')
                ->with('error', 'Tidak ada jadwal normal untuk semester ' . $request->semester);
        }

        // VALIDASI KAPASITAS RUANGAN
        $violations = [];
        foreach ($jadwalsNormal as $jadwal) {
            $kelas = Kelas::find($jadwal->kelas_id);
            $ruangan = Ruangan::find($jadwal->ruangan_id);

            if ($kelas && $ruangan) {
                $kapasitasKelas = $kelas->kapasitas ?? 0;
                $kapasitasRuangan = $ruangan->kapasitas ?? 0;

                if ($kapasitasKelas > $kapasitasRuangan) {
                    $prodiNama = $kelas->angkatan->prodi->nama ?? 'Unknown';
                    $violations[] = "Kelas {$kelas->nama} (Prodi: {$prodiNama}) kapasitas {$kapasitasKelas} melebihi kapasitas ruangan {$ruangan->nama} ({$kapasitasRuangan})";
                }
            }
        }

        if (!empty($violations)) {
            return redirect()->route('jadwalauth.index')
                ->with('error', 'Tidak bisa generate jadwal Ramadan! Pelanggaran kapasitas: ' . implode('; ', array_unique($violations)));
        }

        // HAPUS JADWAL RAMADAN LAMA
        JadwalRamadan::where('semester', $request->semester)->delete();

        // ========== KONFIGURASI ==========
        $durasiPerSks = 35; // 35 MENIT PER SKS
        $jamMulaiAwal = 8 * 60; // 08:00 dalam menit

        // KONVERSI LANGSUNG: jam = 08:00 + (slot_mulai × 35 menit)
        // Normal: slot_mulai = 0 → 07:30, 1 → 08:20, ... (50 menit/slot)
        // Ramadan: slot_mulai = 0 → 08:00, 1 → 08:35, ... (35 menit/slot)
        //
        // 3 sesi × 4 slot:
        //   Sesi 1: 08:00 – 10:20 (slot 0-3)
        //   Sesi 2: 10:20 – 12:40 (slot 4-7)
        //   Sesi 3: 12:40 – 15:00 (slot 8-11)

        $totalTersimpan = 0;

        foreach ($jadwalsNormal as $jadwal) {
            $sks = $jadwal->mataKuliah->sks ?? 1;
            $jamMulai = $jamMulaiAwal + ($jadwal->slot_mulai * $durasiPerSks);
            $jamSelesai = $jamMulai + ($sks * $durasiPerSks);

            $jamMulaiFormatted = sprintf('%02d:%02d:00', intdiv($jamMulai, 60), $jamMulai % 60);
            $jamSelesaiFormatted = sprintf('%02d:%02d:00', intdiv($jamSelesai, 60), $jamSelesai % 60);

            JadwalRamadan::create([
                'jadwal_asli_id' => $jadwal->id,
                'mata_kuliah_id' => $jadwal->mata_kuliah_id,
                'kelas_id'       => $jadwal->kelas_id,
                'ruangan_id'     => $jadwal->ruangan_id,
                'dosen_id'       => $jadwal->dosen_id,
                'hari'           => $jadwal->hari,
                'jam_mulai'      => $jamMulaiFormatted,
                'jam_selesai'    => $jamSelesaiFormatted,
                'semester'       => $request->semester,
                'durasi_per_sks' => $durasiPerSks,
            ]);

            $totalTersimpan++;
        }

        $jumlahJadwalNormal = count($jadwalsNormal);

        $successMsg = "✅ Jadwal Ramadan berhasil digenerate untuk semester {$request->semester}!<br>
    📊 Total jadwal normal: {$jumlahJadwalNormal} mata kuliah<br>
    📊 Total jadwal Ramadan tersimpan: {$totalTersimpan} mata kuliah<br>
    ⏰ 1 SKS = {$durasiPerSks} menit | 3 sesi × 4 slot (08:00–15:00)<br>
    📅 Semua data (hari, ruang, kelas, dosen) sama dengan jadwal normal<br>
    🔄 Konversi slot langsung — 100% jadwal tersimpan, tanpa bentrok";

        return redirect()->route('jadwalauth.index', ['tampilan' => 'ramadan'])
            ->with('success', $successMsg);
    }
    /**
     * TAMPILKAN HALAMAN ANALISIS SLOT
     */
    public function showAnalisisSlot(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $semester = $request->get('semester', 'ganjil');
        $tahunAjaran = $request->get('tahun_ajaran', date('Y'));

        $analisis = $this->analisisKebutuhanSlot($semester, $tahunAjaran);

        $jumlahRuangan = Ruangan::count();
        $rataSlotPerHari = !empty($analisis['slots_per_day']) ? round(array_sum($analisis['slots_per_day']) / count($analisis['slots_per_day']), 1) : 0;

        return view('jadwalauth.analisis_slot', array_merge($analisis, [
            'semester' => $semester,
            'tahunAjaran' => $tahunAjaran,
            'jumlahRuangan' => $jumlahRuangan,
            'rataSlotPerHari' => $rataSlotPerHari,
        ]));
    }

    /**
     * CEK MATA KULIAH TANPA DOSEN PENGAMPU (SEMUA PRODI)
     */
    private function checkMatkulTanpaDosen()
    {
        $matkuls = MataKuliah::all();
        $tanpaDosen = [];
        foreach ($matkuls as $mk) {
            $dosenIds = $mk->dosen_id;
            if (is_string($dosenIds)) {
                $dosenIds = json_decode($dosenIds, true);
            }
            if (empty($dosenIds)) {
                $tanpaDosen[] = $mk;
            }
        }
        return $tanpaDosen;
    }

    /**
     * CEK DOSEN OVER KAPASITAS
     */
    private function checkDosenOverCapacity($semester = null)
    {
        $dosens = Dosen::all();
        $violations = [];

        foreach ($dosens as $dosen) {
            $maxSlot = $dosen->getJumlahSlotTersediaAttribute();
            if ($maxSlot !== null) {
                $totalSks = $semester
                    ? $dosen->getTotalSksDiampuPerSemester($semester)
                    : $dosen->getTotalSksDiampu();
                if ($totalSks > $maxSlot) {
                    $violations[] = (object)[
                        'dosen_id' => $dosen->id,
                        'dosen_nama' => $dosen->nama,
                        'total_sks' => $totalSks,
                        'max_slot' => $maxSlot,
                    ];
                }
            }
        }

        return collect($violations);
    }

    /**
     * CEK KELAS OVER KAPASITAS
     */
    private function checkKelasCapacityPerProdi()
    {
        $ruangansByProdi = DB::table('ruangans')
            ->select('prodi_id', DB::raw('MAX(kapasitas) as max_kapasitas'))
            ->whereNotNull('prodi_id')
            ->groupBy('prodi_id')
            ->get()
            ->keyBy('prodi_id');

        $maxKapasitasUmum = DB::table('ruangans')
            ->whereNull('prodi_id')
            ->max('kapasitas') ?? 0;

        $results = DB::table('kelas')
            ->join('angkatans', 'kelas.angkatan_id', '=', 'angkatans.id')
            ->join('prodis', 'angkatans.prodi_id', '=', 'prodis.id')
            ->select(
                'kelas.id as kelas_id',
                'kelas.nama as kelas_nama',
                'kelas.kapasitas as kapasitas_kelas',
                'prodis.id as prodi_id',
                'prodis.nama as prodi_nama'
            )
            ->get();

        $violations = [];

        foreach ($results as $kelas) {
            $maxKapasitasProdi = $ruangansByProdi[$kelas->prodi_id]->max_kapasitas ?? 0;
            $maxKapasitasTersedia = max($maxKapasitasProdi, $maxKapasitasUmum);

            if ($kelas->kapasitas_kelas > $maxKapasitasTersedia) {
                $violations[] = (object)[
                    'kelas_id' => $kelas->kelas_id,
                    'kelas_nama' => $kelas->kelas_nama,
                    'prodi_nama' => $kelas->prodi_nama,
                    'kapasitas_kelas' => $kelas->kapasitas_kelas,
                    'max_kapasitas_prodi' => $maxKapasitasProdi,
                    'max_kapasitas_umum' => $maxKapasitasUmum,
                    'max_kapasitas_tersedia' => $maxKapasitasTersedia
                ];
            }
        }

        return collect($violations);
    }

    /**
     * ANALISIS KEBUTUHAN SLOT (BERDASARKAN SKS DARI TABEL MATA KULIAH)
     */
    private function analisisKebutuhanSlot($semester, $tahunAjaran)
    {
        // 1. HITUNG KEBUTUHAN SLOT (BERDASARKAN SKS)
        $matkuls = MataKuliah::with('prodi')->where('semester', $semester)->get();

        $sksPerProdi = [];
        foreach ($matkuls as $matkul) {
            $prodiId = $matkul->prodi_id;
            $semesterKe = $matkul->semester_ke;
            $sks = $matkul->sks;

            if (!isset($sksPerProdi[$prodiId])) {
                $sksPerProdi[$prodiId] = [];
            }
            if (!isset($sksPerProdi[$prodiId][$semesterKe])) {
                $sksPerProdi[$prodiId][$semesterKe] = 0;
            }
            $sksPerProdi[$prodiId][$semesterKe] += $sks;
        }

        $kelasPerProdi = [];
        $kelasList = Kelas::with('angkatan.prodi')->get();
        foreach ($kelasList as $kelas) {
            $prodiId = $kelas->angkatan->prodi_id;
            $tahun = $kelas->angkatan->tahun;

            if (!isset($kelasPerProdi[$prodiId])) {
                $kelasPerProdi[$prodiId] = [];
            }
            if (!isset($kelasPerProdi[$prodiId][$tahun])) {
                $kelasPerProdi[$prodiId][$tahun] = 0;
            }
            $kelasPerProdi[$prodiId][$tahun]++;
        }

        $results = [];
        $totalKebutuhan = 0;
        $prodis = Prodi::all();

        foreach ($prodis as $prodi) {
            $kebutuhan = 0;
            $sksProdi = $sksPerProdi[$prodi->id] ?? [];
            $kelasProdi = $kelasPerProdi[$prodi->id] ?? [];

            foreach ($kelasProdi as $tahun => $jumlahKelas) {
                $semesterKe = (($tahunAjaran - $tahun) * 2) + 1;

                if ($semesterKe >= 1 && $semesterKe <= 8) {
                    $totalSksPerAngkatan = $sksProdi[$semesterKe] ?? 0;
                    $kebutuhan += $jumlahKelas * $totalSksPerAngkatan;
                }
            }

            $results[$prodi->nama] = $kebutuhan;
            $totalKebutuhan += $kebutuhan;
        }

        // 2. HITUNG KAPASITAS RUANGAN (DARI TABEL SLOTS)
        $ruangans = Ruangan::all();
        $slots = Slot::where('is_active', true)->get();

        $slotsPerDay = [];
        foreach ($slots as $slot) {
            if (!isset($slotsPerDay[$slot->hari])) {
                $slotsPerDay[$slot->hari] = 0;
            }
            $slotsPerDay[$slot->hari]++;
        }

        $hariEfektif = [1, 2, 3, 4, 5];

        $totalKapasitas = 0;
        foreach ($ruangans as $ruangan) {
            foreach ($hariEfektif as $hari) {
                $totalKapasitas += $slotsPerDay[$hari] ?? 0;
            }
        }

        // 3. STATUS
        $isCukup = $totalKebutuhan <= $totalKapasitas;
        $selisih = $totalKapasitas - $totalKebutuhan;
        $persentase = $totalKapasitas > 0 ? round(($totalKebutuhan / $totalKapasitas) * 100, 2) : 0;

        Log::info("=== ANALISIS KEBUTUHAN SLOT ===");
        Log::info("Semester: {$semester}, Tahun Ajaran: {$tahunAjaran}");
        Log::info("Total Kebutuhan (SKS): {$totalKebutuhan}");
        Log::info("Total Kapasitas: {$totalKapasitas}");
        Log::info("Status: " . ($isCukup ? "CUKUP" : "TIDAK CUKUP"));

        return [
            'is_cukup' => $isCukup,
            'total_kebutuhan' => $totalKebutuhan,
            'total_kapasitas' => $totalKapasitas,
            'selisih' => $selisih,
            'persentase' => $persentase,
            'detail_prodi' => $results,
            'slots_per_day' => $slotsPerDay
        ];
    }

    private function getNamaHari($hari)
    {
        $namaHari = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu'
        ];
        return $namaHari[$hari] ?? 'Hari';
    }

    /**
     * TAMPILKAN PERINGATAN KAPASITAS
     */
    public function showCapacityWarning()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $role = $user->role;

        $query = DB::table('kelas')
            ->join('angkatans', 'kelas.angkatan_id', '=', 'angkatans.id')
            ->join('prodis', 'angkatans.prodi_id', '=', 'prodis.id')
            ->select(
                'kelas.id',
                'kelas.nama as kelas_nama',
                'kelas.kapasitas as kapasitas_kelas',
                'prodis.nama as prodi_nama',
                'prodis.id as prodi_id'
            );

        if ($role == 'kaprodi') {
            $query->where('prodis.id', $user->prodi_id);
        }

        $kelasList = $query->get();

        $ruanganPerProdi = DB::table('ruangans')
            ->select('prodi_id', DB::raw('MAX(kapasitas) as max_kapasitas'), DB::raw('MIN(kapasitas) as min_kapasitas'))
            ->whereNotNull('prodi_id')
            ->groupBy('prodi_id')
            ->get()
            ->keyBy('prodi_id');

        $ruanganUmum = DB::table('ruangans')
            ->whereNull('prodi_id')
            ->select('nama', 'kapasitas')
            ->orderBy('kapasitas', 'desc')
            ->get();

        $maxKapasitasUmum = $ruanganUmum->max('kapasitas') ?? 0;
        $minKapasitasUmum = $ruanganUmum->min('kapasitas') ?? 0;

        $allRuangan = DB::table('ruangans')
            ->leftJoin('prodis', 'ruangans.prodi_id', '=', 'prodis.id')
            ->select('ruangans.nama', 'ruangans.kapasitas', 'prodis.nama as prodi_nama')
            ->orderBy('ruangans.kapasitas', 'desc')
            ->get();

        foreach ($kelasList as $kelas) {
            $maxKapasitasProdi = $ruanganPerProdi[$kelas->prodi_id]->max_kapasitas ?? 0;
            $maxKapasitasTersedia = max($maxKapasitasProdi, $maxKapasitasUmum);

            $kelas->is_over_capacity = $kelas->kapasitas_kelas > $maxKapasitasTersedia;
            $kelas->max_kapasitas_prodi = $maxKapasitasProdi;
            $kelas->max_kapasitas_umum = $maxKapasitasUmum;
            $kelas->max_kapasitas_tersedia = $maxKapasitasTersedia;
            $kelas->warning_message = '';

            if ($kelas->is_over_capacity) {
                $kelas->warning_message = "⚠️ KRITIS! Kapasitas kelas ({$kelas->kapasitas_kelas}) melebihi kapasitas ruangan terbesar yang tersedia";
                if ($maxKapasitasProdi > 0) {
                    $kelas->warning_message .= " di prodi {$kelas->prodi_nama} ({$maxKapasitasProdi})";
                }
                if ($maxKapasitasUmum > 0) {
                    $kelas->warning_message .= " dan ruangan umum ({$maxKapasitasUmum})";
                }
                $kelas->warning_message .= "! Harap kurangi kapasitas kelas atau tambah ruangan.";
            } elseif ($kelas->kapasitas_kelas > $maxKapasitasProdi && $maxKapasitasProdi > 0) {
                $kelas->warning_message = "⚠️ Kapasitas kelas ({$kelas->kapasitas_kelas}) melebihi kapasitas ruangan se-prodi ({$maxKapasitasProdi}). Akan menggunakan ruangan umum (max: {$maxKapasitasUmum})";
            } elseif ($kelas->kapasitas_kelas > $maxKapasitasUmum && $maxKapasitasUmum > 0) {
                $kelas->warning_message = "⚠️ Kapasitas kelas ({$kelas->kapasitas_kelas}) melebihi kapasitas ruangan umum ({$maxKapasitasUmum}). Pastikan ada ruangan se-prodi yang cukup.";
            } else {
                $kelas->warning_message = "✓ Kapasitas kelas {$kelas->kapasitas_kelas} masih dalam batas ruangan yang tersedia.";
            }
        }

        return view('jadwalauth.capacity_warning', compact('kelasList', 'allRuangan', 'ruanganUmum', 'role', 'maxKapasitasUmum', 'minKapasitasUmum'));
    }
}
