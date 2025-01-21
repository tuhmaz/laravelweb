<?php

namespace App\Services;

use App\Models\Visitor;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class VisitorService
{
    public function getVisitorStats()
    {
        // تحقق مما إذا كنا في بيئة التطوير
        $isLocal = in_array(config('app.env'), ['local', 'development']);

        if ($isLocal) {
            // بيانات تجريبية للزوار في بيئة التطوير
            $currentHour = now()->format('H');
            $history = [];
            
            // إنشاء بيانات تجريبية للـ 24 ساعة الماضية
            for ($i = 23; $i >= 0; $i--) {
                $hour = (int)$currentHour - $i;
                if ($hour < 0) $hour += 24;
                
                $history[] = [
                    'timestamp' => now()->subHours($i)->format('Y-m-d H:i:s'),
                    'count' => rand(10, 100)
                ];
            }

            return [
                'current' => rand(20, 50),
                'total_today' => rand(100, 500),
                'change' => rand(-20, 20),
                'history' => $history
            ];
        }

        try {
            // البيانات الحقيقية للإنتاج
            $currentVisitors = Visitor::where('last_activity', '>=', now()->subMinutes(5))->count();
            $totalToday = Visitor::whereDate('created_at', today())->count();
            $lastHour = Visitor::where('created_at', '>=', now()->subHour())->count();
            $previousHour = Visitor::where('created_at', '>=', now()->subHours(2))
                                 ->where('created_at', '<', now()->subHour())
                                 ->count();
            
            $change = $previousHour > 0 ? 
                     round((($lastHour - $previousHour) / $previousHour) * 100) : 
                     0;

            $history = DB::table('visitors')
                ->select(DB::raw('DATE_FORMAT(created_at, "%Y-%m-%d %H:00:00") as timestamp'), 
                        DB::raw('COUNT(*) as count'))
                ->where('created_at', '>=', now()->subDay())
                ->groupBy('timestamp')
                ->orderBy('timestamp')
                ->get();

            return [
                'current' => $currentVisitors,
                'total_today' => $totalToday,
                'change' => $change,
                'history' => $history
            ];

        } catch (\Exception $e) {
            // في حالة حدوث خطأ، نعود للبيانات التجريبية
            return $this->getVisitorStats();
        }
    }

    public function getVisitorLocations()
    {
        // تحقق مما إذا كنا في بيئة التطوير
        $isLocal = in_array(config('app.env'), ['local', 'development']);

        if ($isLocal) {
            // بيانات تجريبية للمواقع
            return [
                ['lat' => 24.7136, 'lng' => 46.6753, 'country' => 'Saudi Arabia', 'count' => rand(10, 50)],
                ['lat' => 30.0444, 'lng' => 31.2357, 'country' => 'Egypt', 'count' => rand(10, 50)],
                ['lat' => 25.2048, 'lng' => 55.2708, 'country' => 'UAE', 'count' => rand(10, 50)],
                ['lat' => 33.8869, 'lng' => 9.5375, 'country' => 'Tunisia', 'count' => rand(10, 50)],
                ['lat' => 31.9522, 'lng' => 35.2332, 'country' => 'Jordan', 'count' => rand(10, 50)]
            ];
        }

        try {
            // البيانات الحقيقية للإنتاج
            return DB::table('visitors')
                ->select('country', DB::raw('COUNT(*) as count'), 'latitude as lat', 'longitude as lng')
                ->whereNotNull('country')
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->where('created_at', '>=', now()->subDay())
                ->groupBy('country', 'latitude', 'longitude')
                ->get()
                ->toArray();

        } catch (\Exception $e) {
            // في حالة حدوث خطأ، نعود للبيانات التجريبية
            return $this->getVisitorLocations();
        }
    }
}
