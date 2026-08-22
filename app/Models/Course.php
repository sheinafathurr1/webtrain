<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Collection;

#[Fillable(['track_id', 'title', 'slug', 'description', 'order', 'is_published', 'lock_lessons_sequentially'])]
class Course extends Model
{
    use HasFactory, HasSlug;

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'lock_lessons_sequentially' => 'boolean',
        ];
    }

    public function track(): BelongsTo
    {
        return $this->belongsTo(Track::class);
    }

    public function modules(): HasMany
    {
        return $this->hasMany(Module::class)->orderBy('order');
    }

    /**
     * Used by Laravel's implicit route model binding scoping for
     * courses/{course:slug}/lessons/{lesson:slug} — also guarantees a
     * lesson slug from another course 404s instead of resolving.
     */
    public function lessons(): HasManyThrough
    {
        return $this->hasManyThrough(Lesson::class, Module::class);
    }

    /**
     * Published lessons across all modules, in course sequence
     * (module order, then lesson order within each module).
     */
    public function publishedLessons(): Collection
    {
        return $this->modules()
            ->with(['lessons' => fn ($query) => $query->where('is_published', true)])
            ->get()
            ->flatMap->lessons;
    }

    public function progressPercentFor(?User $user): int
    {
        $lessons = $this->publishedLessons();

        if (! $user || $lessons->isEmpty()) {
            return 0;
        }

        $completed = UserProgress::where('user_id', $user->id)
            ->whereIn('lesson_id', $lessons->pluck('id'))
            ->count();

        return (int) round($completed / $lessons->count() * 100);
    }

    /**
     * The lesson a student should continue with: the first not yet
     * completed, or the last lesson if everything is done.
     */
    public function nextLessonFor(?User $user): ?Lesson
    {
        $lessons = $this->publishedLessons();

        if ($lessons->isEmpty()) {
            return null;
        }

        if (! $user) {
            return $lessons->first();
        }

        $completedIds = UserProgress::where('user_id', $user->id)
            ->whereIn('lesson_id', $lessons->pluck('id'))
            ->pluck('lesson_id');

        return $lessons->first(fn (Lesson $lesson) => ! $completedIds->contains($lesson->id)) ?? $lessons->last();
    }

    /**
     * Whether a lesson is locked for a user under this course's
     * lock_lessons_sequentially setting: locked until the previous
     * lesson in course sequence has been completed.
     */
    public function isLessonLockedFor(Lesson $lesson, ?User $user): bool
    {
        if (! $this->lock_lessons_sequentially) {
            return false;
        }

        $lessons = $this->publishedLessons()->values();
        $index = $lessons->search(fn (Lesson $l) => $l->id === $lesson->id);

        if ($index === false || $index === 0) {
            return false;
        }

        $previousLesson = $lessons->get($index - 1);

        return ! $previousLesson->isCompletedBy($user);
    }
}
