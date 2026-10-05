<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Mitteilung an Mitarbeitende: immer im Portal, dazu E-Mail (wenn Adresse und Einstellung)
 * und Push (wenn auf einem Gerät aktiviert).
 */
class PortalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $body,
        public string $url,
        public array $extra = [],
        public ?string $mailSetting = null,
        public bool $sendMail = true,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        if ($notifiable instanceof User) {
            if ($this->sendMail && $notifiable->email && (! $this->mailSetting || $notifiable->notificationSetting($this->mailSetting))) {
                $channels[] = 'mail';
            }
            if ($notifiable->pushSubscriptions()->exists() && $notifiable->notificationSetting('push')) {
                $channels[] = WebPushChannel::class;
            }
        }

        return $channels;
    }

    public function toArray(object $notifiable): array
    {
        return ['title' => $this->title, 'body' => $this->body, 'url' => $this->url] + $this->extra;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject($this->title.' · medi Interaktionsportal')
            ->greeting('Hallo '.$notifiable->first_name.',')
            ->line($this->body)
            ->action('Im Portal öffnen', $this->url)
            ->salutation('Ihr medi Interaktionsportal');
    }

    public function toWebPush(object $notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)->title($this->title)->body($this->body)
            ->icon('/images/icon-192.png')->data(['url' => $this->url]);
    }
}
