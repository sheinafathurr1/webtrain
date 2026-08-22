<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['module_id', 'title', 'slug', 'type', 'order', 'is_published', 'content', 'video_url'])]
class Lesson extends Model
{
    use HasFactory, HasSlug;

    public const TYPE_TEXT = 'text';

    public const TYPE_VIDEO = 'video';

    public const TYPE_EXERCISE = 'exercise';

    public const TYPE_QUIZ = 'quiz';

    public const TYPES = [self::TYPE_TEXT, self::TYPE_VIDEO, self::TYPE_EXERCISE, self::TYPE_QUIZ];

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

    public function quiz(): HasOne
    {
        return $this->hasOne(Quiz::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(UserProgress::class);
    }

    public function isCompletedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->progress()->where('user_id', $user->id)->exists();
    }

    public function youtubeEmbedUrl(): ?string
    {
        if (! $this->video_url) {
            return null;
        }

        $pattern = '/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/';

        if (! preg_match($pattern, $this->video_url, $matches)) {
            return null;
        }

        return 'https://www.youtube.com/embed/'.$matches[1];
    }
}
