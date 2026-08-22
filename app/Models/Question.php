<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['quiz_id', 'type', 'question_text', 'explanation', 'correct_answer', 'order'])]
class Question extends Model
{
    use HasFactory;

    public const TYPE_MULTIPLE_CHOICE = 'multiple_choice';

    public const TYPE_SHORT_ANSWER = 'short_answer';

    public const TYPES = [self::TYPE_MULTIPLE_CHOICE, self::TYPE_SHORT_ANSWER];

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('order');
    }

    public function isAnswerCorrect(?int $selectedOptionId, ?string $answerText): bool
    {
        if ($this->type === self::TYPE_MULTIPLE_CHOICE) {
            if (! $selectedOptionId) {
                return false;
            }

            return (bool) $this->options->firstWhere('id', $selectedOptionId)?->is_correct;
        }

        if ($this->type === self::TYPE_SHORT_ANSWER) {
            if (blank($answerText) || blank($this->correct_answer)) {
                return false;
            }

            return trim(mb_strtolower($answerText)) === trim(mb_strtolower($this->correct_answer));
        }

        return false;
    }
}
