<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\URL;

class LoginLink extends Command
{
    protected $signature = 'mip:login-link {username} {--minutes=10}';

    protected $description = 'Signierten Einmal-Login für Tests erzeugen (nur Testsystem)';

    public function handle(): int
    {
        if (! app()->environment('local', 'staging')) {
            $this->error('Nur auf dem Testsystem verfügbar.');

            return self::FAILURE;
        }
        $user = User::where('username', $this->argument('username'))->firstOrFail();
        $this->line(URL::temporarySignedRoute('dev-login', now()->addMinutes((int) $this->option('minutes')), ['user' => $user]));

        return self::SUCCESS;
    }
}
