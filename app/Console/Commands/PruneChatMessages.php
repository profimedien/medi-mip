<?php

namespace App\Console\Commands;

use App\Services\ChatPruner;
use Illuminate\Console\Command;

class PruneChatMessages extends Command
{
    protected $signature = 'mip:chat-prune';

    protected $description = 'Chat-Nachrichten nach Ablauf der Speicherdauer endgültig löschen';

    public function handle(ChatPruner $pruner): int
    {
        $this->info($pruner->run().' Nachrichten gelöscht.');

        return self::SUCCESS;
    }
}
