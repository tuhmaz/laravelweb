<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use App\Models\Setting;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // First try to get locale from session
        $locale = session('locale');

        // If not in session, get from settings
        if (!$locale) {
            $locale = Setting::get('site_language', config('app.locale', 'en'));
            session(['locale' => $locale]);
        }

        // Set the application locale
        if (in_array($locale, ['en', 'ar'])) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
