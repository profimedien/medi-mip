<?php

namespace App\Console\Commands;

use App\Services\ChatGroupSync;
use Illuminate\Console\Command;

class SyncChatGroups extends Command
{
    protected $signature = 'mip:sync-chat';

    protected $description = 'Automatische Chatgruppen (Ankündigungen, Standorte, Abteilungen) abgleichen';

    public function handle(ChatGroupSync $sync): int
    {
        $sync->run();
        $this->info('Chatgruppen abgeglichen');

        return self::SUCCESS;
    }
}
