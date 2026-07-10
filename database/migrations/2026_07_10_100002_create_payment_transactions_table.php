<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->onDelete('cascade');

            $table->string('provider')->default('nexi_xpay');
            // Our own id sent to the gateway as orderId - unique so we can look
            // up the matching transaction from a webhook/redirect callback.
            $table->string('order_id')->unique();
            $table->string('status')->default('pending'); // pending, authorized, declined, cancelled, error

            $table->unsignedInteger('amount_cents');
            $table->string('currency', 3)->default('EUR');

            $table->text('hosted_page_url')->nullable();
            $table->string('security_token')->nullable();
            $table->string('last_operation_type')->nullable();
            // Raw JSON of the last GET /orders/{orderId} response - kept for audit/dispute resolution.
            $table->json('raw_response')->nullable();
            $table->dateTime('verified_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
