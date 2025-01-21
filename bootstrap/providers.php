<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\MenuServiceProvider::class,
    App\Providers\CacheServiceProvider::class,
    App\Providers\AssetServiceProvider::class,
    App\Providers\LocaleServiceProvider::class,
    App\Providers\MonitoringServiceProvider::class,
    App\Providers\EventServiceProvider::class,
    App\Providers\RouteServiceProvider::class,
    App\Providers\MiddlewareServiceProvider::class,
    App\Providers\JetstreamServiceProvider::class,
    App\Providers\SettingsServiceProvider::class,
    App\Providers\RateLimiterServiceProvider::class,
    App\Providers\AssetServiceProvider::class,
    App\Providers\PerformanceServiceProvider::class,
     /*
     * Laravel Framework Service Providers...
     */
    Illuminate\Auth\AuthServiceProvider::class,
    Illuminate\Broadcasting\BroadcastServiceProvider::class,
    Illuminate\Bus\BusServiceProvider::class,
    Illuminate\Cache\CacheServiceProvider::class,
    Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
    Illuminate\Cookie\CookieServiceProvider::class,
    Illuminate\Database\DatabaseServiceProvider::class,
    Illuminate\Encryption\EncryptionServiceProvider::class,
    Illuminate\Filesystem\FilesystemServiceProvider::class,
    Illuminate\Foundation\Providers\FoundationServiceProvider::class,
    Illuminate\Hashing\HashServiceProvider::class,
    Illuminate\Mail\MailServiceProvider::class,
    Illuminate\Notifications\NotificationServiceProvider::class,
    Illuminate\Pagination\PaginationServiceProvider::class,
    Illuminate\Pipeline\PipelineServiceProvider::class,
    Illuminate\Queue\QueueServiceProvider::class,
    Illuminate\Redis\RedisServiceProvider::class,
    Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
    Illuminate\Session\SessionServiceProvider::class,
    Illuminate\Translation\TranslationServiceProvider::class,
    Illuminate\Validation\ValidationServiceProvider::class,
    Illuminate\View\ViewServiceProvider::class,
    Intervention\Image\ImageServiceProvider::class,
     /*
     * Package Service Providers...
     */
    Stevebauman\Location\LocationServiceProvider::class,

    /*
     * Application Service Providers...
     */
    App\Providers\AppServiceProvider::class,
    App\Providers\MonitoringServiceProvider::class,
    App\Providers\EventServiceProvider::class,
    App\Providers\RouteServiceProvider::class,
    App\Providers\MiddlewareServiceProvider::class,
    App\Providers\JetstreamServiceProvider::class,
    App\Providers\MenuServiceProvider::class,
    App\Providers\SettingsServiceProvider::class,
    App\Providers\RateLimiterServiceProvider::class,


];
