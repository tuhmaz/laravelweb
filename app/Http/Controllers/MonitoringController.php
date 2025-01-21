<?php

namespace App\Http\Controllers;

use App\Services\VisitorService;
use App\Services\SystemService;
use App\Services\ErrorLogService;
use Illuminate\Support\Facades\Log;

class MonitoringController extends Controller
{
    protected $visitorService;
    protected $systemService;
    protected $errorLogService;

    public function __construct(VisitorService $visitorService, SystemService $systemService, ErrorLogService $errorLogService)
    {
        $this->visitorService = $visitorService;
        $this->systemService = $systemService;
        $this->errorLogService = $errorLogService;
    }

    public function index()
    {
        return redirect()->route('dashboard.monitoring.monitorboard');
    }

    public function monitorboard()
    {
        return view('content.dashboard.monitoring.index');
    }

    public function getStats()
    {
        try {
            Log::info('بدء جمع إحصائيات المراقبة');

            $stats = [
                'visitors' => $this->visitorService->getVisitorStats(),
                'system' => $this->systemService->getSystemStats(),
                'locations' => $this->visitorService->getVisitorLocations(),
                'errors' => $this->errorLogService->getRecentErrors(),
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ];

            Log::info('تم جمع جميع الإحصائيات بنجاح', ['stats' => $stats]);

            return response()->json($stats);

        } catch (\Exception $e) {
            Log::error('خطأ في جمع الإحصائيات', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json(['error' => 'حدث خطأ أثناء جمع الإحصائيات'], 500);
        }
    }
}
