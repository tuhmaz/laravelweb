<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class ErrorLogService
{
    public function getRecentErrors()
    {
        // تحقق مما إذا كنا في بيئة التطوير
        $isLocal = in_array(config('app.env'), ['local', 'development']);

        if ($isLocal) {
            // بيانات تجريبية للأخطاء في بيئة التطوير
            $errorTypes = ['Error', 'Warning', 'Notice', 'Deprecated'];
            $messages = [
                'Undefined variable $user',
                'Division by zero',
                'Call to undefined method',
                'File not found',
                'Database connection failed'
            ];

            $recent = [];
            for ($i = 0; $i < 5; $i++) {
                $recent[] = [
                    'id' => uniqid(),
                    'type' => $errorTypes[array_rand($errorTypes)],
                    'message' => $messages[array_rand($messages)],
                    'file' => 'app/Http/Controllers/TestController.php',
                    'line' => rand(10, 100),
                    'timestamp' => now()->subMinutes(rand(1, 60))->format('Y-m-d H:i:s')
                ];
            }

            return [
                'count' => rand(5, 20),
                'trend' => rand(-30, 30),
                'recent' => $recent
            ];
        }

        try {
            // قراءة الأخطاء الحقيقية من ملف السجل
            $logFile = storage_path('logs/laravel.log');
            if (!File::exists($logFile)) {
                return [
                    'count' => 0,
                    'trend' => 0,
                    'recent' => []
                ];
            }

            $logs = array_slice(file($logFile), -100); // آخر 100 سطر
            $errors = [];
            $pattern = '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\].*?(error|warning|notice|deprecated).*?: (.*?) in (.*?):(\d+)/i';

            foreach ($logs as $log) {
                if (preg_match($pattern, $log, $matches)) {
                    $errors[] = [
                        'id' => uniqid(),
                        'timestamp' => $matches[1],
                        'type' => ucfirst(strtolower($matches[2])),
                        'message' => $matches[3],
                        'file' => $matches[4],
                        'line' => $matches[5]
                    ];
                }
            }

            $errors = array_slice($errors, -5); // آخر 5 أخطاء فقط
            $todayCount = count(array_filter($errors, function($error) {
                return strtotime($error['timestamp']) >= strtotime('today');
            }));
            $yesterdayCount = count(array_filter($errors, function($error) {
                return strtotime($error['timestamp']) >= strtotime('yesterday') &&
                       strtotime($error['timestamp']) < strtotime('today');
            }));

            $trend = $yesterdayCount > 0 ? 
                    round((($todayCount - $yesterdayCount) / $yesterdayCount) * 100) : 
                    0;

            return [
                'count' => $todayCount,
                'trend' => $trend,
                'recent' => $errors
            ];

        } catch (\Exception $e) {
            Log::error('Error reading error logs: ' . $e->getMessage());
            // في حالة حدوث خطأ، نعود للبيانات التجريبية
            return $this->getRecentErrors();
        }
    }
}
