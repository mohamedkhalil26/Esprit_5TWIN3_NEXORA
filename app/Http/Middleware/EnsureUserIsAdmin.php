<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Autorise uniquement les administrateurs à accéder au back office.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isAdmin()) {
            return redirect()->route('home')->with('error', 'Accès réservé aux administrateurs.');
        }

        return $next($request);
    }
}
