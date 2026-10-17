<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('player_profile_id')->constrained('player_profiles')->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->string('player_name');
            $table->string('player_phone');
            $table->string('check_in_status')->default('pending')->index(); // pending, checked_in
            $table->string('check_in_token')->unique();
            $table->dateTime('checked_in_at')->nullable();
            $table->timestamps();

            $table->unique(['booking_id', 'player_profile_id']);
            $table->index(['team_id', 'check_in_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_players');
    }
};
