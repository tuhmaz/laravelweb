<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Cache;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function clearDashboardCache($section = null)
    {
        if ($section) {
            $cacheKey = "dashboard_{$section}";
            Cache::forget($cacheKey);
            
            // Clear related cache keys
            $relatedKeys = [
                "dashboard_{$section}_list",
                "dashboard_{$section}_count",
                "dashboard_{$section}_active",
            ];
            
            foreach ($relatedKeys as $key) {
                Cache::forget($key);
            }
        } else {
            // Clear all dashboard related cache
            $keys = Cache::get('dashboard_cache_keys', []);
            foreach ($keys as $key) {
                Cache::forget($key);
            }
        }
    }
}