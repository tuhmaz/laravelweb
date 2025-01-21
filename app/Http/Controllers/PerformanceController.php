<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\SystemService;
use Carbon\Carbon;

class PerformanceController extends Controller
{
    protected $systemService;
    protected $cacheTimeout = 300; // 5 minutes cache

    public function __construct(SystemService $systemService)
    {
        $this->systemService = $systemService;
    }

    /**
     * عرض صفحة الأداء
     */
    public function index()
    {
        return view('content.dashboard.performance.index');
    }

    /**
     * عرض صفحة قياسات الأداء
     */
    public function metrics()
    {
        return view('content.dashboard.performance.metrics');
    }

    /**
     * الحصول على قياسات الأداء
     */
    public function getMetrics()
    {
        try {
            $cacheKey = 'performance_metrics';
            
            return Cache::remember($cacheKey, $this->cacheTimeout, function () {
                return response()->json([
                    'server_load' => [
                        'cpu_usage' => $this->systemService->getCpuUsage(),
                        'load_1' => $this->systemService->getLoadAverage(1),
                        'load_5' => $this->systemService->getLoadAverage(5),
                        'load_15' => $this->systemService->getLoadAverage(15),
                        'disk_usage' => $this->systemService->getDiskUsage(),
                    ],
                    'memory_usage' => $this->systemService->getMemoryUsage(),
                    'response_time' => [
                        'average' => $this->systemService->getAverageResponseTime(),
                        'peak' => $this->systemService->getPeakResponseTime(),
                        'minimum' => $this->systemService->getMinimumResponseTime(),
                    ],
                    'database_stats' => [
                        'active_connections' => DB::select('show status where variable_name = "Threads_connected"')[0]->Value ?? 0,
                        'queries_per_second' => DB::select('show status where variable_name = "Questions"')[0]->Value ?? 0,
                        'database_size' => [
                            'value' => $this->systemService->getDatabaseSize(),
                            'formatted' => $this->formatBytes($this->systemService->getDatabaseSize())
                        ],
                        'uptime' => [
                            'value' => DB::select('show status where variable_name = "Uptime"')[0]->Value ?? 0,
                            'formatted' => $this->formatUptime(DB::select('show status where variable_name = "Uptime"')[0]->Value ?? 0)
                        ]
                    ],
                    'cache_stats' => [
                        'hit_ratio' => Cache::get('cache_hit_ratio', 0),
                        'size' => $this->formatBytes(Cache::get('cache_size', 0))
                    ]
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Error fetching performance metrics: ' . $e->getMessage());
            return response()->json(['error' => 'Error fetching performance metrics'], 500);
        }
    }

    /**
     * الحصول على بيانات قياسات الأداء
     */
    public function getMetricsData(Request $request)
    {
        try {
            $range = $request->input('range', '24h');
            $hours = match($range) {
                '7d' => 168,
                '30d' => 720,
                default => 24
            };

            // Generate sample data points
            $dataPoints = [];
            $now = now();
            
            for ($i = $hours; $i >= 0; $i--) {
                $timestamp = $now->copy()->subHours($i)->timestamp * 1000;
                $dataPoints[] = [
                    'x' => $timestamp,
                    'y' => rand(20, 80)
                ];
            }

            $response = [
                'cpu' => $dataPoints,
                'memory' => array_map(function($point) {
                    return [
                        'x' => $point['x'],
                        'y' => rand(2, 8) * 1024 * 1024 * 1024 // 2-8 GB
                    ];
                }, $dataPoints),
                'responseTime' => array_map(function($point) {
                    return [
                        'x' => $point['x'],
                        'y' => rand(50, 500)
                    ];
                }, $dataPoints),
                'requestRate' => array_map(function($point) {
                    return [
                        'x' => $point['x'],
                        'y' => rand(10, 100)
                    ];
                }, $dataPoints),
                'errorRate' => array_map(function($point) {
                    return [
                        'x' => $point['x'],
                        'y' => rand(0, 5)
                    ];
                }, $dataPoints),
                'stats' => [
                    'cpu' => [
                        'current' => rand(20, 80),
                        'average' => rand(40, 60),
                        'peak' => rand(70, 90)
                    ],
                    'memory' => [
                        'current' => rand(4, 6) * 1024 * 1024 * 1024,
                        'average' => 5 * 1024 * 1024 * 1024,
                        'peak' => 7 * 1024 * 1024 * 1024
                    ],
                    'responseTime' => [
                        'current' => rand(100, 300),
                        'average' => 200,
                        'peak' => 500
                    ],
                    'requestRate' => [
                        'current' => rand(20, 50),
                        'average' => 35,
                        'peak' => 80
                    ]
                ]
            ];

            return response()->json($response);

        } catch (\Exception $e) {
            \Log::error('Error in getMetricsData: ' . $e->getMessage());
            return response()->json([
                'error' => 'حدث خطأ أثناء جلب البيانات',
                'details' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * حساب متوسط وقت الاستجابة
     */
    private function getAverageResponseTime()
    {
        try {
            // جلب أوقات الاستجابة من الـ 5 دقائق الماضية
            $times = Cache::get('response_times', []);
            $currentTime = microtime(true);
            
            // إضافة الوقت الحالي
            $times[] = $currentTime;
            
            // الاحتفاظ فقط بآخر 100 قراءة
            $times = array_slice($times, -100);
            Cache::put('response_times', $times, 300);

            // حساب المتوسط
            $avg = count($times) > 1 ? (end($times) - reset($times)) / count($times) : 0;

            return [
                'average' => round($avg * 1000, 2), // تحويل إلى ميلي ثانية
                'peak' => round(max($times) * 1000, 2),
                'minimum' => round(min($times) * 1000, 2),
                'samples' => count($times)
            ];
        } catch (\Exception $e) {
            Log::warning('Error calculating response time: ' . $e->getMessage());
            return $this->getFallbackResponseTime();
        }
    }

    /**
     * الحصول على استخدام الذاكرة
     */
    private function getMemoryUsage()
    {
        try {
            $current = memory_get_usage(true);
            $peak = memory_get_peak_usage(true);
            $limit = $this->convertToBytes(ini_get('memory_limit'));

            return [
                'current' => [
                    'bytes' => $current,
                    'formatted' => $this->formatBytes($current)
                ],
                'peak' => [
                    'bytes' => $peak,
                    'formatted' => $this->formatBytes($peak)
                ],
                'limit' => [
                    'bytes' => $limit,
                    'formatted' => $this->formatBytes($limit)
                ],
                'usage_percentage' => round(($current / $limit) * 100, 2)
            ];
        } catch (\Exception $e) {
            Log::warning('Error getting memory usage: ' . $e->getMessage());
            return $this->getFallbackMemoryUsage();
        }
    }

    /**
     * الحصول على إحصائيات قاعدة البيانات
     */
    private function getDatabaseStats()
    {
        try {
            $stats = Cache::remember('db_stats', 60, function () {
                $connection = DB::connection();
                $pdo = $connection->getPdo();
                
                // الحصول على إحصائيات MySQL
                $mysqlStats = [
                    'version' => $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION),
                    'connections' => DB::select('SHOW STATUS LIKE "Threads_connected"')[0]->Value ?? 0,
                    'uptime' => DB::select('SHOW STATUS LIKE "Uptime"')[0]->Value ?? 0,
                    'queries_per_second' => DB::select('SHOW STATUS LIKE "Queries"')[0]->Value ?? 0,
                ];

                // حساب حجم قاعدة البيانات
                $dbSize = DB::select('
                    SELECT 
                        SUM(data_length + index_length) AS size
                    FROM information_schema.tables 
                    WHERE table_schema = ?
                    GROUP BY table_schema
                ', [config('database.connections.mysql.database')])[0]->size ?? 0;

                return [
                    'mysql_version' => $mysqlStats['version'],
                    'active_connections' => (int) $mysqlStats['connections'],
                    'uptime' => [
                        'seconds' => (int) $mysqlStats['uptime'],
                        'formatted' => $this->formatUptime($mysqlStats['uptime'])
                    ],
                    'queries_per_second' => (int) ($mysqlStats['queries_per_second'] / $mysqlStats['uptime']),
                    'database_size' => [
                        'bytes' => $dbSize,
                        'formatted' => $this->formatBytes($dbSize)
                    ]
                ];
            });

            return $stats;
        } catch (\Exception $e) {
            Log::error('Error fetching database stats: ' . $e->getMessage());
            return $this->getFallbackDatabaseStats();
        }
    }

    /**
     * الحصول على إحصائيات الكاش
     */
    private function getCacheStats()
    {
        try {
            return [
                'driver' => config('cache.default'),
                'status' => Cache::get('cache_test_key') !== null,
                'size' => Cache::get('cache_size', 0),
                'hit_ratio' => Cache::get('cache_hit_ratio', 0),
                'uptime' => Cache::get('cache_uptime', 0)
            ];
        } catch (\Exception $e) {
            Log::warning('Error getting cache stats: ' . $e->getMessage());
            return $this->getFallbackCacheStats();
        }
    }

    /**
     * الحصول على حمل الخادم
     */
    private function getServerLoad()
    {
        try {
            $load = sys_getloadavg();
            return [
                'load_1' => $load[0],
                'load_5' => $load[1],
                'load_15' => $load[2],
                'cpu_usage' => $this->getCpuUsage(),
                'disk_usage' => $this->getDiskUsage()
            ];
        } catch (\Exception $e) {
            Log::warning('Error getting server load: ' . $e->getMessage());
            return $this->getFallbackServerLoad();
        }
    }

    /**
     * تنسيق البايتات إلى صيغة مقروءة
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        return round($bytes / (1024 ** $pow), $precision) . ' ' . $units[$pow];
    }

    /**
     * تحويل النص إلى بايتات
     */
    private function convertToBytes($value)
    {
        $unit = strtolower(substr($value, -1));
        $value = (int) $value;
        
        switch ($unit) {
            case 'g': $value *= 1024;
            case 'm': $value *= 1024;
            case 'k': $value *= 1024;
        }
        
        return $value;
    }

    /**
     * تنسيق وقت التشغيل
     */
    private function formatUptime($seconds)
    {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        return "{$days}d {$hours}h {$minutes}m";
    }

    /**
     * الحصول على استخدام المعالج
     */
    private function getCpuUsage()
    {
        try {
            // محاولة قراءة استخدام CPU على لينكس
            if (is_readable('/proc/stat')) {
                $stat1 = file('/proc/stat')[0];
                usleep(100000); // انتظار 100ms
                $stat2 = file('/proc/stat')[0];
                
                $info1 = explode(' ', preg_replace('/^cpu\s+/', '', $stat1));
                $info2 = explode(' ', preg_replace('/^cpu\s+/', '', $stat2));
                
                $dif = [];
                for ($i = 0; $i < count($info1); $i++) {
                    $dif[$i] = $info2[$i] - $info1[$i];
                }
                
                $total = array_sum($dif);
                $idle = $dif[3];
                
                return round(100 * (1 - $idle / $total), 2);
            }
            
            // استخدام Windows WMI كبديل
            if (PHP_OS_FAMILY === 'Windows') {
                $cmd = 'wmic cpu get loadpercentage';
                $output = shell_exec($cmd);
                if (preg_match('/^[0-9]+/m', $output, $matches)) {
                    return (float) $matches[0];
                }
            }
            
            return null;
        } catch (\Exception $e) {
            Log::warning('Error getting CPU usage: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * الحصول على استخدام القرص
     */
    private function getDiskUsage()
    {
        try {
            $path = base_path();
            $total = disk_total_space($path);
            $free = disk_free_space($path);
            $used = $total - $free;
            
            return [
                'total' => [
                    'bytes' => $total,
                    'formatted' => $this->formatBytes($total)
                ],
                'used' => [
                    'bytes' => $used,
                    'formatted' => $this->formatBytes($used)
                ],
                'free' => [
                    'bytes' => $free,
                    'formatted' => $this->formatBytes($free)
                ],
                'usage_percentage' => round(($used / $total) * 100, 2)
            ];
        } catch (\Exception $e) {
            Log::warning('Error getting disk usage: ' . $e->getMessage());
            return $this->getFallbackDiskUsage();
        }
    }

    /**
     * القيم الافتراضية في حالة الفشل
     */
    private function getFallbackResponseTime()
    {
        return [
            'average' => 0,
            'peak' => 0,
            'minimum' => 0,
            'samples' => 0
        ];
    }

    private function getFallbackMemoryUsage()
    {
        return [
            'current' => ['bytes' => 0, 'formatted' => '0 B'],
            'peak' => ['bytes' => 0, 'formatted' => '0 B'],
            'limit' => ['bytes' => 0, 'formatted' => '0 B'],
            'usage_percentage' => 0
        ];
    }

    private function getFallbackDatabaseStats()
    {
        return [
            'mysql_version' => 'Unknown',
            'active_connections' => 0,
            'uptime' => ['seconds' => 0, 'formatted' => '0 دقيقة'],
            'queries_per_second' => 0,
            'database_size' => ['bytes' => 0, 'formatted' => '0 B']
        ];
    }

    private function getFallbackCacheStats()
    {
        return [
            'driver' => config('cache.default'),
            'status' => false,
            'size' => 0,
            'hit_ratio' => 0,
            'uptime' => 0
        ];
    }

    private function getFallbackServerLoad()
    {
        return [
            'load_1' => 0,
            'load_5' => 0,
            'load_15' => 0,
            'cpu_usage' => null,
            'disk_usage' => $this->getFallbackDiskUsage()
        ];
    }

    private function getFallbackDiskUsage()
    {
        return [
            'total' => ['bytes' => 0, 'formatted' => '0 B'],
            'used' => ['bytes' => 0, 'formatted' => '0 B'],
            'free' => ['bytes' => 0, 'formatted' => '0 B'],
            'usage_percentage' => 0
        ];
    }
}
