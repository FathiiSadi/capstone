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
        Schema::create('semesters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['Fall', 'Spring', 'Summer'])->default('fall');
            $table->dateTime('preferences_open_at');
            $table->dateTime('preferences_closed_at');
            $table->enum('status', ['Draft', 'Open', 'Running', 'closed'])->default('closed');
            $table->common();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('semesters');
    }
};
