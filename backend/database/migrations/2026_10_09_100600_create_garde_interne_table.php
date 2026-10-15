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
        Schema::create('garde_interne', function (Blueprint $table) {
            $table->id();
            $table->foreignId('garde_id')->constrained('gardes')->restrictOnDelete();
            $table->foreignId('interne_id')->constrained('internes')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['garde_id', 'interne_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('garde_interne');
    }
};
