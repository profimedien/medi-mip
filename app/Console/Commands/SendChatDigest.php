<?php

namespace App\Console\Commands;

use App\Models\ChatMember;
use App\Models\ChatMessage;
use App\Models\User;
use App\Notifications\ChatDigestNotification;
use Illuminate\Console\Command;

class SendChatDigest extends Command
{
    protected $signature = 'mip:chat-digest';

    protected $description = 'Tägliche Mail mit ungelesenen Chat-Nachrichten (nur Personen mit E-Mail und Einwilligung)';

    public function handle(): int
    {
        $users = User::active()->whereNotNull('email')->get()->filter(fn ($u) => $u->notificationSetting('mail_chat_digest'));
        $sent = 0;
        foreach ($users as $user) {
            $groups = [];
            foreach (ChatMember::where('user_id', $user->id)->with('group.users')->get() as $m) {
                $count = ChatMessage::where('chat_group_id', $m->chat_group_id)->where('id', '>', $m->last_read_message_id)
                    ->whereNull('deleted_at')->where('user_id', '!=', $user->id)
                    ->where('created_at', '>=', now()->subDay())->count();
                if ($count) {
                    $groups[$m->group->titleFor($user)] = $count;
                }
            }
            if ($groups) {
                $user->notify(new ChatDigestNotification($groups));
                $sent++;
            }
        }
        $this->info("Zusammenfassung an {$sent} Personen");

        return self::SUCCESS;
    }
}
