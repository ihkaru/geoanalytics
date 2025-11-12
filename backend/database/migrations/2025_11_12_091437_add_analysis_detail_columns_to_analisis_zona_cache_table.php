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
        Schema::table('analisis_zona_cache', function (Blueprint $table) {
            $table->integer('jumlah_penduduk_total')->nullable();
            $table->integer('jumlah_usaha_sejenis')->nullable();
            $table->decimal('rasio_penduduk_per_usaha', 8, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('analisis_zona_cache', function (Blueprint $table) {
            $table->dropColumn(['jumlah_penduduk_total', 'jumlah_usaha_sejenis', 'rasio_penduduk_per_usaha']);
        });
    }
};