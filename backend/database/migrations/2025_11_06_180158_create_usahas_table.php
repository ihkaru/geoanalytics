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
        Schema::create('usahas', function (Blueprint $table) {
            $table->string('idsbr')->primary();
            $table->string('nama_usaha')->nullable();
            $table->text('alamat')->nullable();
            $table->string('kdprov', 2)->nullable();
            $table->string('kdkab', 2)->nullable();
            $table->string('kdkec', 3)->nullable()->index();
            $table->string('kddesa', 3)->nullable();
            $table->integer('status_usaha')->nullable()->index();
            $table->string('skala_usaha')->nullable()->index();
            $table->string('nama_komersial_usaha')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 11, 7)->nullable();
            $table->string('nomor_whatsapp')->nullable();
            $table->string('idsubsls', 16)->nullable();
            $table->text('kegiatan_usaha')->nullable();
            $table->string('kategori_usaha', 1)->nullable();
            $table->string('kbli', 5)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usahas');
    }
};
