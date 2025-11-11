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
        Schema::create('analisis_zona_cache', function (Blueprint $table) {
            $table->string('idsubsls');
            $table->string('kbli_5_digit');
            $table->integer('radius_meter');
            $table->float('skor_potensi')->nullable();
            $table->string('zona')->nullable();

            // Composite primary key
            $table->primary(['idsubsls', 'kbli_5_digit', 'radius_meter']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analisis_zona_cache');
    }
};
