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
        Schema::table('usahas', function (Blueprint $table) {
            $table->integer('geocode_status')->nullable()->default(0)->after('longitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usahas', function (Blueprint $table) {
            $table->dropColumn('geocode_status');
        });
    }
};
