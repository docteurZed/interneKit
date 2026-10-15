<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('gardes')->whereNull('service_id')->exists()) {
            throw new RuntimeException(
                'Impossible de rendre le service obligatoire: des gardes existantes n\'ont pas de service. Attribuez-leur un service puis relancez les migrations.'
            );
        }

        Schema::table('gardes', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
            $table->foreignId('service_id')->nullable(false)->change();
            $table->foreign('service_id')->references('id')->on('services')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('gardes', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
            $table->foreignId('service_id')->nullable()->change();
            $table->foreign('service_id')->references('id')->on('services')->nullOnDelete();
        });
    }
};