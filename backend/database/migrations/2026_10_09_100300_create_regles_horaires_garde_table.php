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
        Schema::create('regles_horaires_garde', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->restrictOnDelete();
            $table->foreignId('type_garde_id')->constrained('types_garde')->restrictOnDelete();
            $table->enum('categorie_day', ['semaine', 'weekend', 'ferie']);
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedTinyInteger('decalage_end')->default(0);
            $table->timestamps();

            $table->unique(['service_id', 'type_garde_id', 'categorie_day'], 'unique_regle_horaire_garde');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regles_horaires_garde');
    }
};
