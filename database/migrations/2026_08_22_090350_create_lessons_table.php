<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            // text: markdown in `content`. video: YouTube URL in `video_url`.
            // exercise: see lesson_exercises table. quiz: wired up in a later phase.
            $table->string('type')->default('text');
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->longText('content')->nullable();
            $table->string('video_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
