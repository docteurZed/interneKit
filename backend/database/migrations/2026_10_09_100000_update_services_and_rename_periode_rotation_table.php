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
        if (Schema::hasTable('services')) {
            Schema::table('services', function (Blueprint $table) {
                $table->unique('name');
            });
        }

        if (Schema::hasTable('periode_rotations') && ! Schema::hasTable('periodes_rotation')) {
            Schema::rename('periode_rotations', 'periodes_rotation');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('services')) {
            Schema::table('services', function (Blueprint $table) {
                $table->dropUnique(['name']);
            });
        }

        if (Schema::hasTable('periodes_rotation') && ! Schema::hasTable('periode_rotations')) {
            Schema::rename('periodes_rotation', 'periode_rotations');
        }
    }
};
