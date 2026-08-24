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

    /**
     * Per-instance memoization for publishedLessons(): the same Course
     * instance is often asked for this multiple times in one request
     * (progress %, next lesson, per-lesson lock checks) — without this,
     * each call re-queries modules+lessons from scratch.
     */
    private ?Collection $publishedLessonsCache = null;

    /** @var array<int, Collection> */
    private array $completedLessonIdsCache = [];

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
        if ($this->publishedLessonsCache !== null) {
            return $this->publishedLessonsCache;
        }

        // Reuse an already-eager-loaded modules.lessons relation when
        // present (course/lesson pages load it up front) instead of
        // re-querying; re-filter in memory so this stays correct
        // regardless of what constraint the original load used.
        $alreadyLoaded = $this->relationLoaded('modules')
            && $this->modules->every(fn (Module $module) => $module->relationLoaded('lessons'));

        $modules = $alreadyLoaded
            ? $this->modules
            : $this->modules()->with('lessons')->get();

        return $this->publishedLessonsCache = $modules->flatMap->lessons
            ->where('is_published', true)
            ->values();
    }

    public function progressPercentFor(?User $user): int
    {
        $lessons = $this->publishedLessons();

        if (! $user || $lessons->isEmpty()) {
            return 0;
        }

        $completed = $this->completedLessonIdsFor($user)->count();

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

        $completedIds = $this->completedLessonIdsFor($user);

        return $lessons->first(fn (Lesson $lesson) => ! $completedIds->contains($lesson->id)) ?? $lessons->last();
    }

    /**
     * Memoized per user: progressPercentFor() and nextLessonFor() both
     * need "which of this course's lessons has the user completed",
     * so share one query instead of each running its own.
     */
    private function completedLessonIdsFor(User $user): Collection
    {
        return $this->completedLessonIdsCache[$user->id] ??= UserProgress::where('user_id', $user->id)
            ->whereIn('lesson_id', $this->publishedLessons()->pluck('id'))
            ->pluck('lesson_id');
    }

    /**
     * Whether a lesson is locked for a user under this course's
     * lock_lessons_sequentially setting: locked until the previous
     * lesson in course sequence has been completed.
     *
     * Pass $completedLessonIds (already fetched once by the caller,
     * e.g. in mount()) to check membership in memory instead of
     * issuing a UserProgress query per lesson — this method is
     * typically called once per lesson in a listing loop.
     */
    public function isLessonLockedFor(Lesson $lesson, ?User $user, ?array $completedLessonIds = null): bool
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

        if ($completedLessonIds !== null) {
            return ! in_array($previousLesson->id, $completedLessonIds, true);
        }

        return ! $previousLesson->isCompletedBy($user);
    }
}
