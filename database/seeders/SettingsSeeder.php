<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'site_name', 'value' => 'موقع الايمان'],
            ['key' => 'site_description', 'value' => 'عالم المعلم العربي'],
            ['key' => 'meta_keywords', 'value' => 'alemedu, keywords'],
            ['key' => 'meta_description', 'value' => 'الميتا الموقع الكامل'],
            ['key' => 'google_analytics_id', 'value' => ''],
            ['key' => 'admin_email', 'value' => 'info@alemedu.com'],
            ['key' => 'facebook_url', 'value' => ''],
            ['key' => 'twitter_url', 'value' => ''],
            ['key' => 'site_language', 'value' => 'ar'],
            ['key' => 'timezone', 'value' => 'UTC'],
            ['key' => 'site_logo', 'value' => 'settings/default-logo.png'],
            ['key' => 'site_favicon', 'value' => 'settings/default-favicon.png'],
            ['key' => 'meta_title', 'value' => 'seo موقع الايمان'],
            ['key' => 'robots_txt', 'value' => "User-agent: *\nDisallow: /"],
            ['key' => 'sitemap_url', 'value' => 'https://alemedu.com/sitemap.xml'],
            ['key' => 'canonical_url', 'value' => 'https://alemedu.com'],
            ['key' => 'facebook_pixel_id', 'value' => '970868040300401'],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->insertOrIgnore($setting);
        }
    }
}
