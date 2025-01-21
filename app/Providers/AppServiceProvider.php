<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Vite;
use Laravel\Passport\Passport;
use App\Models\Article;
use App\Observers\ArticleObserver;
use App\Models\News;
use App\Observers\NewsObserver;
use Symfony\Component\VarDumper\VarDumper;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Component\VarDumper\Cloner\VarCloner;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
       
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure VarDumper
        VarDumper::setHandler(function ($var) {
            $dumper = new HtmlDumper();
            $dumper->setStyles([
                'default' => 'background-color:#fff; color:#222; line-height:1.2em; font-weight:normal; font:12px Monaco, Consolas, monospace; word-wrap: break-word; white-space: pre-wrap; position:relative; z-index:100000',
                'search-input' => 'id:dump-search; name:dump-search;'
            ]);
            $cloner = new VarCloner();
            $dumper->dump($cloner->cloneVar($var));
        });

        // Set application locale from session or settings
        $locale = config('app.locale', 'ar'); // استبدال مؤقتًا لتجاوز المشكلة
         $locale = session('locale', function() {
             $locale = \App\Models\Setting::get('site_language', config('app.locale', 'ar'));
             session(['locale' => $locale]);
            return $locale;
         });
        if (in_array($locale, ['en', 'ar'])) {
            app()->setLocale($locale);
        }

        // Load Passport keys
        Passport::loadKeysFrom(__DIR__.'/../secrets/oauth');

        // Custom Vite styles
        Vite::useStyleTagAttributes(function (?string $src, string $url, ?array $chunk, ?array $manifest) {
            if ($src !== null) {
                return [
                    'class' => preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?core)-?.*/i", $src) ? 'template-customizer-core-css' :
                              (preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?theme)-?.*/i", $src) ? 'template-customizer-theme-css' : '')
                ];
            }
            return [];
        });

        // Register the observer for Article model
        Article::observe(ArticleObserver::class);

        // Register the observer for News model
        News::observe(NewsObserver::class);

        
    }
   
    
}
