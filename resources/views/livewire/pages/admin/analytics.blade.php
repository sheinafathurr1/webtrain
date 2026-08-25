<?php

use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

use function Livewire\Volt\{layout, state};

layout('layouts.app');

// One shared cache entry for the whole dashboard: these are aggregate
// queries over the entire dataset, not per-viewer, so every admin who
// opens this page within the TTL reuses the same snapshot instead of
// re-running six queries (several of them multi-table joins) each time.
//
// The cached payload must be plain arrays, not Eloquent models/
// Collections or stdClass rows: this app's cache config defaults
// serializable_classes to false, so unserialize() runs with
// allowed_classes => false and silently replaces any cached object
// with an unusable stub on the next read — only arrays/scalars
// survive a round trip. courseStats/quizStats/recentActivity are
// cast to arrays before caching and reconstructed into objects
// (recentActivity's created_at back into a real Carbon instance)
// after every read, cache hit or miss.
state([
    'analytics' => function () {
        $cached = Cache::remember('admin:analytics', 300, fn () => [
            'totalStudents' => User::role('Student')->count(),

            'activeStudents' => UserProgress::where('created_at', '>=', now()->subDays(7))
                ->distinct('user_id')
                ->count('user_id'),

            'totalCompletions' => UserProgress::count(),

            'averageQuizScore' => (int) round(QuizAttempt::avg('score') ?? 0),

            'courseStats' => DB::table('courses')
                ->join('tracks', 'tracks.id', '=', 'courses.track_id')
                ->leftJoin('modules', 'modules.course_id', '=', 'courses.id')
                ->leftJoin('lessons', function ($join) {
                    $join->on('lessons.module_id', '=', 'modules.id')->where('lessons.is_published', true);
                })
                ->leftJoin('user_progress', 'user_progress.lesson_id', '=', 'lessons.id')
                ->where('courses.is_published', true)
                ->select(
                    'courses.id',
                    'courses.title as course_title',
                    'tracks.title as track_title',
                    DB::raw('COUNT(DISTINCT lessons.id) as lessons_count'),
                    DB::raw('COUNT(DISTINCT user_progress.user_id) as students_count'),
                    DB::raw('COUNT(user_progress.id) as completions_count')
                )
                ->groupBy('courses.id', 'courses.title', 'tracks.title')
                ->orderByDesc('students_count')
                ->get()
                ->map(function ($row) {
                    $row->completion_rate = ($row->students_count > 0 && $row->lessons_count > 0)
                        ? (int) round($row->completions_count / ($row->students_count * $row->lessons_count) * 100)
                        : 0;

                    return (array) $row;
                })
                ->all(),

            'quizStats' => DB::table('quizzes')
                ->join('lessons', 'lessons.id', '=', 'quizzes.lesson_id')
                ->join('modules', 'modules.id', '=', 'lessons.module_id')
                ->join('courses', 'courses.id', '=', 'modules.course_id')
                ->leftJoin('quiz_attempts', 'quiz_attempts.quiz_id', '=', 'quizzes.id')
                ->select(
                    'quizzes.id',
                    'quizzes.title as quiz_title',
                    'courses.title as course_title',
                    DB::raw('COUNT(quiz_attempts.id) as attempts_count'),
                    DB::raw('AVG(quiz_attempts.score) as average_score')
                )
                ->groupBy('quizzes.id', 'quizzes.title', 'courses.title')
                ->orderByDesc('attempts_count')
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all(),

            'recentActivity' => UserProgress::with(['user:id,name', 'lesson:id,title,module_id', 'lesson.module:id,course_id', 'lesson.module.course:id,title'])
                ->latest('user_progress.created_at')
                ->limit(10)
                ->get()
                ->toArray(),
        ]);

        return [
            'totalStudents' => $cached['totalStudents'],
            'activeStudents' => $cached['activeStudents'],
            'totalCompletions' => $cached['totalCompletions'],
            'averageQuizScore' => $cached['averageQuizScore'],
            'courseStats' => collect($cached['courseStats'])->map(fn ($row) => (object) $row),
            'quizStats' => collect($cached['quizStats'])->map(fn ($row) => (object) $row),
            'recentActivity' => collect($cached['recentActivity'])->map(function ($row) {
                $progress = json_decode(json_encode($row));
                $progress->created_at = \Illuminate\Support\Carbon::parse($row['created_at']);

                return $progress;
            }),
        ];
    },
]);

?>

