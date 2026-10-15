<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('page_views');
    }

    public function down(): void
    {
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_id')->unique();
            $table->uuid('visitor_id')->index();
            $table->uuid('session_id')->index();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('path');
            $table->string('title', 120)->nullable();
            $table->string('referrer', 190)->nullable();
            $table->string('device', 10);
            $table->string('browser', 20);
            $table->dateTime('occurred_at')->index();
            $table->timestamp('created_at')->nullable();
            $table->index(['path', 'occurred_at']);
        });
    }
};