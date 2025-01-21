<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\SecurityLog;
use App\Models\TrustedIp;
use Symfony\Component\HttpFoundation\Response;

class LogSuspiciousAttempts
{
    /**
     * المسارات التي تعتبر حساسة وتحتاج لمراقبة خاصة
     */
    protected $sensitiveRoutes = [
        'login',
        'register',
        'password/reset',
        'admin',
        'dashboard'
    ];

    /**
     * أنماط محددة في العنوان URL تعتبر مشبوهة
     */
    protected $suspiciousPatterns = [
        'eval\(',
        'base64_',
        '\.\./\.\.',
        'select.+from',
        'union.+select',
        '<script',
        'javascript:',
        'onload\s*=',
        'onerror\s*=',
        'onclick\s*=',
        'admin',
        'wp-admin',
        'wp-login',
        'phpmyadmin'
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // التحقق مما إذا كان IP موثوق
            $isTrusted = TrustedIp::where('ip_address', $request->ip())->exists();
            
            if (!$isTrusted) {
                // فحص المحاولات المشبوهة
                if ($this->isSuspiciousRequest($request)) {
                    $this->logSuspiciousActivity($request, 'suspicious_activity');
                }

                // فحص المسارات الحساسة
                if ($this->isMonitoredRoute($request)) {
                    $this->logSuspiciousActivity($request, 'sensitive_route_access');
                }
            }
        } catch (\Exception $e) {
            // تسجيل الخطأ ولكن السماح للطلب بالمتابعة
            \Log::error('خطأ في فحص الأمان: ' . $e->getMessage());
        }

        return $next($request);
    }

    /**
     * تحديد ما إذا كان الطلب مشبوهاً
     */
    protected function isSuspiciousRequest(Request $request): bool
    {
        $url = strtolower($request->fullUrl());
        $userAgent = strtolower($request->userAgent() ?? '');
        $input = json_encode($request->except(['password', 'password_confirmation']));

        // فحص الأنماط المشبوهة في URL
        foreach ($this->suspiciousPatterns as $pattern) {
            $safePattern = preg_quote($pattern, '/');
            if (preg_match("/\b{$safePattern}\b/i", $url) ||
                preg_match("/\b{$safePattern}\b/i", $input)) {
                return true;
            }
        }

        // فحص User Agent المشبوه
        if (empty($userAgent) || 
            str_contains($userAgent, 'curl') ||
            str_contains($userAgent, 'wget') ||
            str_contains($userAgent, 'python')) {
            return true;
        }

        // فحص عدد الطلبات من نفس IP
        $recentAttempts = SecurityLog::where('ip_address', $request->ip())
            ->where('created_at', '>=', now()->subMinutes(5))
            ->count();

        if ($recentAttempts > 10) {
            return true;
        }

        return false;
    }

    /**
     * تحديد ما إذا كان المسار يحتاج لمراقبة
     */
    protected function isMonitoredRoute(Request $request): bool
    {
        $path = $request->path();
        
        foreach ($this->sensitiveRoutes as $route) {
            if (str_contains($path, $route)) {
                return true;
            }
        }

        return false;
    }

    /**
     * تسجيل النشاط المشبوه
     */
    protected function logSuspiciousActivity(Request $request, string $eventType): void
    {
        $severity = $eventType === 'suspicious_activity' ? 'danger' : 'warning';
        
        SecurityLog::create([
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'event_type' => $eventType,
            'description' => "محاولة وصول مشبوهة إلى: {$request->fullUrl()}",
            'user_id' => auth()->id(),
            'route' => $request->path(),
            'request_data' => [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'input' => $request->except(['password', 'password_confirmation']),
                'headers' => $request->headers->all()
            ],
            'severity' => $severity
        ]);
    }
}
