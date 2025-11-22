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
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained();
            $table->foreignId('semester_id')->constrained();
            $table->enum('days', ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Saturday']);
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('status', ['Active', 'Inactive', 'Closed'])->default('Active');
            $table->common();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
