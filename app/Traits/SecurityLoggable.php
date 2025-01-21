<?php

namespace App\Traits;

use App\Models\SecurityLog;
use Illuminate\Http\Request;

trait SecurityLoggable
{
    /**
     * تسجيل محاولة تسجيل دخول فاشلة
     */
    protected function logFailedLogin(Request $request, string $email): void
    {
        SecurityLog::create([
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'event_type' => 'login_failed',
            'description' => "محاولة تسجيل دخول فاشلة باستخدام البريد الإلكتروني: {$email}",
            'route' => $request->path(),
            'request_data' => [
                'email' => $email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ],
            'severity' => 'warning'
        ]);
    }

    /**
     * تسجيل محاولة إعادة تعيين كلمة المرور
     */
    protected function logPasswordReset(Request $request, string $email): void
    {
        SecurityLog::create([
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'event_type' => 'password_reset',
            'description' => "طلب إعادة تعيين كلمة المرور للبريد الإلكتروني: {$email}",
            'route' => $request->path(),
            'request_data' => [
                'email' => $email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ],
            'severity' => 'info'
        ]);
    }

    /**
     * تسجيل تغيير معلومات حساسة
     */
    protected function logSensitiveChange(Request $request, string $type, string $description): void
    {
        SecurityLog::create([
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'event_type' => 'sensitive_change',
            'description' => $description,
            'user_id' => auth()->id(),
            'route' => $request->path(),
            'request_data' => [
                'type' => $type,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ],
            'severity' => 'warning'
        ]);
    }

    /**
     * تسجيل محاولة وصول غير مصرح
     */
    protected function logUnauthorizedAccess(Request $request, string $resource): void
    {
        SecurityLog::create([
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'event_type' => 'unauthorized_access',
            'description' => "محاولة وصول غير مصرح إلى: {$resource}",
            'user_id' => auth()->id(),
            'route' => $request->path(),
            'request_data' => [
                'resource' => $resource,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'method' => $request->method()
            ],
            'severity' => 'danger'
        ]);
    }

    /**
     * تسجيل نشاط مشبوه عام
     */
    protected function logSuspiciousActivity(Request $request, string $description, array $additionalData = []): void
    {
        SecurityLog::create([
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'event_type' => 'suspicious_activity',
            'description' => $description,
            'user_id' => auth()->id(),
            'route' => $request->path(),
            'request_data' => array_merge([
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'method' => $request->method()
            ], $additionalData),
            'severity' => 'danger'
        ]);
    }
}
