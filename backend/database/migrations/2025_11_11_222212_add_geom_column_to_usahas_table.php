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
        Schema::table('usahas', function (Blueprint $table) {
            // Add PostGIS geometry column using raw statement as it's not natively supported by Blueprint
            DB::statement('ALTER TABLE usahas ADD COLUMN geom geometry(Point, 4326);');
            // Add spatial index
            $table->spatialIndex('geom');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usahas', function (Blueprint $table) {
            $table->dropSpatialIndex(['geom']);
            $table->dropColumn('geom');
        });
    }
};
