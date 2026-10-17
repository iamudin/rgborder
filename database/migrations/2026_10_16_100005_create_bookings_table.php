<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('game_session_id')->constrained('game_sessions')->cascadeOnDelete();
            $table->string('booking_type')->default('individual'); // individual, group, full_team
            $table->foreignId('target_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->unsignedInteger('total_players')->default(1);
            $table->unsignedInteger('price_per_player')->default(25000);
            $table->unsignedInteger('total_amount')->default(25000);
            $table->string('status')->default('pending_payment')->index(); // pending_payment, confirmed, cancelled, expired
            $table->dateTime('expires_at')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['game_session_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
