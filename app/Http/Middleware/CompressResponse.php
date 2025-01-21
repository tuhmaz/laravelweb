<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Config;

class CompressResponse
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // التحقق مما إذا كان يجب ضغط الطلب
        if ($this->shouldCompress($request)) {
            $this->configureCompression();
        }

        $response = $next($request);

        // التحقق من ضغط المحتوى
        if (!$this->isCompressibleContent($response)) {
            return $response;
        }

        // إضافة رؤوس الأمان، التخزين المؤقت، والتحسينات الأخرى
        $this->addCacheHeaders($response);
        $this->addSecurityHeaders($response);
        $this->addETag($response);
        $this->addPerformanceHeaders($response);

        return $response;
    }

    /**
     * تحديد ما إذا كان يجب ضغط الطلب.
     */
    protected function shouldCompress(Request $request): bool
    {
        if (!Config::get('app.compression.enabled', true)) {
            return false;
        }

        if (!$request->isMethod('GET')) {
            return false;
        }

        if ($request->ajax() || $request->headers->has('X-No-Compression')) {
            return false;
        }

        $acceptEncoding = $request->header('Accept-Encoding', '');
        return str_contains($acceptEncoding, 'gzip') ||
               str_contains($acceptEncoding, 'deflate') ||
               str_contains($acceptEncoding, 'br');
    }

    /**
     * تكوين إعدادات الضغط.
     */
    protected function configureCompression(): void
    {
        $level = Config::get('app.compression.level', 8);
        ini_set('zlib.output_compression', 'On');
        ini_set('zlib.output_compression_level', $level);
    }

    /**
     * تحديد ما إذا كان المحتوى قابل للضغط.
     */
    protected function isCompressibleContent(Response $response): bool
    {
        $contentType = $response->headers->get('Content-Type', '');
        $allowedTypes = Config::get('app.compression.types', ['text/html', 'application/json']);

        foreach ($allowedTypes as $type) {
            if (str_starts_with($contentType, $type)) {
                return strlen($response->getContent() ?? '') >= Config::get('app.compression.threshold', 1024);
            }
        }

        return false;
    }

    /**
     * إضافة رؤوس التخزين المؤقت.
     */
    protected function addCacheHeaders(Response $response): void
    {
        $maxAge = Config::get('app.compression.cache_max_age', 86400);

        $response->headers->set('Cache-Control', "public, max-age={$maxAge}, must-revalidate");
        $response->headers->set('Vary', 'Accept-Encoding, User-Agent');
        $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');
        $response->headers->set('Expires', gmdate('D, d M Y H:i:s', time() + $maxAge) . ' GMT');
    }

    /**
     * إضافة رؤوس الأمان.
     */
    protected function addSecurityHeaders(Response $response): void
    {
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        if ($this->shouldEnableHSTS()) {
            $this->addHSTSHeader($response);
        }
    }

    /**
     * تحقق مما إذا كان يجب تمكين HSTS.
     */
    protected function shouldEnableHSTS(): bool
    {
        $config = Config::get('app.compression.security.hsts', []);
        return ($config['enabled'] ?? false) && request()->secure();
    }

    /**
     * إضافة رأس HSTS.
     */
    protected function addHSTSHeader(Response $response): void
    {
        $config = Config::get('app.compression.security.hsts', []);
        $header = "max-age=" . ($config['max_age'] ?? 31536000);

        if ($config['include_subdomains'] ?? true) {
            $header .= '; includeSubDomains';
        }

        if ($config['preload'] ?? true) {
            $header .= '; preload';
        }

        $response->headers->set('Strict-Transport-Security', $header);
    }

    /**
     * إضافة ETag للتحقق من التغييرات.
     */
    protected function addETag(Response $response): void
    {
        $content = $response->getContent();
        if ($content) {
            $response->headers->set('ETag', '"' . md5($content) . '"');
        }
    }

    /**
     * إضافة رؤوس تحسين الأداء.
     */
    protected function addPerformanceHeaders(Response $response): void
    {
        // Removed incorrect preload header
    }
}
