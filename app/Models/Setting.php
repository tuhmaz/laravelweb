<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public $timestamps = false;

    /**
     * Get a setting value by key
     */
    public static function get($key, $default = null)
    {
        return Cache::rememberForever('settings', function () {
            return self::pluck('value', 'key')->toArray();
        })[$key] ?? $default;
    }

    /**
     * Set a setting value
     */
    public static function set($key, $value)
    {
        $setting = self::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        Cache::forget('settings');

        return $setting;
    }

    /**
     * Delete a setting
     */
    public static function remove($key)
    {
        self::where('key', $key)->delete();
        Cache::forget('settings');
    }
}
