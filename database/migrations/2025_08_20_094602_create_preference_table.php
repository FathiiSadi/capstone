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
        Schema::create('preference', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->constrained('instructor');
            $table->foreignId('course_id')->constrained('course');
            $table->enum('day', ['Monday & Thursday', 'Tuesday & Saturday', 'Sunday & Wednesday'])->default('Monday & Thursday');
            $table->enum('time_slot', ['morning', 'afternoon', 'evening'])->default('morning');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preference');
    }
};
