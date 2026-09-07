<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the response language from ?lang=, then the Accept-Language header.
 * Drives the ru/en output of every translatable JSON column.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = config('app.supported_locales', ['en', 'ru']);

        $locale = $request->query('lang')
            ?? $request->getPreferredLanguage($supported)
            ?? config('app.fallback_locale');

        if (in_array($locale, $supported, true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
