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
        Schema::create('demografi_sls', function (Blueprint $table) {
            $table->string('idsubsls')->primary();
            $table->string('kode_desa')->nullable();
            $table->string('nama_desa')->nullable();
            $table->integer('jumlah_penduduk')->default(0);
            $table->integer('jumlah_kk')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demografi_sls');
    }
};
