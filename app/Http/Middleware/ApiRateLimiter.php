<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ApiRateLimiter
{
    protected array $limits = [
        'api' => [
            'max' => 60,
            'decay' => 60
        ],
        'file-uploads' => [
            'max' => 100,
            'decay' => 3600
        ],
        'login' => [
            'max' => 5,
            'decay' => 60
        ]
    ];

    public function handle(Request $request, Closure $next, string $type = 'api'): Response
    {
        if (!isset($this->limits[$type])) {
            $type = 'api';
        }

        $key = match($type) {
            'file-uploads' => 'file-uploads|' . ($request->user()?->id ?: $request->ip()),
            'login' => 'login|' . $request->ip(),
            default => 'api|' . ($request->user()?->id ?: $request->ip())
        };

        if (RateLimiter::tooManyAttempts($key, $this->limits[$type]['max'])) {
            return response()->json([
                'message' => 'Too Many Attempts.',
                'retry_after' => RateLimiter::availableIn($key)
            ], 429);
        }

        RateLimiter::hit($key, $this->limits[$type]['decay']);

        $response = $next($request);

        // Add rate limit headers only for non-binary responses
        if (!$response instanceof BinaryFileResponse) {
            $remaining = RateLimiter::remaining($key, $this->limits[$type]['max']);
            $response->headers->set('X-RateLimit-Limit', $this->limits[$type]['max']);
            $response->headers->set('X-RateLimit-Remaining', $remaining);
        }

        return $response;
    }
}
