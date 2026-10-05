<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Formular-Eingang an eine Fachstelle (Vorschlag, Materialanfrage, Empfehlung, FAQ-Frage) */
class InboxNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $subject, public string $intro, public string $body, public ?string $url = null) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject($this->subject.' · MIP')
            ->greeting($this->subject)
            ->line($this->intro);
        foreach (preg_split("/\n/", $this->body) as $line) {
            $mail->line($line);
        }
        if ($this->url) {
            $mail->action('Im Portal ansehen', $this->url);
        }

        return $mail->salutation('Ihr MIP · Mitarbeiter Informationsportal');
    }
}
