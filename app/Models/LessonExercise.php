<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['lesson_id', 'language', 'instructions', 'starter_code', 'expected_output', 'solution_code', 'checks'])]
class LessonExercise extends Model
{
    use HasFactory;

    public const LANGUAGES = ['html', 'css', 'js', 'mixed'];

    public const CHECK_TYPES = ['text', 'style', 'alert'];

    protected $casts = [
        'checks' => 'array',
    ];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function hasChecks(): bool
    {
        return ! empty($this->checks);
    }
}
