<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dosens', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('slot_tersedia');
        });
        Schema::table('kelas', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('kapasitas');
        });
        Schema::table('angkatans', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('tahun');
        });
        Schema::table('mata_kuliahs', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('ruangan_id');
        });
        Schema::table('ruangans', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('prodi_id');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('role');
        });
        Schema::table('prodis', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('validation_notes');
        });
    }

    public function down(): void
    {
        Schema::table('dosens', fn (Blueprint $t) => $t->dropColumn('is_active'));
        Schema::table('kelas', fn (Blueprint $t) => $t->dropColumn('is_active'));
        Schema::table('angkatans', fn (Blueprint $t) => $t->dropColumn('is_active'));
        Schema::table('mata_kuliahs', fn (Blueprint $t) => $t->dropColumn('is_active'));
        Schema::table('ruangans', fn (Blueprint $t) => $t->dropColumn('is_active'));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('is_active'));
        Schema::table('prodis', fn (Blueprint $t) => $t->dropColumn('is_active'));
    }
};
