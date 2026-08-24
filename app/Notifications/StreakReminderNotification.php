<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StreakReminderNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly int $currentStreak)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Jangan sampai streak {$this->currentStreak} hari kamu putus! 🔥")
            ->greeting("Hai, {$notifiable->name}!")
            ->line("Streak belajar kamu sekarang **{$this->currentStreak} hari** berturut-turut.")
            ->line('Kamu belum belajar hari ini — selesaikan minimal 1 lesson sebelum tengah malam biar streak-nya tetap lanjut.')
            ->action('Lanjut Belajar', route('dashboard'))
            ->line('Semangat terus belajarnya!');
    }
}
