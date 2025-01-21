<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        '/auth/google/callback', // استثناء مسار إعادة التوجيه من التحقق من CSRF
        '/smtp/send-test',
        '/smtp/test',
        '/smtp/validate',
        'api/*',  // استثناء جميع مسارات API
        'api/dashboard/*',  // استثناء جميع مسارات لوحة التحكم
        'calendar-events',  // استثناء مسار أحداث التقويم
        'files/filter',  // استثناء مسار تصفية الملفات
    ];
}
