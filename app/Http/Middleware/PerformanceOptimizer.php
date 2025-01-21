<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class PerformanceOptimizer
{
    /**
     * معالجة الطلب وتحسين الاستجابة
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // تطبيق التحسينات فقط على استجابات HTML
        if ($this->isHtmlResponse($response)) {
            $this->optimizeResponse($response);
        }

        return $response;
    }

    /**
     * التحقق مما إذا كانت الاستجابة HTML
     *
     * @param $response
     * @return bool
     */
    protected function isHtmlResponse($response): bool
    {
        $contentType = $response->headers->get('Content-Type', '');
        return str_contains($contentType, 'text/html');
    }

    /**
     * تحسين الاستجابة
     *
     * @param $response
     * @return void
     */
    protected function optimizeResponse($response): void
    {
        $content = $response->getContent();

        // إضافة Preload headers إذا كانت مفعلة
        if (Config::get('performance.optimization.preload_resources', false)) {
            $this->addPreloadHeaders($response);
        }

        // تطبيق التحميل الكسول للصور إذا كانت مفعلة
        if (Config::get('performance.optimization.lazy_loading', false)) {
            $content = $this->addLazyLoading($content);
        }

        // تحسين HTML
        $content = $this->optimizeHtml($content);

        $response->setContent($content);
    }

    /**
     * إضافة رؤوس Preload لتحميل الموارد المهمة مسبقًا
     *
     * @param $response
     * @return void
     */
    protected function addPreloadHeaders($response): void
    {
        $preloadResources = Config::get('performance.optimization.preload', []);

        foreach ($preloadResources as $resource) {
            $response->headers->set(
                'Link',
                sprintf('</%s>; rel=preload; as=%s', $resource['url'], $resource['type']),
                true
            );
        }
    }

    /**
     * إضافة التحميل الكسول للصور
     *
     * @param string $content
     * @return string
     */
    protected function addLazyLoading(string $content): string
    {
        return preg_replace(
            '/<img((?!loading=)[^>]*)>/i',
            '<img$1 loading="lazy">',
            $content
        );
    }

    /**
     * تحسين HTML
     *
     * @param string $content
     * @return string
     */
    protected function optimizeHtml(string $content): string
    {
        // إزالة التعليقات غير الضرورية
        $content = preg_replace('/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/s', '', $content);

        // إزالة المسافات البيضاء الزائدة
        $content = preg_replace('/\s+/', ' ', $content);

        // تحسين علامة meta viewport
        $content = preg_replace(
            '/<meta name="viewport"[^>]*>/',
            '<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">',
            $content
        );

        return $content;
    }
}
