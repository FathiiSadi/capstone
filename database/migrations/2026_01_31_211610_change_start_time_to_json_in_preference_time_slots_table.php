<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        \Illuminate\Support\Facades\DB::table('preference_time_slots')->truncate();
        Schema::table('preference_time_slots', function (Blueprint $table) {
            $table->json('start_time')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('preference_time_slots', function (Blueprint $table) {
            $table->time('start_time')->nullable()->change();
        });
    }
};
