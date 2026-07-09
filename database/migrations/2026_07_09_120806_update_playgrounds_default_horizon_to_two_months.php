<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('playgrounds', function (Blueprint $table) {
            $table->unsignedInteger('max_horizon_days')->default(60)->change();
        });

        // Bump any playground still on the old default (14 days) up to the new
        // 2-month default. Playgrounds an admin has customized to a different
        // value are left untouched.
        DB::table('playgrounds')->where('max_horizon_days', 14)->update(['max_horizon_days' => 60]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('playgrounds', function (Blueprint $table) {
            $table->unsignedInteger('max_horizon_days')->default(14)->change();
        });
    }
};
