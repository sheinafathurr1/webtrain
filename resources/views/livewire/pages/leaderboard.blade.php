<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

use function Livewire\Volt\{layout, state};

layout('layouts.app');

state([
    // Same top-50 for every viewer, so one shared cache entry serves
    // everyone — short TTL keeps it close to real-time without hitting
    // the DB on every single page view.
    'leaderboard' => fn () => Cache::remember('leaderboard:top50', 60, fn () => User::role('Student')
        ->orderByDesc('total_points')
        ->orderBy('id')
        ->limit(50)
        ->get()),
]);

?>

<div>
    <x-slot:header>
        <h2 class="font-display font-extrabold text-2xl text-ink-primary">{{ __('Leaderboard') }}</h2>
    </x-slot:header>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if ($leaderboard->isEmpty())
                <div class="bg-surface border border-border rounded-2xl p-6 text-center text-ink-secondary">
                    {{ __('Belum ada siswa dengan XP. Mulai belajar untuk naik ke leaderboard!') }}
                </div>
            @else
                @php $top3 = $leaderboard->take(3); $rest = $leaderboard->slice(3)->values(); @endphp

                @if ($top3->isNotEmpty())
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        @foreach ($top3 as $index => $user)
                            @php
                                $rank = $index + 1;
                                $medal = ['🥇', '🥈', '🥉'][$index];
                                $ring = [1 => 'from-gold to-accent', 2 => 'from-ink-muted to-ink-secondary', 3 => 'from-accent to-danger'][$rank];
                            @endphp
                            <div class="rounded-2xl p-6 text-center bg-surface border border-border {{ $user->id === Auth::id() ? 'ring-2 ring-brand' : '' }} {{ $rank === 1 ? 'sm:order-2 sm:-translate-y-3' : ($rank === 2 ? 'sm:order-1' : 'sm:order-3') }}">
                                <div class="text-3xl mb-2">{{ $medal }}</div>
                                <div class="w-12 h-12 mx-auto rounded-full bg-gradient-to-br {{ $ring }} text-white flex items-center justify-center text-lg font-display font-bold mb-2">
                                    {{ Str::of($user->name)->substr(0, 1)->upper() }}
                                </div>
                                <p class="font-semibold text-ink-primary truncate">{{ $user->name }}</p>
                                <p class="font-display text-xl font-extrabold text-gold tabular-nums mt-1">{{ $user->total_points }} XP</p>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($rest->isNotEmpty())
                    <div class="bg-surface border border-border rounded-2xl overflow-hidden">
                        <ul class="divide-y divide-border">
                            @foreach ($rest as $index => $user)
                                @php $rank = $index + 4; @endphp
                                <li class="flex items-center gap-4 px-6 py-3 {{ $user->id === Auth::id() ? 'bg-brand/5' : '' }}">
                                    <span class="w-6 text-sm font-bold text-ink-muted tabular-nums">{{ $rank }}</span>
                                    <span class="w-8 h-8 rounded-full bg-gradient-to-br from-brand to-accent text-white flex items-center justify-center text-xs font-display font-bold shrink-0">
                                        {{ Str::of($user->name)->substr(0, 1)->upper() }}
                                    </span>
                                    <span class="flex-1 font-medium text-ink-primary truncate">{{ $user->name }} @if ($user->id === Auth::id()) <span class="text-xs text-brand font-semibold">({{ __('Kamu') }})</span> @endif</span>
                                    <span class="text-xs text-ink-muted">🔥 {{ $user->current_streak }}</span>
                                    <span class="font-display font-bold text-ink-primary tabular-nums w-16 text-right">{{ $user->total_points }} XP</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (! $leaderboard->contains('id', Auth::id()) && Auth::user()->hasRole('Student'))
                    @php
                        $myRank = User::role('Student')->where('total_points', '>', Auth::user()->total_points)->count() + 1;
                    @endphp
                    <div class="bg-brand/5 border border-brand/20 rounded-2xl p-4 flex items-center gap-4">
                        <span class="w-6 text-sm font-bold text-brand tabular-nums">{{ $myRank }}</span>
                        <span class="w-8 h-8 rounded-full bg-gradient-to-br from-brand to-accent text-white flex items-center justify-center text-xs font-display font-bold shrink-0">
                            {{ Str::of(Auth::user()->name)->substr(0, 1)->upper() }}
                        </span>
                        <span class="flex-1 font-medium text-ink-primary">{{ Auth::user()->name }} <span class="text-xs text-brand font-semibold">({{ __('Kamu') }})</span></span>
                        <span class="font-display font-bold text-ink-primary tabular-nums">{{ Auth::user()->total_points }} XP</span>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
