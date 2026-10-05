<?php

use Illuminate\Support\Facades\Schedule;

// Läuft per Cron jede Minute: php84 artisan schedule:run
Schedule::command('mip:notify')->everyMinute()->withoutOverlapping();
Schedule::command('queue:work --stop-when-empty --max-time=50')->everyMinute()->withoutOverlapping();
Schedule::command('mip:sync-chat')->hourly();
Schedule::command('mip:chat-digest')->dailyAt('17:00');
Schedule::command('media-library:clean --force')->weekly();
