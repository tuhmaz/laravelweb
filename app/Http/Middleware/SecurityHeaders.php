<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * قائمة الامتدادات المسموح بها للملفات الثابتة.
     */
    private array $allowedStaticExtensions = [
        '.js', '.css', '.jpg', '.png', '.svg', '.woff', '.woff2', '.ttf', '.eot',
    ];

    /**
     * قائمة المسارات المستثناة.
     */
    private array $bypassPaths = [
        'login', 'register', 'password/reset', 'password/email', 'logout', 'js/*', 'css/*', 'images/*',
    ];

    /**
     * معالجة الطلب وإضافة رؤوس الأمان الأساسية.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // استثناء الملفات الثابتة أو المسارات المحددة
        if ($this->isStaticFile($request->path()) || $this->isBypassedPath($request)) {
            return $response;
        }

        // إزالة الرؤوس غير الضرورية
        $this->removeUnnecessaryHeaders($response);

        // إضافة رؤوس الأمان الأساسية
        $this->addSecurityHeaders($response);

        return $response;
    }

    /**
     * التحقق مما إذا كان الطلب لملف ثابت بناءً على الامتداد.
     */
    private function isStaticFile(string $path): bool
    {
        foreach ($this->allowedStaticExtensions as $extension) {
            if (str_ends_with($path, $extension)) {
                return true;
            }
        }
        return false;
    }

    /**
     * التحقق مما إذا كان الطلب ينتمي إلى مسار مستثنى.
     */
    private function isBypassedPath(Request $request): bool
    {
        return $request->is($this->bypassPaths);
    }

    /**
     * إزالة الرؤوس غير الضرورية لتحسين الأمان.
     */
    private function removeUnnecessaryHeaders(Response $response): void
    {
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');
    }

    /**
     * إضافة رؤوس الأمان الأساسية.
     */
    private function addSecurityHeaders(Response $response): void
    {
        $headers = [
            'X-Content-Type-Options' => 'nosniff', // منع المتصفح من تفسير أنواع الملفات بشكل غير صحيح
            'X-Frame-Options' => 'SAMEORIGIN', // منع التضمين داخل iframe خارجي
            'X-XSS-Protection' => '1; mode=block', // الحماية من XSS
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains', // تفعيل HSTS
            'Referrer-Policy' => 'no-referrer-when-downgrade', // التحكم في إرسال الـ Referrer
        ];

        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }
    }
}
