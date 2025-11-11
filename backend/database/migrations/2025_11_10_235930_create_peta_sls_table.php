<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('peta_sls', function (Blueprint $table) {
            $table->string('idsubsls')->primary();
        });

        // Add the geometry column with PostGIS
        DB::statement('ALTER TABLE peta_sls ADD COLUMN geom geometry(MultiPolygon, 4326)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peta_sls');
    }
};
