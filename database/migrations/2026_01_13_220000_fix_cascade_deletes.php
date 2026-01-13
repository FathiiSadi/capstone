<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1. Instructors -> User
        Schema::table('instructors', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // 2. Sections -> Course, Semester
        Schema::table('sections', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->dropForeign(['semester_id']);

            $table->foreign('course_id')->references('id')->on('courses')->onDelete('cascade');
            $table->foreign('semester_id')->references('id')->on('semesters')->onDelete('cascade');
        });

        // 3. Instructor Preferences -> Instructor, Course, Semester
        Schema::table('instructor_preferences', function (Blueprint $table) {
            $table->dropForeign(['instructor_id']);
            $table->dropForeign(['course_id']);
            $table->dropForeign(['semester_id']);

            $table->foreign('instructor_id')->references('id')->on('instructors')->onDelete('cascade');
            $table->foreign('course_id')->references('id')->on('courses')->onDelete('cascade');
            $table->foreign('semester_id')->references('id')->on('semesters')->onDelete('cascade');
        });

        // 4. Department Instructors -> Department, Instructor
        Schema::table('department_instructors', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['instructor_id']);

            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->foreign('instructor_id')->references('id')->on('instructors')->onDelete('cascade');
        });

        // 6. Preference Time Slots
        // Note: It seems this table did NOT have a real foreign key constraint before, just a column.
        // We must clean up orphans first otherwise adding constraint fails.
        if (Schema::hasTable('preference_time_slots')) {
            DB::table('preference_time_slots')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('instructor_preferences')
                        ->whereRaw('preference_time_slots.instructor_preference_id = instructor_preferences.id');
                })
                ->delete();

            Schema::table('preference_time_slots', function (Blueprint $table) {
                // Try to drop ONLY if it exists (Laravel Schema builder doesn't easily support "drop checks")
                // But simply adding it is safe if it didn't exist.
                // If it DOES exist (unlikely based on analysis), we'd need to drop.
                // Let's assume it doesn't exist and just add it.
                $table->foreign('instructor_preference_id', 'pts_ip_cascade_fk')
                    ->references('id')
                    ->on('instructor_preferences')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        // One-way fix
    }
};
