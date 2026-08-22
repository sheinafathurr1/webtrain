<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['slug', 'name', 'description', 'icon', 'criteria_type', 'criteria_value'])]
class Badge extends Model
{
    use HasFactory;

    public const CRITERIA_LESSONS_COMPLETED = 'lessons_completed';

    public const CRITERIA_COURSE_COMPLETED = 'course_completed';

    public const CRITERIA_QUIZ_PERFECT_SCORE = 'quiz_perfect_score';

    public const CRITERIA_STREAK_DAYS = 'streak_days';

    public function userBadges(): HasMany
    {
        return $this->hasMany(UserBadge::class);
    }
}
