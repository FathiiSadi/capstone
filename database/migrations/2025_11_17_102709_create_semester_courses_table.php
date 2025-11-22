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
        Schema::create('semester_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semester_id')->constrained();
            $table->foreignId('course_id')->constrained();
            $table->integer('sections_required')->default(4);
            $table->integer('sections_per_instructor')->default(2);
            $table->common();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('semester_courses');
    }
};
