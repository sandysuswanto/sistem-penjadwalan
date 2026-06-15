<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('mata_kuliahs', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique();
            $table->string('nama', 200);
            $table->foreignId('prodi_id')->constrained()->onDelete('cascade');
            $table->integer('sks'); // 2 atau 3
            $table->integer('semester_ke'); // 1..8
            $table->enum('semester', ['ganjil', 'genap']);
            $table->json('dosen_id')->nullable(); // array of dosen IDs
            $table->foreignId('ruangan_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mata_kuliahs');
    }
};
