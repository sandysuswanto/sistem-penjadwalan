<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('jadwals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mata_kuliah_id')->constrained();
            $table->foreignId('kelas_id')->constrained();
            $table->foreignId('dosen_id')->constrained();
            $table->foreignId('ruangan_id')->constrained();
            $table->tinyInteger('hari');      // 1=Senin ... 6=Sabtu
            $table->tinyInteger('slot_mulai'); // 0..11
            $table->timestamps();

            // Index untuk menghindari bentrok cepat
            $table->unique(['kelas_id', 'hari', 'slot_mulai'], 'unique_kelas_slot');
            $table->unique(['ruangan_id', 'hari', 'slot_mulai'], 'unique_ruang_slot');
            $table->unique(['dosen_id', 'hari', 'slot_mulai'], 'unique_dosen_slot');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jadwals');
    }
};
