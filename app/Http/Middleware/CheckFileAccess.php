<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\File;
use Symfony\Component\HttpFoundation\Response;

class CheckFileAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // الحصول على معرف الملف من المسار
            $fileId = $request->route('file') ?? $request->route('id');
            
            if (!$fileId) {
                return $this->return404();
            }

            // الحصول على الاتصال الصحيح بقاعدة البيانات
            $country = $request->input('country', 'jordan');
            $connection = match ($country) {
                'saudi' => 'sa',
                'egypt' => 'eg',
                'palestine' => 'ps',
                default => 'jo',
            };

            // البحث عن الملف
            $file = File::on($connection)->find($fileId);
            
            if (!$file) {
                return $this->return404();
            }

            // التحقق من أن الملف موجود فعلياً
            $filePath = public_path('storage/' . $file->file_path);
            if (!file_exists($filePath)) {
                return $this->return404();
            }

            return $next($request);

        } catch (\Exception $e) {
            // تسجيل الخطأ في السجلات
            \Log::error('File access error: ' . $e->getMessage());
            return $this->return404();
        }
    }

    /**
     * إرجاع صفحة 404
     */
    private function return404()
    {
        if (request()->expectsJson()) {
            abort(404, 'File not found');
        }
        
        return response()->view('errors.404', [], 404);
    }
}
