<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waitlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->constrained('game_sessions')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('player_name');
            $table->string('player_phone');
            $table->unsignedInteger('player_count')->default(1);
            $table->unsignedInteger('queue_number');
            $table->string('status')->default('waiting')->index(); // waiting, offered, accepted, declined, expired, cancelled
            $table->dateTime('offered_at')->nullable();
            $table->dateTime('offer_expires_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['game_session_id', 'status', 'queue_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waitlists');
    }
};
