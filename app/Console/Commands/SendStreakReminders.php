<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\StreakReminderNotification;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('streak:remind')]
#[Description('Email students whose active streak will break if they do not complete a lesson today')]
class SendStreakReminders extends Command
{
    public function handle(): int
    {
        $today = Carbon::today();
        $yesterday = $today->copy()->subDay();

        $atRiskUsers = User::role('Student')
            ->where('current_streak', '>', 0)
            ->whereDate('last_activity_date', $yesterday)
            ->where(function ($query) use ($today) {
                $query->whereNull('last_streak_reminder_sent_at')
                    ->orWhereDate('last_streak_reminder_sent_at', '<', $today);
            })
            ->get();

        foreach ($atRiskUsers as $user) {
            $user->notify(new StreakReminderNotification($user->current_streak));
            $user->forceFill(['last_streak_reminder_sent_at' => $today])->save();
        }

        $this->info("Sent {$atRiskUsers->count()} streak reminder(s).");

        return self::SUCCESS;
    }
}
