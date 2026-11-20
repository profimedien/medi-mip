<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Browser merken sich, das Portal nur per HTTPS aufzurufen (HSTS, 1 Jahr) */
class StrictTransportSecurity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if ($request->isSecure() || str_starts_with((string) config('app.url'), 'https://')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        return $response;
    }
}
