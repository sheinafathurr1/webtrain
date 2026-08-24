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
        // Leaderboard sorts the entire Student population by this on
        // every page load.
        Schema::table('users', function (Blueprint $table) {
            $table->index('total_points');
        });

        // Public listings filter by is_published and sort by order on
        // every visit — a composite index serves both in one lookup.
        Schema::table('tracks', function (Blueprint $table) {
            $table->index(['is_published', 'order']);
        });

        // Courses are always queried scoped to a track and/or filtered
        // by published state.
        Schema::table('courses', function (Blueprint $table) {
            $table->index(['track_id', 'is_published']);
        });

        // Modules are always queried scoped to a course, ordered.
        Schema::table('modules', function (Blueprint $table) {
            $table->index(['course_id', 'order']);
        });

        // Lessons are the hottest table here: every course/lesson page,
        // admin list, and the analytics joins filter by module_id and
        // is_published together, then sort by order.
        Schema::table('lessons', function (Blueprint $table) {
            $table->index(['module_id', 'is_published', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['total_points']);
        });

        Schema::table('tracks', function (Blueprint $table) {
            $table->dropIndex(['is_published', 'order']);
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex(['track_id', 'is_published']);
        });

        Schema::table('modules', function (Blueprint $table) {
            $table->dropIndex(['course_id', 'order']);
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->dropIndex(['module_id', 'is_published', 'order']);
        });
    }
};
