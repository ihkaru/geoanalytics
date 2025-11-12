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
        Schema::create('referensi_kbli', function (Blueprint $table) {
            $table->string('kbli_id', 50);
            $table->integer('tahun');
            $table->string('level', 50);
            $table->string('judul', 255);
            $table->text('deskripsi');

            // Composite primary key to allow different KBLI versions
            $table->primary(['kbli_id', 'tahun']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referensi_kbli');
    }
};
