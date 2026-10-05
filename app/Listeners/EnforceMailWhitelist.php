<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mime\Address;

/** Auf dem Testsystem nur an freigegebene Adressen senden (MIP_MAIL_WHITELIST) */
class EnforceMailWhitelist
{
    public function handle(MessageSending $event): bool
    {
        $whitelist = array_map('strtolower', config('mip.mail_whitelist', []));
        if ($whitelist === []) {
            return true;
        }

        $message = $event->message;
        $allowed = array_filter($message->getTo(), fn (Address $a) => in_array(strtolower($a->getAddress()), $whitelist, true));
        $dropped = array_diff(array_map(fn (Address $a) => $a->getAddress(), $message->getTo()), array_map(fn (Address $a) => $a->getAddress(), $allowed));

        if ($dropped) {
            Log::info('Mail-Whitelist: nicht versendet an '.implode(', ', $dropped).' · Betreff: '.$message->getSubject());
        }
        $message->cc();
        $message->bcc();
        if ($allowed === []) {
            return false;
        }
        $message->to(...array_values($allowed));

        return true;
    }
}
