<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['module_id', 'title', 'slug', 'type', 'order', 'is_published', 'content', 'video_url'])]
class Lesson extends Model
{
    use HasFactory, HasSlug;

    public const TYPE_TEXT = 'text';

    public const TYPE_VIDEO = 'video';

    public const TYPE_EXERCISE = 'exercise';

    public const TYPES = [self::TYPE_TEXT, self::TYPE_VIDEO, self::TYPE_EXERCISE];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function exercise(): HasOne
    {
        return $this->hasOne(LessonExercise::class);
    }
}