<div>
    @php
        ['totalStudents' => $totalStudents, 'activeStudents' => $activeStudents, 'totalCompletions' => $totalCompletions, 'averageQuizScore' => $averageQuizScore, 'courseStats' => $courseStats, 'quizStats' => $quizStats, 'recentActivity' => $recentActivity] = $analytics;
    @endphp

    <x-slot:header>
        <h2 class="font-display font-extrabold text-2xl text-ink-primary">{{ __('Analytics') }}</h2>
    </x-slot:header>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-surface border border-border rounded-2xl p-6">
                    <p class="text-sm text-ink-secondary font-medium">{{ __('Total Siswa') }}</p>
                    <p class="mt-1 font-display text-3xl font-extrabold text-brand tabular-nums">{{ $totalStudents }}</p>
                </div>
                <div class="bg-surface border border-border rounded-2xl p-6">
                    <p class="text-sm text-ink-secondary font-medium">{{ __('Aktif 7 Hari Terakhir') }}</p>
                    <p class="mt-1 font-display text-3xl font-extrabold text-accent tabular-nums">{{ $activeStudents }}</p>
                </div>
                <div class="bg-surface border border-border rounded-2xl p-6">
                    <p class="text-sm text-ink-secondary font-medium">{{ __('Total Lesson Selesai') }}</p>
                    <p class="mt-1 font-display text-3xl font-extrabold text-gold tabular-nums">{{ $totalCompletions }}</p>
                </div>
                <div class="bg-surface border border-border rounded-2xl p-6">
                    <p class="text-sm text-ink-secondary font-medium">{{ __('Rata-rata Skor Quiz') }}</p>
                    <p class="mt-1 font-display text-3xl font-extrabold text-ink-primary tabular-nums">{{ $averageQuizScore }}%</p>
                </div>
            </div>

            <div>
                <h3 class="font-display font-bold text-lg text-ink-primary mb-4">{{ __('Engagement per Course') }}</h3>

                @if ($courseStats->isEmpty())
                    <div class="bg-surface border border-border rounded-2xl p-6 text-center text-ink-secondary">
                        {{ __('Belum ada course yang dipublikasikan.') }}
                    </div>
                @else
                    <div class="bg-surface border border-border rounded-2xl overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-border text-left">
                                    <th class="px-6 py-3 font-semibold text-ink-secondary">{{ __('Course') }}</th>
                                    <th class="px-6 py-3 font-semibold text-ink-secondary">{{ __('Track') }}</th>
                                    <th class="px-6 py-3 font-semibold text-ink-secondary text-right">{{ __('Lesson') }}</th>
                                    <th class="px-6 py-3 font-semibold text-ink-secondary text-right">{{ __('Siswa') }}</th>
                                    <th class="px-6 py-3 font-semibold text-ink-secondary text-right">{{ __('Completion Rate') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                @foreach ($courseStats as $row)
                                    <tr>
                                        <td class="px-6 py-3 font-medium text-ink-primary">{{ $row->course_title }}</td>
                                        <td class="px-6 py-3 text-ink-secondary">{{ $row->track_title }}</td>
                                        <td class="px-6 py-3 text-right tabular-nums text-ink-secondary">{{ $row->lessons_count }}</td>
                                        <td class="px-6 py-3 text-right tabular-nums text-ink-secondary">{{ $row->students_count }}</td>
                                        <td class="px-6 py-3 text-right">
                                            <div class="inline-flex items-center gap-2">
                                                <div class="w-20 h-1.5 rounded-full bg-border overflow-hidden">
                                                    <div class="h-full bg-brand" style="width: {{ $row->completion_rate }}%"></div>
                                                </div>
                                                <span class="tabular-nums font-semibold text-ink-primary w-10 text-right">{{ $row->completion_rate }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div>
                <h3 class="font-display font-bold text-lg text-ink-primary mb-4">{{ __('Performa Quiz') }}</h3>

                @if ($quizStats->isEmpty())
                    <div class="bg-surface border border-border rounded-2xl p-6 text-center text-ink-secondary">
                        {{ __('Belum ada quiz.') }}
                    </div>
                @else
                    <div class="bg-surface border border-border rounded-2xl overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-border text-left">
                                    <th class="px-6 py-3 font-semibold text-ink-secondary">{{ __('Quiz') }}</th>
                                    <th class="px-6 py-3 font-semibold text-ink-secondary">{{ __('Course') }}</th>
                                    <th class="px-6 py-3 font-semibold text-ink-secondary text-right">{{ __('Percobaan') }}</th>
                                    <th class="px-6 py-3 font-semibold text-ink-secondary text-right">{{ __('Rata-rata Skor') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                @foreach ($quizStats as $row)
                                    <tr>
                                        <td class="px-6 py-3 font-medium text-ink-primary">{{ $row->quiz_title }}</td>
                                        <td class="px-6 py-3 text-ink-secondary">{{ $row->course_title }}</td>
                                        <td class="px-6 py-3 text-right tabular-nums text-ink-secondary">{{ $row->attempts_count }}</td>
                                        <td class="px-6 py-3 text-right tabular-nums font-semibold text-ink-primary">
                                            {{ $row->attempts_count > 0 ? round($row->average_score).'%' : '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div>
                <h3 class="font-display font-bold text-lg text-ink-primary mb-4">{{ __('Aktivitas Terbaru') }}</h3>

                @if ($recentActivity->isEmpty())
                    <div class="bg-surface border border-border rounded-2xl p-6 text-center text-ink-secondary">
                        {{ __('Belum ada aktivitas.') }}
                    </div>
                @else
                    <div class="bg-surface border border-border rounded-2xl overflow-hidden">
                        <ul class="divide-y divide-border">
                            @foreach ($recentActivity as $progress)
                                <li class="flex items-center gap-4 px-6 py-3">
                                    <span class="w-8 h-8 rounded-full bg-gradient-to-br from-brand to-accent text-white flex items-center justify-center text-xs font-display font-bold shrink-0">
                                        {{ \Illuminate\Support\Str::of($progress->user->name)->substr(0, 1)->upper() }}
                                    </span>
                                    <span class="flex-1 text-sm text-ink-primary">
                                        <span class="font-semibold">{{ $progress->user->name }}</span>
                                        {{ __('menyelesaikan') }}
                                        <span class="font-semibold">{{ $progress->lesson?->title ?? __('lesson yang sudah dihapus') }}</span>
                                        @if ($progress->lesson?->module?->course)
                                            <span class="text-ink-muted">— {{ $progress->lesson->module->course->title }}</span>
                                        @endif
                                    </span>
                                    <span class="text-xs text-ink-muted whitespace-nowrap">{{ $progress->created_at->diffForHumans() }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
