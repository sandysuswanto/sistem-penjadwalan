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
        Schema::create('aco_parameters', function (Blueprint $table) {
            $table->id();
            $table->float('alpha');
            $table->float('beta');
            $table->float('rho');
            $table->float('q');
            $table->integer('ant_count');
            $table->integer('iterations');
            $table->float('best_fitness');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aco_parameters');
    }
};
