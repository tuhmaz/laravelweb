<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckApiAccess
{
    protected $protectedPaths = [
        'api/users',
        'api/dashboard',
        'api/admin',
        'api/settings'
    ];

    protected $publicPaths = [
        'api/subjects',
        'api/semesters',
        'api/files'
    ];

    protected $allowedUserAgents = [
        'Mobile-App',
        'PostmanRuntime',
        'Mozilla',
        'Chrome',
        'Safari',
        'Edge',
        'Firefox'
    ];

    protected $maxAttempts = 60;
    protected $decayMinutes = 1;

    public function handle(Request $request, Closure $next): Response
    {
        // Skip non-API routes
        if (!str_starts_with($request->path(), 'api')) {
            return $next($request);
        }

        // Log request
        $this->logRequest($request);

        // Check if public route
        if ($this->isPublicRoute($request)) {
            return $next($request);
        }

        // Check rate limiting
        if ($this->isRateLimited($request)) {
            return response()->json([
                'status' => 'error',
                'message' => 'تم تجاوز الحد المسموح من الطلبات'
            ], 429);
        }

        // Validate API Authentication
        $authResult = $this->validateApiAuthentication($request);
        if (!$authResult['success']) {
            return response()->json([
                'status' => 'error',
                'message' => $authResult['message']
            ], 401);
        }

        // Validate User Agent
        if (!$this->validateUserAgent($request)) {
            return response()->json([
                'status' => 'error',
                'message' => 'غير مصرح بالوصول. User-Agent غير صالح.'
            ], 403);
        }

        return $next($request);
    }

    private function logRequest(Request $request): void
    {
        Log::info('API Request', [
            'path' => $request->path(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'token' => substr($request->header('X-API-TOKEN') ?? '', 0, 8) . '***',
            'key' => substr($request->header('X-API-KEY') ?? '', 0, 8) . '***'
        ]);
    }

    private function isPublicRoute(Request $request): bool
    {
        $path = $request->path();

        if (in_array($path, $this->publicPaths)) {
            return true;
        }

        $publicPatterns = ['api/subjects/', 'api/semesters/', 'api/files/'];
        foreach ($publicPatterns as $pattern) {
            if (str_contains($path, $pattern)) {
                return true;
            }
        }

        return false;
    }

    protected function validateApiAuthentication(Request $request): array
    {
        // Check for API Key first
        $apiKey = $request->header('X-API-KEY') ?? $request->query('api_key');
        if (!empty($apiKey)) {
            if ($apiKey === config('app.api_key')) {
                return ['success' => true, 'message' => ''];
            }
        }

        // Check for API Token as fallback
        $token = $request->header('X-API-TOKEN') ?? $request->query('api_token');
        if (!empty($token)) {
            if ($token === config('app.api_token')) {
                return ['success' => true, 'message' => ''];
            }
        }

        return [
            'success' => false,
            'message' => 'غير مصرح بالوصول. يرجى توفير مفتاح API صالح.'
        ];
    }

    private function validateUserAgent(Request $request): bool
    {
        $userAgent = $request->header('User-Agent');
        if (!$userAgent) {
            return false;
        }

        foreach ($this->allowedUserAgents as $agent) {
            if (stripos($userAgent, $agent) !== false) {
                return true;
            }
        }
        return false;
    }

    private function isRateLimited(Request $request): bool
    {
        $key = sprintf('api_limit:%s', $request->ip());
        $attempts = Cache::get($key, 0);

        if ($attempts >= $this->maxAttempts) {
            return true;
        }

        Cache::add($key, 1, now()->addMinutes($this->decayMinutes));

        if ($attempts > 0) {
            Cache::increment($key);
        }

        return false;
    }
}
