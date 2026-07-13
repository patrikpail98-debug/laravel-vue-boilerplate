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
        Schema::table('reservations', function (Blueprint $table) {
            $table->string('ico')->nullable()->after('customer_phone');
            // Full address as a single combined line (street, postcode, city) -
            // unlike the user's own profile, split fields aren't needed here.
            $table->string('address')->nullable()->after('ico');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['ico', 'address']);
        });
    }
};
