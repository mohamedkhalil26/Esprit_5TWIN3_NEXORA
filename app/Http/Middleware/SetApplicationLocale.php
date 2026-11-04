<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class SetApplicationLocale
{
    private const LOCALES = ['fr', 'en', 'ar'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale', $request->user()?->locale ?? config('app.locale', 'fr'));
        $locale = in_array($locale, self::LOCALES, true) ? $locale : 'fr';
        app()->setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}
