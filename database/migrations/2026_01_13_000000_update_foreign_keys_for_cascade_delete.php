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
        // 1. Sections: Cascade on Course and Semester
        Schema::table('sections', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();

            $table->dropForeign(['semester_id']);
            $table->foreign('semester_id')->references('id')->on('semesters')->cascadeOnDelete();
        });

        // 2. Instructors: Cascade on User
        Schema::table('instructors', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        // 3. Semester Courses: Cascade on both
        Schema::table('semester_courses', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
            $table->foreign('semester_id')->references('id')->on('semesters')->cascadeOnDelete();

            $table->dropForeign(['course_id']);
            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
        });

        // 4. User Courses: Cascade on both
        Schema::table('user_courses', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->dropForeign(['course_id']);
            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
        });

        // 5. Department Instructors: Cascade on both
        Schema::table('department_instructors', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->foreign('department_id')->references('id')->on('departments')->cascadeOnDelete();

            $table->dropForeign(['instructor_id']);
            $table->foreign('instructor_id')->references('id')->on('instructors')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to default (restrict/no action usually, but Laravel default is constrained which is usually RESTRICT)

        Schema::table('sections', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->foreign('course_id')->references('id')->on('courses');

            $table->dropForeign(['semester_id']);
            $table->foreign('semester_id')->references('id')->on('semesters');
        });

        Schema::table('instructors', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users');
        });

        Schema::table('semester_courses', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
            $table->foreign('semester_id')->references('id')->on('semesters');

            $table->dropForeign(['course_id']);
            $table->foreign('course_id')->references('id')->on('courses');
        });

        Schema::table('user_courses', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users');

            $table->dropForeign(['course_id']);
            $table->foreign('course_id')->references('id')->on('courses');
        });

        Schema::table('department_instructors', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->foreign('department_id')->references('id')->on('departments');

            $table->dropForeign(['instructor_id']);
            $table->foreign('instructor_id')->references('id')->on('instructors');
        });
    }
};
