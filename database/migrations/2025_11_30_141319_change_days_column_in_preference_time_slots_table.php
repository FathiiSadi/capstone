<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE preference_time_slots MODIFY days VARCHAR(1000) NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We cannot easily revert to ENUM if data contains non-enum values.
        // Leaving as string or text is safer.
    }
};
