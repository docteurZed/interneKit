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
        Schema::create('gardes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_garde_id')->constrained('types_garde')->restrictOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->date('date_garde');
            $table->dateTime('start');
            $table->dateTime('end');
            $table->enum('statut', ['prevue', 'effectuee', 'annulee'])->default('prevue');
            $table->timestamps();

            $table->index(['service_id', 'date_garde']);
            $table->index(['type_garde_id', 'date_garde']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gardes');
    }
};
