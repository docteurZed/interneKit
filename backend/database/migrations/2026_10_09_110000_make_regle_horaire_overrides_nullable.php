<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('regles_horaires_garde', function (Blueprint $table) {
            $table->time('start_time')->nullable()->change();
            $table->time('end_time')->nullable()->change();
            $table->unsignedTinyInteger('decalage_end')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        $rules = DB::table('regles_horaires_garde')->get(['id', 'type_garde_id', 'start_time', 'end_time', 'decalage_end']);

        foreach ($rules as $rule) {
            $defaults = DB::table('types_garde')
                ->where('id', $rule->type_garde_id)
                ->first(['start_time', 'end_time', 'decalage_end']);

            DB::table('regles_horaires_garde')->where('id', $rule->id)->update([
                'start_time' => $rule->start_time ?? $defaults->start_time,
                'end_time' => $rule->end_time ?? $defaults->end_time,
                'decalage_end' => $rule->decalage_end ?? $defaults->decalage_end,
            ]);
        }

        Schema::table('regles_horaires_garde', function (Blueprint $table) {
            $table->time('start_time')->nullable(false)->change();
            $table->time('end_time')->nullable(false)->change();
            $table->unsignedTinyInteger('decalage_end')->default(0)->nullable(false)->change();
        });
    }
};