<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the response language from ?lang=, then the session, then the
 * Accept-Language header. A ?lang= choice is remembered for the session, so the
 * topbar switcher survives navigation. Drives the ru/en output of every
 * translatable JSON column.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = config('app.supported_locales', ['en', 'ru']);

        $locale = $request->query('lang')
            ?? $request->session()->get('locale')
            ?? $request->getPreferredLanguage($supported)
            ?? config('app.fallback_locale');

        if (in_array($locale, $supported, true)) {
            app()->setLocale($locale);
            $request->session()->put('locale', $locale);
        }

        return $next($request);
    }
}
