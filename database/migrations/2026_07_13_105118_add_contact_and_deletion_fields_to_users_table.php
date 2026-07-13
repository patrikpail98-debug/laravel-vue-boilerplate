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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('street')->nullable()->after('phone');
            $table->string('city')->nullable()->after('street');
            $table->string('postcode')->nullable()->after('city');
            $table->string('ico')->nullable()->after('postcode');
            $table->boolean('is_deleted')->default(false)->after('is_blocked');
            $table->timestamp('deleted_at')->nullable()->after('is_deleted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'street', 'city', 'postcode', 'ico', 'is_deleted', 'deleted_at']);
        });
    }
};
