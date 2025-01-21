<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Event;
use Illuminate\Support\Facades\DB;

class CalendarController extends Controller
{
    public function index()
    {
        // الحصول على قائمة قواعد البيانات المتاحة
        $databases = config('database.connections');
        $availableDatabases = collect($databases)
            ->filter(function ($config, $name) {
                return in_array($name, [
                    'mysql', 'jo', 'sa', 'ae', 'bh', 'kw', 'om', 'qa',
                    'eg', // مصر
                    'ps'  // فلسطين
                ]); // قائمة قواعد البيانات المسموح بها
            })
            ->keys()
            ->toArray();

        return view('content.dashboard.calendar.index', [
            'databases' => $availableDatabases,
            'currentDatabase' => session('database', config('database.default'))
        ]);
    }

    public function getEvents(Request $request)
    {
        try {
            // الحصول على قاعدة البيانات من الطلب أو الجلسة
            $database = $request->input('database', session('database', config('database.default')));
            
            // تسجيل معلومات التصحيح
            \Log::info('Current Database Connection:', ['database' => $database]);
            
            // تغيير اتصال قاعدة البيانات
            DB::setDefaultConnection($database);
            
            // الحصول على الأحداث من قاعدة البيانات المحددة
            $events = Event::on($database)
                ->get()
                ->map(function ($event) use ($database) {
                    return [
                        'id' => $event->id,
                        'title' => $event->title,
                        'start' => Carbon::parse($event->event_date)->format('Y-m-d'),
                        'end' => Carbon::parse($event->event_date)->addHours(1)->format('Y-m-d H:i:s'),
                        'allDay' => true,
                        'extendedProps' => [
                            'description' => $event->description ?? '',
                            'database' => $database
                        ]
                    ];
                });

            // تسجيل معلومات الأحداث للتصحيح
            \Log::info('Events Query:', [
                'database' => $database,
                'count' => $events->count(),
                'events' => $events
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $events
            ]);

        } catch (\Exception $e) {
            \Log::error('Calendar Events Error:', [
                'message' => $e->getMessage(),
                'database' => $database ?? 'unknown'
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch calendar events'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'event_date' => 'required|date',
                'eventDatabase' => 'required|string' // تغيير اسم الحقل ليتطابق مع النموذج
            ]);

            // استخدام قاعدة البيانات المحددة
            $database = $validated['eventDatabase'];
            
            // تسجيل معلومات التصحيح
            \Log::info('Creating event in database:', [
                'database' => $database,
                'event_data' => $validated
            ]);

            // تغيير اتصال قاعدة البيانات
            DB::setDefaultConnection($database);

            // إعداد بيانات الحدث
            $eventData = [
                'title' => $validated['title'],
                'description' => $validated['description'],
                'event_date' => $validated['event_date']
            ];

            $event = Event::create($eventData);

            \Log::info('Event created successfully:', [
                'database' => $database,
                'event_id' => $event->id
            ]);

            return response()->json([
                'success' => true,
                'event' => array_merge($event->toArray(), ['database' => $database])
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Calendar Event Validation Error:', [
                'errors' => $e->errors()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'بيانات الحدث غير صحيحة',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Calendar Event Creation Error:', [
                'message' => $e->getMessage(),
                'database' => $database ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء الحدث'
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_date' => 'required|date',
            'database' => 'required|string' // إضافة التحقق من قاعدة البيانات
        ]);

        try {
            // استخدام قاعدة البيانات المحددة
            $database = $validated['database'];
            unset($validated['database']); // إزالة حقل قاعدة البيانات من البيانات المصادق عليها

            $event = Event::on($database)->findOrFail($id);
            $event->update($validated);

            return response()->json([
                'success' => true,
                'event' => array_merge($event->toArray(), ['database' => $database])
            ]);
        } catch (\Exception $e) {
            \Log::error('Calendar Event Update Error:', [
                'message' => $e->getMessage(),
                'database' => $database ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update event'
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $database = $request->input('database');
            $event = Event::on($database)->findOrFail($id);
            $event->delete();

            return response()->json([
                'success' => true
            ]);
        } catch (\Exception $e) {
            \Log::error('Calendar Event Deletion Error:', [
                'message' => $e->getMessage(),
                'database' => $database ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete event'
            ], 500);
        }
    }
}
