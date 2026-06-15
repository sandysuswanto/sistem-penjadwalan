<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal_ramadan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_asli_id')->nullable()->constrained('jadwals')->onDelete('set null');
            $table->foreignId('mata_kuliah_id')->constrained('mata_kuliahs');
            $table->foreignId('kelas_id')->constrained('kelas');
            $table->foreignId('ruangan_id')->constrained('ruangans');
            $table->foreignId('dosen_id')->constrained('dosens');
            $table->string('hari'); // Senin, Selasa, etc
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->string('semester'); // ganjil/genap
            $table->integer('durasi_per_sks')->default(35); // menit
            $table->timestamps();

            // Optional: unique constraint to avoid duplicate
            $table->unique(['mata_kuliah_id', 'kelas_id', 'hari', 'jam_mulai'], 'unique_jadwal_ramadan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_ramadan');
    }
};
