<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('room_id');
            $table->string('player_id')->nullable();
            $table->string('command_id')->nullable();
            $table->string('event_type');
            $table->json('event_json');
            $table->unsignedInteger('state_version');
            $table->timestamp('created_at');

            $table->foreign('room_id')->references('id')->on('rooms')->cascadeOnDelete();
            $table->foreign('player_id')->references('id')->on('players')->nullOnDelete();
            $table->unique(['room_id', 'command_id']);
            $table->index(['room_id', 'state_version']);
            $table->index('event_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_events');
    }
};
