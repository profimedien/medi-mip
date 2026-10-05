<?php

namespace App\Console\Commands;

use App\Models\Form;
use App\Models\Post;
use App\Notifications\PortalNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Verschickt Mitteilungen zu neu veröffentlichten News und Rückmeldungen (auch zeitgesteuert)
 * und Erinnerungen kurz vor Fristende an alle, die noch nicht geantwortet haben.
 */
class DispatchPortalNotifications extends Command
{
    protected $signature = 'mip:notify';

    protected $description = 'Mitteilungen zu neuen Inhalten und Fristen verschicken';

    public function handle(): int
    {
        foreach (Post::published()->whereNull('notified_at')->get() as $post) {
            $post->update(['notified_at' => now()]);
            $recipients = $post->audienceMembers()->where('id', '!=', $post->author_id)->get();
            Notification::send($recipients, new PortalNotification(
                $post->requires_confirmation ? 'Bitte lesen und bestätigen: '.$post->title : 'Neu: '.$post->title,
                $post->teaser ?: 'Es gibt eine neue Nachricht im Portal.',
                route('news.show', $post), ['post_id' => $post->id], 'mail_news', sendMail: $post->notify_mail || $post->requires_confirmation,
            ));
            $this->info("News {$post->id}: {$recipients->count()} Empfänger");
        }

        foreach (Form::published()->whereNull('notified_at')->get() as $form) {
            $form->update(['notified_at' => now()]);
            $recipients = $form->audienceMembers()->get();
            Notification::send($recipients, new PortalNotification(
                'Ihre Rückmeldung ist gefragt: '.$form->title,
                $form->deadline ? 'Bitte antworten Sie bis '.$form->deadline->format('d.m.Y, H:i').' Uhr.' : 'Bitte geben Sie im Portal Rückmeldung.',
                route('forms.show', $form), ['form_id' => $form->id], 'mail_forms',
            ));
            $this->info("Rückmeldung {$form->id}: {$recipients->count()} Empfänger");
        }

        // Erinnerung je Rückmeldung: reminder_hours vor Fristende (Standard 48)
        $candidates = Form::published()->where('send_reminder', true)->whereNull('reminder_sent_at')
            ->whereNotNull('deadline')->where('deadline', '>', now())->get()
            ->filter(fn (Form $f) => $f->deadline->lte(now()->addHours($f->reminder_hours ?: config('mip.form_reminder_hours'))));
        foreach ($candidates as $form) {
            $form->update(['reminder_sent_at' => now()]);
            $recipients = $form->audienceMembers()->whereDoesntHave('formResponses', fn ($q) => $q->where('form_id', $form->id))->get();
            Notification::send($recipients, new PortalNotification(
                'Erinnerung: '.$form->title,
                'Die Frist endet am '.$form->deadline->format('d.m.Y, H:i').' Uhr. Ihre Antwort fehlt noch.',
                route('forms.show', $form), ['form_id' => $form->id], 'mail_forms',
            ));
            $this->info("Erinnerung {$form->id}: {$recipients->count()} Empfänger");
        }

        return self::SUCCESS;
    }
}
