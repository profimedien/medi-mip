<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Gebündelter Hinweis auf ungelesene Chat-Nachrichten, höchstens einmal täglich */
class ChatDigestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** @param array<string,int> $groups Gruppenname => Anzahl ungelesen */
    public function __construct(public array $groups) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $total = array_sum($this->groups);
        $mail = (new MailMessage)->subject($total.' ungelesene Nachrichten im Medigram')
            ->greeting('Hallo '.$notifiable->first_name.',')
            ->line('im Medigram warten ungelesene Nachrichten auf Sie:');
        foreach ($this->groups as $name => $count) {
            $mail->line('· '.$name.': '.$count);
        }

        return $mail->action('Zum Medigram', route('chat'))
            ->line('Diese Zusammenfassung können Sie in Ihrem Profil abschalten.')
            ->salutation('Ihr MIP · Mitarbeiter-Informationsportal');
    }
}
