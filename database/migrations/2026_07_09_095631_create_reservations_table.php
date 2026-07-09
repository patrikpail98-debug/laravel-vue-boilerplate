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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('playground_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');

            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone');

            $table->dateTime('start_time');
            $table->dateTime('end_time');

            $table->string('variable_symbol')->unique();
            $table->decimal('total_price', 8, 2);

            $table->string('status')->default('unverified'); // unverified, pending_approval, approved, rejected, cancelled
            $table->string('verification_token')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->text('admin_note')->nullable();

            $table->timestamps();

            $table->index(['playground_id', 'start_time', 'end_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
