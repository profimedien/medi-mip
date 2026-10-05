<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Notifications\InboxNotification;
use App\Notifications\PortalNotification;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    public function test_neue_news_benachrichtigt_genau_die_zielgruppe_einmal(): void
    {
        Notification::fake();
        $in = $this->person('Fürth', ['Physiotherapie']);
        $out = $this->person('Roth', ['Physiotherapie']);
        Post::create(['title' => 'Fürth intern', 'audience_all' => false, 'audience_locations' => [$this->location('Fürth')->id], 'published_at' => now()->subMinute()]);

        $this->artisan('mip:notify')->assertSuccessful();
        $this->artisan('mip:notify')->assertSuccessful();

        Notification::assertSentToTimes($in, PortalNotification::class, 1);
        Notification::assertNotSentTo($out, PortalNotification::class);
    }

    public function test_geplante_news_wird_erst_zum_zeitpunkt_verschickt(): void
    {
        Notification::fake();
        $user = $this->person();
        Post::create(['title' => 'Später', 'audience_all' => true, 'published_at' => now()->addHour()]);
        $this->artisan('mip:notify');
        Notification::assertNothingSent();
        $this->travel(2)->hours();
        $this->artisan('mip:notify');
        Notification::assertSentTo($user, PortalNotification::class);
    }

    public function test_mail_whitelist_verwirft_fremde_empfaenger(): void
    {
        config(['mip.mail_whitelist' => ['info@profimedien.net'], 'mail.default' => 'array']);
        \Illuminate\Support\Facades\Notification::route('mail', ['fremd@example.org'])->notifyNow(new InboxNotification('Test', 'x', 'y'));
        \Illuminate\Support\Facades\Notification::route('mail', ['info@profimedien.net', 'fremd@example.org'])->notifyNow(new InboxNotification('Test 2', 'x', 'y'));

        $sent = app('mailer')->getSymfonyTransport()->messages();
        $this->assertCount(1, $sent);
        $to = array_map(fn ($a) => $a->getAddress(), $sent[0]->getOriginalMessage()->getTo());
        $this->assertSame(['info@profimedien.net'], $to);
    }
}
