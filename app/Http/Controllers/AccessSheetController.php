<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\AdminScope;
use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Druckbare Zugangszettel (PDF) mit Benutzername, Startcode und QR-Code zur Anmeldeseite.
 * Gedruckt werden nur Personen mit offenem Startcode. Bestehende Passwörter werden hier nie
 * zurückgesetzt, das geht nur bewusst über die Admin-Aktion "Neuen Startcode erzeugen".
 */
class AccessSheetController extends Controller
{
    public function show(Request $request): Response
    {
        $admin = $request->user();
        abort_unless($admin->isAdmin(), 403);

        $ids = array_filter(array_map('intval', explode(',', (string) $request->query('ids'))));
        $users = AdminScope::users($admin)->whereIn('id', $ids)->whereNotNull('start_code')->orderBy('last_name')->get();
        abort_if($users->isEmpty(), 404);

        $qr = new QRCode(new QROptions(['outputBase64' => true, 'scale' => 6, 'quietzoneSize' => 1]));
        $sheets = $users->map(function (User $user) use ($qr) {
            $code = $user->start_code;
            $url = route('login', ['u' => $user->username]);

            return [
                'user' => $user,
                'code' => $code,
                'url' => route('login'),
                'qr' => $qr->render($url),
            ];
        });

        return Pdf::loadView('pdf.access-sheets', ['sheets' => $sheets])
            ->setPaper('a4')
            ->stream('zugangszettel.pdf');
    }
}
