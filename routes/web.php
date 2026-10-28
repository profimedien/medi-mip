<?php

use App\Http\Controllers\AccessSheetController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\PushSubscriptionController;
use App\Livewire;
use Illuminate\Support\Facades\Route;

// Signierter, zeitlich begrenzter Test-Login nur auf dem Testsystem (erzeugt per: php artisan mip:login-link <benutzername>)
if (app()->environment('local', 'staging')) {
    Route::get('/dev-login/{user}', function (\App\Models\User $user) {
        \Illuminate\Support\Facades\Auth::login($user);
        request()->session()->regenerate();

        return redirect()->route('home');
    })->middleware('signed')->name('dev-login');
}

// KAS-Cronjob (All-Inkl ruft nur URLs auf): treibt den Laravel-Scheduler. Geheimnis in CRON_SECRET.
Route::get('/cron/{secret}', function (string $secret) {
    abort_unless(filled(config('mip.cron_secret')) && hash_equals(config('mip.cron_secret'), $secret), 403);
    ignore_user_abort(true);
    set_time_limit(120);
    // Der Scheduler startet jeden Befehl als eigenen PHP-Prozess. Im Webaufruf findet er sonst das System-PHP (zu alt) statt php84.
    if ($php = config('mip.php_binary')) {
        putenv('PHP_BINARY='.$php);
    }
    // Fehlgeschlagene Befehle mitschreiben, damit der Aufruf nicht trotzdem "ok" meldet
    $failed = [];
    \Illuminate\Support\Facades\Event::listen(\Illuminate\Console\Events\ScheduledTaskFinished::class, function ($e) use (&$failed) {
        if ($e->task->exitCode) {
            $failed[] = $e->task->command;
        }
    });
    \Illuminate\Support\Facades\Event::listen(\Illuminate\Console\Events\ScheduledTaskFailed::class, function ($e) use (&$failed) {
        $failed[] = $e->task->command;
    });
    \Illuminate\Support\Facades\Artisan::call('schedule:run');

    $failed = array_values(array_unique($failed));
    \App\Models\Setting::set('cron_last_run', now()->toIso8601String());
    if ($failed) {
        \App\Models\Setting::set('cron_last_error', now()->format('d.m.Y H:i').': '.implode(', ', $failed));

        return response("Fehler:\n".implode("\n", $failed), 500)->header('Content-Type', 'text/plain');
    }
    \App\Models\Setting::set('cron_last_ok', now()->toIso8601String());

    return response('ok', 200)->header('Content-Type', 'text/plain');
})->middleware('throttle:10,1')->name('cron');

Route::middleware('guest')->group(function () {
    Route::get('/anmelden', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/anmelden', [AuthController::class, 'login'])->middleware('throttle:300,1'); // Klinik-WLAN: viele Kolleginnen und Kollegen teilen eine IP
});

Route::middleware(['auth', 'account.ready'])->group(function () {
    Route::post('/abmelden', [AuthController::class, 'logout'])->name('logout');
    Route::get('/passwort-festlegen', [AuthController::class, 'showSetPassword'])->name('password.set');
    Route::post('/passwort-festlegen', [AuthController::class, 'setPassword'])->name('password.store');

    Route::get('/', Livewire\Home::class)->name('home');
    Route::get('/news', Livewire\News\Index::class)->name('news.index');
    Route::get('/news/{post}', Livewire\News\Show::class)->name('news.show');
    Route::get('/kalender', Livewire\Calendar::class)->name('calendar');
    Route::get('/faq', Livewire\FaqPage::class)->name('faq');
    Route::get('/ansprechpartner', Livewire\Contacts::class)->name('contacts');
    Route::get('/ansprechpartner/{contact}', Livewire\ContactShow::class)->name('contacts.show');
    Route::get('/galerien', Livewire\Galleries\Index::class)->name('galleries.index');
    Route::get('/galerien/{gallery}', Livewire\Galleries\Show::class)->name('galleries.show');
    Route::get('/organigramm', Livewire\OrgChart::class)->name('orgchart');
    Route::get('/onboarding', Livewire\Onboarding::class)->name('onboarding');
    Route::get('/rueckmeldungen', Livewire\Forms\Index::class)->name('forms.index');
    Route::get('/rueckmeldungen/{form}', Livewire\Forms\Show::class)->name('forms.show');
    Route::get('/mitmachen', Livewire\Participation::class)->name('participation');
    Route::get('/kleinanzeigen', Livewire\Classifieds::class)->name('classifieds');
    Route::get('/kleinanzeigen/{classified}', Livewire\Classifieds\Show::class)->name('classifieds.show');
    Route::get('/verzeichnis', Livewire\Directory\Index::class)->name('directory.index');
    Route::get('/verzeichnis/{user}', Livewire\Directory\Show::class)->name('directory.show');
    Route::get('/medigram/{group?}', Livewire\Chat::class)->name('chat');
    Route::get('/mitteilungen', Livewire\Notifications::class)->name('notifications');
    Route::get('/profil', Livewire\Profile::class)->name('profile');

    Route::get('/medien/{media}/{conversion?}', [MediaController::class, 'show'])->name('media.show');
    Route::post('/push/abonnieren', [PushSubscriptionController::class, 'store'])->name('push.subscribe');
    Route::post('/push/abmelden', [PushSubscriptionController::class, 'destroy'])->name('push.unsubscribe');

    // Zugangszettel (nur Admins, Prüfung im Controller)
    Route::get('/zugangszettel', [AccessSheetController::class, 'show'])->name('access-sheet');
});
