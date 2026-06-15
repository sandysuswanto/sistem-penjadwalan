<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slots', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('hari'); // 1=Senin, 2=Selasa, ..., 6=Sabtu
            $table->tinyInteger('slot_ke'); // 1-12 (mulai dari 0 atau 1)
            $table->string('jam_mulai', 10);
            $table->string('jam_selesai', 10);
            $table->tinyInteger('durasi_sks'); // minimal SKS yang bisa diisi (1, 2, 3)
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['hari', 'slot_ke']);
        });

        // Seed data slot
        $this->seedSlots();
    }

    private function seedSlots()
    {
        // Definisikan slot waktu (dimulai dari jam 07:30, tiap slot 100 menit / 2 SKS)
        // Senin (hari=1)
        $slots = [
            // Senin (hari=1)
            ['hari' => 1, 'slot_ke' => 0, 'jam_mulai' => '07:30', 'jam_selesai' => '08:20', 'durasi_sks' => 1],
            ['hari' => 1, 'slot_ke' => 1, 'jam_mulai' => '08:20', 'jam_selesai' => '09:10', 'durasi_sks' => 1],
            ['hari' => 1, 'slot_ke' => 2, 'jam_mulai' => '09:10', 'jam_selesai' => '10:00', 'durasi_sks' => 1],
            ['hari' => 1, 'slot_ke' => 3, 'jam_mulai' => '10:00', 'jam_selesai' => '10:50', 'durasi_sks' => 1],
            ['hari' => 1, 'slot_ke' => 4, 'jam_mulai' => '10:50', 'jam_selesai' => '11:40', 'durasi_sks' => 1],
            ['hari' => 1, 'slot_ke' => 5, 'jam_mulai' => '11:40', 'jam_selesai' => '12:30', 'durasi_sks' => 1],
            ['hari' => 1, 'slot_ke' => 6, 'jam_mulai' => '12:30', 'jam_selesai' => '13:20', 'durasi_sks' => 1],
            ['hari' => 1, 'slot_ke' => 7, 'jam_mulai' => '13:20', 'jam_selesai' => '14:10', 'durasi_sks' => 1],
            ['hari' => 1, 'slot_ke' => 8, 'jam_mulai' => '14:10', 'jam_selesai' => '15:00', 'durasi_sks' => 1],
            ['hari' => 1, 'slot_ke' => 9, 'jam_mulai' => '15:00', 'jam_selesai' => '15:50', 'durasi_sks' => 1],
            ['hari' => 1, 'slot_ke' => 10, 'jam_mulai' => '15:50', 'jam_selesai' => '16:40', 'durasi_sks' => 1],
            ['hari' => 1, 'slot_ke' => 11, 'jam_mulai' => '16:40', 'jam_selesai' => '17:30', 'durasi_sks' => 1],

            // Selasa (hari=2)
            ['hari' => 2, 'slot_ke' => 0, 'jam_mulai' => '07:30', 'jam_selesai' => '08:20', 'durasi_sks' => 1],
            ['hari' => 2, 'slot_ke' => 1, 'jam_mulai' => '08:20', 'jam_selesai' => '09:10', 'durasi_sks' => 1],
            ['hari' => 2, 'slot_ke' => 2, 'jam_mulai' => '09:10', 'jam_selesai' => '10:00', 'durasi_sks' => 1],
            ['hari' => 2, 'slot_ke' => 3, 'jam_mulai' => '10:00', 'jam_selesai' => '10:50', 'durasi_sks' => 1],
            ['hari' => 2, 'slot_ke' => 4, 'jam_mulai' => '10:50', 'jam_selesai' => '11:40', 'durasi_sks' => 1],
            ['hari' => 2, 'slot_ke' => 5, 'jam_mulai' => '11:40', 'jam_selesai' => '12:30', 'durasi_sks' => 1],
            ['hari' => 2, 'slot_ke' => 6, 'jam_mulai' => '12:30', 'jam_selesai' => '13:20', 'durasi_sks' => 1],
            ['hari' => 2, 'slot_ke' => 7, 'jam_mulai' => '13:20', 'jam_selesai' => '14:10', 'durasi_sks' => 1],
            ['hari' => 2, 'slot_ke' => 8, 'jam_mulai' => '14:10', 'jam_selesai' => '15:00', 'durasi_sks' => 1],
            ['hari' => 2, 'slot_ke' => 9, 'jam_mulai' => '15:00', 'jam_selesai' => '15:50', 'durasi_sks' => 1],
            ['hari' => 2, 'slot_ke' => 10, 'jam_mulai' => '15:50', 'jam_selesai' => '16:40', 'durasi_sks' => 1],
            ['hari' => 2, 'slot_ke' => 11, 'jam_mulai' => '16:40', 'jam_selesai' => '17:30', 'durasi_sks' => 1],

            // Rabu (hari=3)
            ['hari' => 3, 'slot_ke' => 0, 'jam_mulai' => '07:30', 'jam_selesai' => '08:20', 'durasi_sks' => 1],
            ['hari' => 3, 'slot_ke' => 1, 'jam_mulai' => '08:20', 'jam_selesai' => '09:10', 'durasi_sks' => 1],
            ['hari' => 3, 'slot_ke' => 2, 'jam_mulai' => '09:10', 'jam_selesai' => '10:00', 'durasi_sks' => 1],
            ['hari' => 3, 'slot_ke' => 3, 'jam_mulai' => '10:00', 'jam_selesai' => '10:50', 'durasi_sks' => 1],
            ['hari' => 3, 'slot_ke' => 4, 'jam_mulai' => '10:50', 'jam_selesai' => '11:40', 'durasi_sks' => 1],
            ['hari' => 3, 'slot_ke' => 5, 'jam_mulai' => '11:40', 'jam_selesai' => '12:30', 'durasi_sks' => 1],
            ['hari' => 3, 'slot_ke' => 6, 'jam_mulai' => '12:30', 'jam_selesai' => '13:20', 'durasi_sks' => 1],
            ['hari' => 3, 'slot_ke' => 7, 'jam_mulai' => '13:20', 'jam_selesai' => '14:10', 'durasi_sks' => 1],
            ['hari' => 3, 'slot_ke' => 8, 'jam_mulai' => '14:10', 'jam_selesai' => '15:00', 'durasi_sks' => 1],
            ['hari' => 3, 'slot_ke' => 9, 'jam_mulai' => '15:00', 'jam_selesai' => '15:50', 'durasi_sks' => 1],
            ['hari' => 3, 'slot_ke' => 10, 'jam_mulai' => '15:50', 'jam_selesai' => '16:40', 'durasi_sks' => 1],
            ['hari' => 3, 'slot_ke' => 11, 'jam_mulai' => '16:40', 'jam_selesai' => '17:30', 'durasi_sks' => 1],

            // Kamis (hari=4)
            ['hari' => 4, 'slot_ke' => 0, 'jam_mulai' => '07:30', 'jam_selesai' => '08:20', 'durasi_sks' => 1],
            ['hari' => 4, 'slot_ke' => 1, 'jam_mulai' => '08:20', 'jam_selesai' => '09:10', 'durasi_sks' => 1],
            ['hari' => 4, 'slot_ke' => 2, 'jam_mulai' => '09:10', 'jam_selesai' => '10:00', 'durasi_sks' => 1],
            ['hari' => 4, 'slot_ke' => 3, 'jam_mulai' => '10:00', 'jam_selesai' => '10:50', 'durasi_sks' => 1],
            ['hari' => 4, 'slot_ke' => 4, 'jam_mulai' => '10:50', 'jam_selesai' => '11:40', 'durasi_sks' => 1],
            ['hari' => 4, 'slot_ke' => 5, 'jam_mulai' => '11:40', 'jam_selesai' => '12:30', 'durasi_sks' => 1],
            ['hari' => 4, 'slot_ke' => 6, 'jam_mulai' => '12:30', 'jam_selesai' => '13:20', 'durasi_sks' => 1],
            ['hari' => 4, 'slot_ke' => 7, 'jam_mulai' => '13:20', 'jam_selesai' => '14:10', 'durasi_sks' => 1],
            ['hari' => 4, 'slot_ke' => 8, 'jam_mulai' => '14:10', 'jam_selesai' => '15:00', 'durasi_sks' => 1],
            ['hari' => 4, 'slot_ke' => 9, 'jam_mulai' => '15:00', 'jam_selesai' => '15:50', 'durasi_sks' => 1],
            ['hari' => 4, 'slot_ke' => 10, 'jam_mulai' => '15:50', 'jam_selesai' => '16:40', 'durasi_sks' => 1],
            ['hari' => 4, 'slot_ke' => 11, 'jam_mulai' => '16:40', 'jam_selesai' => '17:30', 'durasi_sks' => 1],

            // Jumat (hari=5) - s/d jam 15:50 (karena Jumat lebih pendek)
            ['hari' => 5, 'slot_ke' => 0, 'jam_mulai' => '07:30', 'jam_selesai' => '08:20', 'durasi_sks' => 1],
            ['hari' => 5, 'slot_ke' => 1, 'jam_mulai' => '08:20', 'jam_selesai' => '09:10', 'durasi_sks' => 1],
            ['hari' => 5, 'slot_ke' => 2, 'jam_mulai' => '09:10', 'jam_selesai' => '10:00', 'durasi_sks' => 1],
            ['hari' => 5, 'slot_ke' => 3, 'jam_mulai' => '10:00', 'jam_selesai' => '10:50', 'durasi_sks' => 1],
            ['hari' => 5, 'slot_ke' => 4, 'jam_mulai' => '10:50', 'jam_selesai' => '11:40', 'durasi_sks' => 1],
            ['hari' => 5, 'slot_ke' => 5, 'jam_mulai' => '11:40', 'jam_selesai' => '12:30', 'durasi_sks' => 1],
            ['hari' => 5, 'slot_ke' => 6, 'jam_mulai' => '12:30', 'jam_selesai' => '13:20', 'durasi_sks' => 1],
            ['hari' => 5, 'slot_ke' => 7, 'jam_mulai' => '13:20', 'jam_selesai' => '14:10', 'durasi_sks' => 1],
            ['hari' => 5, 'slot_ke' => 8, 'jam_mulai' => '14:10', 'jam_selesai' => '15:00', 'durasi_sks' => 1],
            ['hari' => 5, 'slot_ke' => 9, 'jam_mulai' => '15:00', 'jam_selesai' => '15:50', 'durasi_sks' => 1],

            // Sabtu (hari=6)
            ['hari' => 6, 'slot_ke' => 0, 'jam_mulai' => '07:30', 'jam_selesai' => '08:20', 'durasi_sks' => 1],
            ['hari' => 6, 'slot_ke' => 1, 'jam_mulai' => '08:20', 'jam_selesai' => '09:10', 'durasi_sks' => 1],
            ['hari' => 6, 'slot_ke' => 2, 'jam_mulai' => '09:10', 'jam_selesai' => '10:00', 'durasi_sks' => 1],
            ['hari' => 6, 'slot_ke' => 3, 'jam_mulai' => '10:00', 'jam_selesai' => '10:50', 'durasi_sks' => 1],
            ['hari' => 6, 'slot_ke' => 4, 'jam_mulai' => '10:50', 'jam_selesai' => '11:40', 'durasi_sks' => 1],
            ['hari' => 6, 'slot_ke' => 5, 'jam_mulai' => '11:40', 'jam_selesai' => '12:30', 'durasi_sks' => 1],
            ['hari' => 6, 'slot_ke' => 6, 'jam_mulai' => '12:30', 'jam_selesai' => '13:20', 'durasi_sks' => 1],
            ['hari' => 6, 'slot_ke' => 7, 'jam_mulai' => '13:20', 'jam_selesai' => '14:10', 'durasi_sks' => 1],
            ['hari' => 6, 'slot_ke' => 8, 'jam_mulai' => '14:10', 'jam_selesai' => '15:00', 'durasi_sks' => 1],
            ['hari' => 6, 'slot_ke' => 9, 'jam_mulai' => '15:00', 'jam_selesai' => '15:50', 'durasi_sks' => 1],
            ['hari' => 6, 'slot_ke' => 10, 'jam_mulai' => '15:50', 'jam_selesai' => '16:40', 'durasi_sks' => 1],
            ['hari' => 6, 'slot_ke' => 11, 'jam_mulai' => '16:40', 'jam_selesai' => '17:30', 'durasi_sks' => 1],
        ];
        foreach ($slots as $slot) {
            DB::table('slots')->insert($slot);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('slots');
    }
};
