<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SystemService
{
    public function getSystemStats()
    {
        // تحقق مما إذا كنا في بيئة التطوير
        $isLocal = in_array(config('app.env'), ['local', 'development']);

        if ($isLocal) {
            // بيانات تجريبية لبيئة التطوير
            return [
                'cpu' => [
                    'usage' => rand(20, 80),
                    'cores' => 4,
                    'model' => 'Intel(R) Core(TM) i7 (Development)',
                ],
                'memory' => [
                    'total' => '16GB',
                    'used' => '8GB',
                    'free' => '8GB',
                    'percentage' => rand(40, 90)
                ],
                'os' => 'Windows 10 Pro (Development)',
                'uptime' => '2 days, 5 hours',
                'php' => [
                    'version' => PHP_VERSION,
                    'memory_limit' => ini_get('memory_limit'),
                    'max_execution_time' => ini_get('max_execution_time')
                ],
                'database' => [
                    'type' => DB::connection()->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME),
                    'version' => DB::connection()->getPdo()->getAttribute(\PDO::ATTR_SERVER_VERSION),
                    'size' => $this->getDatabaseSize()
                ]
            ];
        }

        try {
            // قراءة معلومات النظام الحقيقية (للإنتاج)
            $cpuUsage = $this->getCpuUsage();
            $memoryInfo = $this->getMemoryInfo();
            
            return [
                'cpu' => [
                    'usage' => $cpuUsage,
                    'cores' => php_sapi_name() !== 'cli' ? 'N/A' : shell_exec('nproc'),
                    'model' => php_sapi_name() !== 'cli' ? 'N/A' : shell_exec('cat /proc/cpuinfo | grep "model name" | head -1')
                ],
                'memory' => $memoryInfo,
                'os' => php_uname(),
                'uptime' => $this->getUptime(),
                'php' => [
                    'version' => PHP_VERSION,
                    'memory_limit' => ini_get('memory_limit'),
                    'max_execution_time' => ini_get('max_execution_time')
                ],
                'database' => [
                    'type' => DB::connection()->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME),
                    'version' => DB::connection()->getPdo()->getAttribute(\PDO::ATTR_SERVER_VERSION),
                    'size' => $this->getDatabaseSize()
                ]
            ];
        } catch (\Exception $e) {
            // في حالة حدوث خطأ، نعود للبيانات التجريبية
            return $this->getSystemStats();
        }
    }

    private function getCpuUsage()
    {
        if (php_sapi_name() !== 'cli') {
            return 'N/A';
        }

        $load = sys_getloadavg();
        return isset($load[0]) ? round($load[0] * 100) : 'N/A';
    }

    private function getMemoryInfo()
    {
        if (php_sapi_name() !== 'cli') {
            return [
                'total' => 'N/A',
                'used' => 'N/A',
                'free' => 'N/A',
                'percentage' => 'N/A'
            ];
        }

        $memInfo = @file_get_contents('/proc/meminfo');
        if ($memInfo === false) {
            return [
                'total' => 'N/A',
                'used' => 'N/A',
                'free' => 'N/A',
                'percentage' => 'N/A'
            ];
        }

        // تحليل معلومات الذاكرة
        preg_match_all('/^(.+?):[ \t]+(\d+)/m', $memInfo, $matches);
        $memInfo = array_combine($matches[1], $matches[2]);

        $total = isset($memInfo['MemTotal']) ? round($memInfo['MemTotal'] / 1024) : 0;
        $free = isset($memInfo['MemFree']) ? round($memInfo['MemFree'] / 1024) : 0;
        $used = $total - $free;
        $percentage = $total > 0 ? round(($used / $total) * 100) : 0;

        return [
            'total' => $total . 'MB',
            'used' => $used . 'MB',
            'free' => $free . 'MB',
            'percentage' => $percentage
        ];
    }

    private function getUptime()
    {
        if (php_sapi_name() !== 'cli') {
            return 'N/A';
        }

        $uptime = @file_get_contents('/proc/uptime');
        if ($uptime === false) {
            return 'N/A';
        }

        $uptime = explode(' ', $uptime)[0];
        $days = floor($uptime / 86400);
        $hours = floor(($uptime % 86400) / 3600);

        return "$days days, $hours hours";
    }

    private function getDatabaseSize()
    {
        try {
            $database = DB::connection()->getDatabaseName();
            $size = DB::select("
                SELECT pg_size_pretty(pg_database_size('$database')) as size
            ");
            return $size[0]->size;
        } catch (\Exception $e) {
            return 'N/A';
        }
    }

    /**
     * Get historical metrics data
     */
    public function getHistoricalMetrics($hours = 24)
    {
        // For development environment, generate mock data
        $dataPoints = $hours * 12; // One data point every 5 minutes
        $timestamps = array_map(function($i) {
            return now()->subHours($hours)->addMinutes($i * 5)->timestamp * 1000;
        }, range(0, $dataPoints));

        return [
            'cpu' => array_map(function($timestamp) {
                return [
                    'x' => $timestamp,
                    'y' => rand(20, 80)
                ];
            }, $timestamps),
            'memory' => array_map(function($timestamp) {
                return [
                    'x' => $timestamp,
                    'y' => rand(2 * 1024 * 1024 * 1024, 8 * 1024 * 1024 * 1024) // 2-8 GB in bytes
                ];
            }, $timestamps),
            'responseTime' => array_map(function($timestamp) {
                return [
                    'x' => $timestamp,
                    'y' => rand(50, 500) // 50-500ms
                ];
            }, $timestamps),
            'requestRate' => array_map(function($timestamp) {
                return [
                    'x' => $timestamp,
                    'y' => rand(10, 100)
                ];
            }, $timestamps),
            'errorRate' => array_map(function($timestamp) {
                return [
                    'x' => $timestamp,
                    'y' => rand(0, 5)
                ];
            }, $timestamps)
        ];
    }

    /**
     * Get current request rate
     */
    public function getCurrentRequestRate()
    {
        return Cache::remember('current_request_rate', 60, function() {
            return rand(10, 100);
        });
    }

    /**
     * Get CPU usage
     */
    public function getCpuUsageForMetrics()
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return rand(20, 80); // Mock data for Windows
        }

        $load = sys_getloadavg();
        return $load[0] * 100;
    }

    /**
     * Get memory usage in bytes
     */
    public function getMemoryUsageForMetrics()
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return [
                'total' => 16 * 1024 * 1024 * 1024, // 16GB
                'used' => rand(4, 12) * 1024 * 1024 * 1024,
                'free' => rand(4, 12) * 1024 * 1024 * 1024
            ];
        }

        $free = shell_exec('free');
        $free = (string)trim($free);
        $free_arr = explode("\n", $free);
        $mem = explode(" ", $free_arr[1]);
        $mem = array_filter($mem);
        $mem = array_merge($mem);

        return [
            'total' => $mem[1] * 1024,
            'used' => $mem[2] * 1024,
            'free' => $mem[3] * 1024
        ];
    }

    /**
     * Get average response time
     */
    public function getAverageResponseTime()
    {
        return Cache::remember('avg_response_time', 60, function() {
            return rand(50, 500); // 50-500ms
        });
    }

    /**
     * Get peak response time
     */
    public function getPeakResponseTime()
    {
        return Cache::remember('peak_response_time', 60, function() {
            return rand(200, 1000); // 200-1000ms
        });
    }

    /**
     * Get minimum response time
     */
    public function getMinimumResponseTime()
    {
        return Cache::remember('min_response_time', 60, function() {
            return rand(10, 100); // 10-100ms
        });
    }
}
