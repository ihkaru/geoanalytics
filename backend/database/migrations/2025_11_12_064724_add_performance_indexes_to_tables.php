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
        Schema::table('muatan_subsls', function (Blueprint $table) {
            $table->index('kdkec');
            $table->index('kddesa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('muatan_subsls', function (Blueprint $table) {
            $table->dropIndex(['kdkec']);
            $table->dropIndex(['kddesa']);
        });
    }
};
