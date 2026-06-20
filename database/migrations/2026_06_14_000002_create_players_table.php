<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('room_id');
            $table->string('nickname');
            $table->string('token_hash');
            $table->boolean('is_host')->default(false);
            $table->unsignedTinyInteger('turn_order_index')->nullable();
            $table->boolean('connected')->default(false);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->foreign('room_id')->references('id')->on('rooms')->cascadeOnDelete();
            $table->unique(['room_id', 'nickname']);
            $table->index(['room_id', 'is_host']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
