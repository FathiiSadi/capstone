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
        Schema::table('courses', function (Blueprint $table) {
            $table->boolean('office_hours')->default(false)->after('sections');
        });

        Schema::table('sections', function (Blueprint $table) {
            $table->time('start_time')->nullable()->change();
            $table->time('end_time')->nullable()->change();
            // days is an enum or json depending on history, simpler to drop and recreate or raw statement if complex
            // But earlier I saw it was enum, then changed to JSON.
            // Let's assume it's JSON/text now based on "2025_12_27_212011_change_days_column_to_json_in_sections_table.php"
            $table->json('days')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('office_hours');
        });

        Schema::table('sections', function (Blueprint $table) {
            // Reverting nullable is risky if nulls exist, but technically:
            $table->time('start_time')->nullable(false)->change();
            $table->time('end_time')->nullable(false)->change();
            $table->json('days')->nullable(false)->change();
        });
    }
};
