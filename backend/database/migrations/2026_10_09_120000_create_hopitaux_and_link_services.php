<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hopitaux', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->foreignId('hopital_id')->nullable()->constrained('hopitaux')->restrictOnDelete();
            $table->unique(['hopital_id', 'name']);
        });
    }

    public function down(): void
    {
        $duplicateNames = DB::table('services')
            ->select('name')
            ->groupBy('name')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplicateNames) {
            throw new RuntimeException('Impossible de restaurer l’unicité globale: plusieurs hôpitaux utilisent le même nom de service.');
        }

        Schema::table('services', function (Blueprint $table) {
            $table->dropUnique(['hopital_id', 'name']);
            $table->dropConstrainedForeignId('hopital_id');
            $table->unique('name');
        });

        Schema::dropIfExists('hopitaux');
    }
};
