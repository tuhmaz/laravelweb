<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DashboardSecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next)
    {
        // تمرير الطلب إلى التالي والحصول على الاستجابة
        $response = $next($request);

        // إعداد سياسة Content-Security-Policy
        $cspDirectives = [
            "default-src 'self'",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdn.onesignal.com",
            "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net",
            "img-src 'self' data: blob: https://cdn.jsdelivr.net",
            "connect-src 'self' https://cdn.onesignal.com",
            "frame-src 'self' https://cdn.onesignal.com",
        ];

        $response->headers->set('Content-Security-Policy', implode('; ', $cspDirectives));

        // إضافة رؤوس أمان إضافية
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        return $response;
    }
}
