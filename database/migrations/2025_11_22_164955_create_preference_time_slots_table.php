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
        Schema::create('preference_time_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_preference_id');
            $table->enum('days', ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Saturday']);
            $table->common();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preference_time_slots');
    }
};
