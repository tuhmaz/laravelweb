<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Log;

class ImageUploadController extends Controller
{
    // رفع الصور
    public function upload(Request $request)
    {
        try {
            // التحقق من وجود ملف في الطلب
            if (!$request->hasFile('file')) {
                Log::error('No file uploaded in the request');
                return response()->json(['error' => 'No file uploaded.'], 400);
            }

            // الحصول على الملف المرفوع
            $file = $request->file('file');

            // التحقق من أن الملف صورة صالحة
            if (!$file->isValid() || !in_array($file->extension(), ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff', 'webp'])) {
                Log::error('Invalid image file uploaded', ['extension' => $file->extension()]);
                return response()->json(['error' => 'Invalid image file.'], 400);
            }

            // إنشاء اسم عشوائي للملف بصيغة WebP
            $filename = Str::random(10) . '.webp';

            try {
                // إنشاء نسخة من الصورة باستخدام Intervention Image
                $image = Image::make($file->getRealPath());

                // Get resize dimensions from request or use defaults (650x450)
                $width = $request->input('width', 650);
                $height = $request->input('height', 450);

                // Always resize to maintain consistent dimensions
                Log::info('Resizing image', ['width' => $width, 'height' => $height]);
                $image->resize($width, $height, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });

                // Set quality (1-100)
                $quality = $request->input('quality', 80);

                // Convert to WebP and encode with quality
                $image->encode('webp', $quality);

                // Save the image
                Storage::disk('public')->put('images/' . $filename, (string) $image);

                $url = Storage::url('images/' . $filename);
                Log::info('Image uploaded successfully', [
                    'filename' => $filename,
                    'width' => $width,
                    'height' => $height,
                    'quality' => $quality
                ]);

                return response()->json([
                    'url' => $url,
                    'width' => $image->width(),
                    'height' => $image->height()
                ]);

            } catch (\Exception $e) {
                Log::error('Error processing image with Intervention Image', [
                    'error' => $e->getMessage(),
                    'file' => $file->getClientOriginalName()
                ]);
                return response()->json(['error' => 'Error processing image.'], 500);
            }

        } catch (\Exception $e) {
            Log::error('Unexpected error in image upload', [
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'An unexpected error occurred.'], 500);
        }
    }

    // رفع الملفات العامة (مثل PDF و DOCX)
    public function uploadFile(Request $request)
    {
        // التحقق من وجود ملف في الطلب
        if ($request->hasFile('file')) {
            // الحصول على الملف المرفوع
            $file = $request->file('file');

            // إنشاء اسم عشوائي للملف باستخدام الامتداد الأصلي
            $filename = Str::random(10) . '.' . $file->getClientOriginalExtension();

            // تخزين الملف في مجلد 'public/files' والحصول على مسار التخزين
            $path = $file->storeAs('public/files', $filename);

            // إرجاع الرابط إلى الملف المرفوع
            return response()->json(['url' => Storage::url('files/' . $filename)]);
        }

        // إرجاع خطأ إذا لم يتم العثور على ملف في الطلب
        return response()->json(['error' => 'No file uploaded.'], 400);
    }
}
