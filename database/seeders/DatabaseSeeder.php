<?php

namespace Database\Seeders;

use App\Models\Angkatan;
use App\Models\Dosen;
use App\Models\Kelas;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Ruangan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // 1. Buat 3 Prodi
        $prodiList = [
            ['kode' => 'IF', 'nama' => 'Informatika'],
            ['kode' => 'SI', 'nama' => 'Sipil'],
            ['kode' => 'SIS', 'nama' => 'Sistem Informasi'],
        ];
        $prodis = [];
        foreach ($prodiList as $p) {
            $prodis[$p['kode']] = Prodi::create($p);
        }

        // 2. Buat Angkatan untuk setiap prodi (tahun 2022,2023,2024,2025)
        $tahunList = [2022, 2023, 2024, 2025];
        $angkatans = [];
        foreach ($prodis as $kode => $prodi) {
            foreach ($tahunList as $tahun) {
                $angkatan = Angkatan::create([
                    'prodi_id' => $prodi->id,
                    'tahun' => $tahun,
                ]);
                $angkatans[$kode][$tahun] = $angkatan;
            }
        }

        // 3. Buat Kelas untuk setiap angkatan (2 kelas: A dan B)
        foreach ($angkatans as $prodiKode => $angkatanByTahun) {
            foreach ($angkatanByTahun as $tahun => $angkatan) {
                Kelas::create(['angkatan_id' => $angkatan->id, 'nama' => 'A', 'kapasitas' => 40]);
                Kelas::create(['angkatan_id' => $angkatan->id, 'nama' => 'B', 'kapasitas' => 40]);
            }
        }

        // 4. Buat Dosen per prodi dengan slot_tersedia (bukan hari_tersedia)
        $dosenPerProdi = [
            'IF' => [
                ['nidn' => 'IF001', 'nama' => 'Dr. Andi Wijaya', 'slot_tersedia' => null], // null = semua slot
                ['nidn' => 'IF002', 'nama' => 'Prof. Budi Santoso', 'slot_tersedia' => null],
                ['nidn' => 'IF003', 'nama' => 'Dr. Citra Dewi', 'slot_tersedia' => null],
                ['nidn' => 'IF004', 'nama' => 'Ir. Dedi Kurniawan', 'slot_tersedia' => null],
            ],
            'SI' => [
                ['nidn' => 'SI001', 'nama' => 'Dra. Eka Putri', 'slot_tersedia' => null],
                ['nidn' => 'SI002', 'nama' => 'Dr. Fajar Setiawan', 'slot_tersedia' => null],
                ['nidn' => 'SI003', 'nama' => 'Prof. Gita Lestari', 'slot_tersedia' => null],
                ['nidn' => 'SI004', 'nama' => 'Ir. Hadi Prasetyo', 'slot_tersedia' => null],
            ],
            'SIS' => [
                ['nidn' => 'SIS001', 'nama' => 'Dr. Indah Purnama', 'slot_tersedia' => null],
                ['nidn' => 'SIS002', 'nama' => 'Dr. Joko Susilo', 'slot_tersedia' => null],
                ['nidn' => 'SIS003', 'nama' => 'Prof. Kurniawan', 'slot_tersedia' => null],
            ],
        ];

        foreach ($prodis as $kode => $prodi) {
            if (isset($dosenPerProdi[$kode])) {
                foreach ($dosenPerProdi[$kode] as $d) {
                    Dosen::create([
                        'prodi_id' => $prodi->id,
                        'nidn' => $d['nidn'],
                        'nama' => $d['nama'],
                        'slot_tersedia' => $d['slot_tersedia'],
                    ]);
                }
            }
        }

        // 5. Buat Ruangan dengan prodi_id
        $ruanganPerProdi = [
            'IF' => [
                ['kode' => '18.8', 'nama' => 'Informatika 1', 'kapasitas' => 30, 'is_lab' => true],
                ['kode' => '18.9', 'nama' => 'Informatika 2', 'kapasitas' => 40, 'is_lab' => true],
                ['kode' => '18.10', 'nama' => 'Informatika Teori', 'kapasitas' => 50, 'is_lab' => false],
            ],
            'SI' => [
                ['kode' => '15.4', 'nama' => 'Sipil 1', 'kapasitas' => 25, 'is_lab' => true],
                ['kode' => '15.3', 'nama' => 'Sipil 2', 'kapasitas' => 25, 'is_lab' => true],
                ['kode' => '18.4', 'nama' => 'Sipil 3', 'kapasitas' => 50, 'is_lab' => false],
            ],
            'SIS' => [
                ['kode' => '18.3', 'nama' => 'Sistem Informasi 1', 'kapasitas' => 30, 'is_lab' => true],
                ['kode' => '18.5', 'nama' => 'Sistem Informasi 2', 'kapasitas' => 35, 'is_lab' => true],
                ['kode' => '18.6', 'nama' => 'Sistem Informasi Teori', 'kapasitas' => 45, 'is_lab' => false],
            ],
        ];

        $ruangans = collect();
        foreach ($prodis as $kode => $prodi) {
            if (isset($ruanganPerProdi[$kode])) {
                foreach ($ruanganPerProdi[$kode] as $r) {
                    $ruangan = Ruangan::create([
                        'kode' => $r['kode'],
                        'nama' => $r['nama'],
                        'kapasitas' => $r['kapasitas'],
                        'is_lab' => $r['is_lab'],
                        'prodi_id' => $prodi->id,
                    ]);
                    $ruangans->push($ruangan);
                }
            }
        }

        // Ruangan umum (prodi_id = null)
        $ruanganUmum = [
            ['kode' => '18.1', 'nama' => 'Lab Komputer Umum 1', 'kapasitas' => 100, 'is_lab' => true, 'prodi_id' => null],
            ['kode' => '18.2', 'nama' => 'Lab Komputer Umum 2', 'kapasitas' => 80, 'is_lab' => true, 'prodi_id' => null],
            ['kode' => '18.7', 'nama' => 'Ruang Teori Umum', 'kapasitas' => 120, 'is_lab' => false, 'prodi_id' => null],
        ];

        foreach ($ruanganUmum as $r) {
            $ruangan = Ruangan::create($r);
            $ruangans->push($ruangan);
        }

        // Helper function untuk random ruangan (BISA NULL)
        $getRandomRuangan = function ($prodiId, $isLab = false) use ($ruangans) {
            // Filter ruangan milik prodi yang sama
            $filtered = $ruangans->where('prodi_id', $prodiId)->where('is_lab', $isLab);

            // Jika tidak ada, ambil ruangan umum
            if ($filtered->isEmpty()) {
                $filtered = $ruangans->whereNull('prodi_id')->where('is_lab', $isLab);
            }

            // Jika masih tidak ada, ambil ruangan umum apapun
            if ($filtered->isEmpty()) {
                $filtered = $ruangans->whereNull('prodi_id');
            }

            // Jika masih tidak ada, return null
            if ($filtered->isEmpty()) {
                return null;
            }

            return $filtered->random();
        };

        // 6. Mata Kuliah Semester Ganjil (ruangan_id = NULL atau random)
        $semesterGanjil = 'ganjil';
        $matkulTemplatesGanjil = [
            'IF' => [
                ['kode' => 'IF101', 'nama' => 'Dasar Pemrograman', 'sks' => 3, 'semester_ke' => 1, 'is_lab' => true],
                ['kode' => 'IF102', 'nama' => 'Algoritma dan Struktur Data', 'sks' => 3, 'semester_ke' => 1, 'is_lab' => false],
                ['kode' => 'IF103', 'nama' => 'Matematika Diskrit', 'sks' => 2, 'semester_ke' => 1, 'is_lab' => false],
                ['kode' => 'IF201', 'nama' => 'Basis Data', 'sks' => 3, 'semester_ke' => 3, 'is_lab' => true],
                ['kode' => 'IF202', 'nama' => 'Pemrograman Web', 'sks' => 3, 'semester_ke' => 3, 'is_lab' => true],
                ['kode' => 'IF203', 'nama' => 'Jaringan Komputer', 'sks' => 3, 'semester_ke' => 3, 'is_lab' => false],
                ['kode' => 'IF301', 'nama' => 'Kecerdasan Buatan', 'sks' => 3, 'semester_ke' => 5, 'is_lab' => false],
                ['kode' => 'IF302', 'nama' => 'Pemrograman Mobile', 'sks' => 3, 'semester_ke' => 5, 'is_lab' => true],
                ['kode' => 'IF401', 'nama' => 'Proyek Perangkat Lunak', 'sks' => 3, 'semester_ke' => 7, 'is_lab' => false],
                ['kode' => 'IF402', 'nama' => 'Etika Profesi', 'sks' => 2, 'semester_ke' => 7, 'is_lab' => false],
            ],
            'SI' => [
                ['kode' => 'SI101', 'nama' => 'Mekanika Teknik', 'sks' => 3, 'semester_ke' => 1, 'is_lab' => false],
                ['kode' => 'SI102', 'nama' => 'Gambar Teknik', 'sks' => 2, 'semester_ke' => 1, 'is_lab' => true],
                ['kode' => 'SI103', 'nama' => 'Matematika Teknik', 'sks' => 3, 'semester_ke' => 1, 'is_lab' => false],
                ['kode' => 'SI201', 'nama' => 'Struktur Beton', 'sks' => 3, 'semester_ke' => 3, 'is_lab' => false],
                ['kode' => 'SI202', 'nama' => 'Mekanika Tanah', 'sks' => 3, 'semester_ke' => 3, 'is_lab' => true],
                ['kode' => 'SI203', 'nama' => 'Hidrolika', 'sks' => 2, 'semester_ke' => 3, 'is_lab' => false],
                ['kode' => 'SI301', 'nama' => 'Rekayasa Lalu Lintas', 'sks' => 3, 'semester_ke' => 5, 'is_lab' => false],
                ['kode' => 'SI302', 'nama' => 'Manajemen Konstruksi', 'sks' => 3, 'semester_ke' => 5, 'is_lab' => false],
                ['kode' => 'SI401', 'nama' => 'Struktur Baja', 'sks' => 3, 'semester_ke' => 7, 'is_lab' => false],
                ['kode' => 'SI402', 'nama' => 'Teknik Pondasi', 'sks' => 2, 'semester_ke' => 7, 'is_lab' => false],
            ],
            'SIS' => [
                ['kode' => 'SIS101', 'nama' => 'Pengantar Sistem Informasi', 'sks' => 3, 'semester_ke' => 1, 'is_lab' => false],
                ['kode' => 'SIS102', 'nama' => 'Pemrograman Dasar', 'sks' => 3, 'semester_ke' => 1, 'is_lab' => true],
                ['kode' => 'SIS103', 'nama' => 'Analisis Proses Bisnis', 'sks' => 2, 'semester_ke' => 1, 'is_lab' => false],
                ['kode' => 'SIS201', 'nama' => 'Basis Data Lanjut', 'sks' => 3, 'semester_ke' => 3, 'is_lab' => true],
                ['kode' => 'SIS202', 'nama' => 'E-Business', 'sks' => 3, 'semester_ke' => 3, 'is_lab' => false],
                ['kode' => 'SIS203', 'nama' => 'Rekayasa Perangkat Lunak', 'sks' => 3, 'semester_ke' => 3, 'is_lab' => false],
                ['kode' => 'SIS301', 'nama' => 'Data Warehouse', 'sks' => 3, 'semester_ke' => 5, 'is_lab' => true],
                ['kode' => 'SIS302', 'nama' => 'Audit Sistem Informasi', 'sks' => 3, 'semester_ke' => 5, 'is_lab' => false],
                ['kode' => 'SIS401', 'nama' => 'Manajemen Proyek SI', 'sks' => 3, 'semester_ke' => 7, 'is_lab' => false],
                ['kode' => 'SIS402', 'nama' => 'Komunikasi Data', 'sks' => 2, 'semester_ke' => 7, 'is_lab' => false],
            ],
        ];

        foreach ($matkulTemplatesGanjil as $prodiKode => $matkuls) {
            $prodi = $prodis[$prodiKode];
            $dosenProdi = Dosen::where('prodi_id', $prodi->id)->get();

            foreach ($matkuls as $mk) {
                $dosen = $dosenProdi->random();

                // 50% chance ruangan_id = null, 50% random ruangan
                if (rand(0, 1) == 0) {
                    $ruangan_id = null; // Biarkan null
                } else {
                    $ruangan = $getRandomRuangan($prodi->id, $mk['is_lab']);
                    $ruangan_id = $ruangan ? $ruangan->id : null;
                }

                MataKuliah::create([
                    'kode' => $mk['kode'],
                    'nama' => $mk['nama'],
                    'prodi_id' => $prodi->id,
                    'sks' => $mk['sks'],
                    'semester_ke' => $mk['semester_ke'],
                    'semester' => $semesterGanjil,
                    'dosen_id' => json_encode([$dosen->id]),
                    'ruangan_id' => $ruangan_id,
                ]);
            }
        }

        // 7. Mata Kuliah Semester Genap (ruangan_id = NULL atau random)
        $semesterGenap = 'genap';
        $matkulTemplatesGenap = [
            'IF' => [
                ['kode' => 'IF104', 'nama' => 'Sistem Digital', 'sks' => 3, 'semester_ke' => 2, 'is_lab' => false],
                ['kode' => 'IF105', 'nama' => 'Pemrograman Berorientasi Objek', 'sks' => 3, 'semester_ke' => 2, 'is_lab' => true],
                ['kode' => 'IF204', 'nama' => 'Sistem Operasi', 'sks' => 3, 'semester_ke' => 4, 'is_lab' => false],
                ['kode' => 'IF205', 'nama' => 'Pemrograman Web Lanjut', 'sks' => 3, 'semester_ke' => 4, 'is_lab' => true],
                ['kode' => 'IF304', 'nama' => 'Analisis dan Perancangan Sistem', 'sks' => 3, 'semester_ke' => 6, 'is_lab' => false],
                ['kode' => 'IF305', 'nama' => 'Manajemen Proyek TI', 'sks' => 2, 'semester_ke' => 6, 'is_lab' => false],
                ['kode' => 'IF404', 'nama' => 'Keamanan Komputer', 'sks' => 3, 'semester_ke' => 8, 'is_lab' => false],
                ['kode' => 'IF405', 'nama' => 'Komputasi Awan', 'sks' => 2, 'semester_ke' => 8, 'is_lab' => true],
                ['kode' => 'IF406', 'nama' => 'Praktikum Jaringan', 'sks' => 2, 'semester_ke' => 8, 'is_lab' => true],
                ['kode' => 'IF407', 'nama' => 'Metodologi Penelitian', 'sks' => 2, 'semester_ke' => 8, 'is_lab' => false],
            ],
            'SI' => [
                ['kode' => 'SI104', 'nama' => 'Mekanika Fluida', 'sks' => 3, 'semester_ke' => 2, 'is_lab' => false],
                ['kode' => 'SI105', 'nama' => 'Praktikum Gambar Teknik', 'sks' => 2, 'semester_ke' => 2, 'is_lab' => true],
                ['kode' => 'SI204', 'nama' => 'Struktur Kayu', 'sks' => 3, 'semester_ke' => 4, 'is_lab' => false],
                ['kode' => 'SI205', 'nama' => 'Geoteknik', 'sks' => 3, 'semester_ke' => 4, 'is_lab' => false],
                ['kode' => 'SI304', 'nama' => 'Manajemen Proyek Konstruksi', 'sks' => 3, 'semester_ke' => 6, 'is_lab' => false],
                ['kode' => 'SI305', 'nama' => 'Rekayasa Jalan Raya', 'sks' => 3, 'semester_ke' => 6, 'is_lab' => false],
                ['kode' => 'SI404', 'nama' => 'Teknik Gempa', 'sks' => 3, 'semester_ke' => 8, 'is_lab' => false],
                ['kode' => 'SI405', 'nama' => 'Praktikum Struktur', 'sks' => 2, 'semester_ke' => 8, 'is_lab' => true],
                ['kode' => 'SI406', 'nama' => 'Penilaian Properti', 'sks' => 2, 'semester_ke' => 8, 'is_lab' => false],
                ['kode' => 'SI407', 'nama' => 'Metode Elemen Hingga', 'sks' => 3, 'semester_ke' => 8, 'is_lab' => false],
            ],
            'SIS' => [
                ['kode' => 'SIS104', 'nama' => 'Perancangan Basis Data', 'sks' => 3, 'semester_ke' => 2, 'is_lab' => true],
                ['kode' => 'SIS105', 'nama' => 'Interaksi Manusia Komputer', 'sks' => 2, 'semester_ke' => 2, 'is_lab' => false],
                ['kode' => 'SIS204', 'nama' => 'Sistem Informasi Manajemen', 'sks' => 3, 'semester_ke' => 4, 'is_lab' => false],
                ['kode' => 'SIS205', 'nama' => 'Pemrograman Visual', 'sks' => 3, 'semester_ke' => 4, 'is_lab' => true],
                ['kode' => 'SIS304', 'nama' => 'Enterprise Architecture', 'sks' => 3, 'semester_ke' => 6, 'is_lab' => false],
                ['kode' => 'SIS305', 'nama' => 'Tata Kelola TI', 'sks' => 3, 'semester_ke' => 6, 'is_lab' => false],
                ['kode' => 'SIS404', 'nama' => 'Business Intelligence', 'sks' => 3, 'semester_ke' => 8, 'is_lab' => true],
                ['kode' => 'SIS405', 'nama' => 'Manajemen Pengetahuan', 'sks' => 2, 'semester_ke' => 8, 'is_lab' => false],
                ['kode' => 'SIS406', 'nama' => 'Praktikum E-Business', 'sks' => 2, 'semester_ke' => 8, 'is_lab' => true],
                ['kode' => 'SIS407', 'nama' => 'Topik Khusus Sistem Informasi', 'sks' => 3, 'semester_ke' => 8, 'is_lab' => false],
            ],
        ];

        foreach ($matkulTemplatesGenap as $prodiKode => $matkuls) {
            $prodi = $prodis[$prodiKode];
            $dosenProdi = Dosen::where('prodi_id', $prodi->id)->get();

            foreach ($matkuls as $mk) {
                $dosen = $dosenProdi->random();

                // 50% chance ruangan_id = null, 50% random ruangan
                if (rand(0, 1) == 0) {
                    $ruangan_id = null; // Biarkan null
                } else {
                    $ruangan = $getRandomRuangan($prodi->id, $mk['is_lab']);
                    $ruangan_id = $ruangan ? $ruangan->id : null;
                }

                MataKuliah::create([
                    'kode' => $mk['kode'],
                    'nama' => $mk['nama'],
                    'prodi_id' => $prodi->id,
                    'sks' => $mk['sks'],
                    'semester_ke' => $mk['semester_ke'],
                    'semester' => $semesterGenap,
                    'dosen_id' => json_encode([$dosen->id]),
                    'ruangan_id' => $ruangan_id,
                ]);
            }
        }

        // 8. Buat User Admin dan Kaprodi
        User::create([
            'name' => 'Admin Akademik',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'prodi_id' => null,
        ]);

        foreach ($prodis as $kode => $prodi) {
            User::create([
                'name' => "Kaprodi {$prodi->nama}",
                'email' => "kaprodi.{$kode}@example.com",
                'password' => Hash::make('password'),
                'role' => 'kaprodi',
                'prodi_id' => $prodi->id,
            ]);
        }

        $this->command->info("Seeder selesai. Total:");
        $this->command->info("- Prodi: " . Prodi::count());
        $this->command->info("- Dosen: " . Dosen::count());
        $this->command->info("- Ruangan: " . Ruangan::count());
        $this->command->info("- Mata Kuliah: " . MataKuliah::count());
        $this->command->info("- User admin dan kaprodi telah dibuat.");
        $this->command->info("- Mata kuliah dengan ruangan_id NULL: " . MataKuliah::whereNull('ruangan_id')->count());
    }
}
