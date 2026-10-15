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
        Schema::table('affectations', function (Blueprint $table) {
            $table->foreignId('interne_id')->constrained('internes')->restrictOnDelete();
            $table->foreignId('service_id')->constrained('services')->restrictOnDelete();
            $table->foreignId('periode_rotation_id')->constrained('periodes_rotation')->restrictOnDelete();

            $table->unique(['interne_id', 'periode_rotation_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('affectations', function (Blueprint $table) {
            $table->dropUnique(['interne_id', 'periode_rotation_id']);
            $table->dropConstrainedForeignId('periode_rotation_id');
            $table->dropConstrainedForeignId('service_id');
            $table->dropConstrainedForeignId('interne_id');
        });
    }
};
