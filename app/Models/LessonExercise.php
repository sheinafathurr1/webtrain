<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['lesson_id', 'language', 'instructions', 'starter_code', 'expected_output', 'solution_code'])]
class LessonExercise extends Model
{
    use HasFactory;

    public const LANGUAGES = ['html', 'css', 'js', 'mixed'];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
