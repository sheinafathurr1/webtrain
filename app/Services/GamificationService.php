<?php

namespace App\Services;

use App\Models\Badge;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\PointTransaction;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\UserBadge;
use Carbon\Carbon;

class GamificationService
{
    public const POINTS_PER_LESSON = 10;

    /**
     * Points/streak/badges are a one-way ratchet: un-completing a lesson
     * later does not claw back points or revoke badges already earned.
     */
    public function recordLessonCompleted(User $user, Lesson $lesson): void
    {
        $alreadyAwarded = PointTransaction::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->exists();

        if (! $alreadyAwarded) {
            $this->awardPoints($user, self::POINTS_PER_LESSON, "Menyelesaikan lesson: {$lesson->title}", $lesson);
        }

        $this->bumpStreak($user);
        $this->checkBadges($user);
    }

    public function recordQuizSubmitted(User $user): void
    {
        $this->checkBadges($user);
    }

    private function awardPoints(User $user, int $points, string $reason, ?Lesson $lesson = null): void
    {
        PointTransaction::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson?->id,
            'points' => $points,
            'reason' => $reason,
        ]);

        $user->increment('total_points', $points);
    }

    private function bumpStreak(User $user): void
    {
        $today = Carbon::today();
        $last = $user->last_activity_date ? Carbon::parse($user->last_activity_date) : null;

        if ($last?->isSameDay($today)) {
            return;
        }

        $user->current_streak = $last?->isSameDay($today->copy()->subDay())
            ? $user->current_streak + 1
            : 1;

        $user->longest_streak = max($user->longest_streak, $user->current_streak);
        $user->last_activity_date = $today;
        $user->save();
    }

    private function checkBadges(User $user): void
    {
        $earnedBadgeIds = $user->userBadges()->pluck('badge_id');

        foreach (Badge::whereNotIn('id', $earnedBadgeIds)->get() as $badge) {
            if ($this->meetsCriteria($user, $badge)) {
                UserBadge::create([
                    'user_id' => $user->id,
                    'badge_id' => $badge->id,
                    'earned_at' => now(),
                ]);
            }
        }
    }

    private function meetsCriteria(User $user, Badge $badge): bool
    {
        return match ($badge->criteria_type) {
            Badge::CRITERIA_LESSONS_COMPLETED => $user->progress()->count() >= $badge->criteria_value,
            Badge::CRITERIA_STREAK_DAYS => $user->longest_streak >= $badge->criteria_value,
            Badge::CRITERIA_QUIZ_PERFECT_SCORE => QuizAttempt::where('user_id', $user->id)->where('score', 100)->exists(),
            Badge::CRITERIA_COURSE_COMPLETED => $this->hasCompletedAnyCourse($user),
            default => false,
        };
    }

    private function hasCompletedAnyCourse(User $user): bool
    {
        $completedLessonIds = $user->progress()->pluck('lesson_id');

        $courseIds = Lesson::whereIn('id', $completedLessonIds)
            ->with('module')
            ->get()
            ->pluck('module.course_id')
            ->unique();

        foreach (Course::whereIn('id', $courseIds)->get() as $course) {
            if ($course->progressPercentFor($user) === 100) {
                return true;
            }
        }

        return false;
    }
}
