<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('muatan_subsls', function (Blueprint $table) {
            $table->string('idsubsls')->primary();
            $table->string('semester')->nullable();
            $table->string('idsls')->nullable();
            $table->string('nmsls')->nullable();
            $table->string('nama_ketua')->nullable();
            $table->string('jenis')->nullable();
            $table->string('kdprov')->nullable();
            $table->string('kdkab')->nullable();
            $table->string('kdkec')->nullable();
            $table->string('kddesa')->nullable();
            $table->string('kdsls')->nullable();
            $table->string('kdsubsls')->nullable();
            $table->integer('klas')->nullable();
            $table->string('nmprov')->nullable();
            $table->string('nmkab')->nullable();
            $table->string('nmkec')->nullable();
            $table->string('nmdesa')->nullable();
            $table->integer('kk')->nullable();
            $table->integer('btt')->nullable();
            $table->integer('bttk')->nullable();
            $table->integer('bku')->nullable();
            $table->integer('bbtt_nonusaha')->nullable();
            $table->integer('usaha')->nullable();
            $table->integer('muatan')->nullable();
            $table->integer('dominan')->nullable();
            $table->integer('berubah_batas')->nullable();
            $table->integer('id')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('jam_operasional')->nullable();
            $table->string('nm_ekonomi')->nullable();
            $table->string('shift')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('muatan_subsls');
    }
};
