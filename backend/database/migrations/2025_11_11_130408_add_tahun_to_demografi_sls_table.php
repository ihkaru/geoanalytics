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
        Schema::table('demografi_sls', function (Blueprint $table) {
            $table->integer('tahun')->nullable()->after('nama_desa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('demografi_sls', function (Blueprint $table) {
            $table->dropColumn('tahun');
        });
    }
};
