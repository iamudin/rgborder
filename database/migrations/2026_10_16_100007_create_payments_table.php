<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->string('payment_method')->default('qris'); // qris, bank_transfer, cash_on_site
            $table->unsignedInteger('amount');
            $table->string('status')->default('unpaid')->index(); // unpaid, pending_verification, paid, failed, expired, refunded
            $table->string('proof_file')->nullable();
            $table->string('reference_number')->nullable()->index();
            $table->text('notes')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['booking_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
