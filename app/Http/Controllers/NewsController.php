<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NewsController extends Controller
{
    private array $countries = [
        '1' => 'الأردن',
        '2' => 'السعودية',
        '3' => 'مصر',
        '4' => 'فلسطين'
    ];

    private function getConnection(string $country): string
    {
        return match ($country) {
            'saudi', '2' => 'sa',
            'egypt', '3' => 'eg',
            'palestine', '4' => 'ps',
            'jordan', '1' => 'jo',
            default => throw new NotFoundHttpException(__('Invalid country selected')),
        };
    }

    public function index(Request $request)
    {
        try {
            $country = $request->input('country', '1');
            $connection = $this->getConnection($country);

            $news = News::on($connection)
                ->with('category')
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return view('content.dashboard.news.index', [
                'news' => $news,
                'country' => $country,
                'countries' => $this->countries,
                'currentCountry' => $country
            ]);
        } catch (NotFoundHttpException $e) {
            abort(404, $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Error in news index: ' . $e->getMessage());
            return back()->with('error', __('Error loading news'));
        }
    }

    public function create(Request $request)
    {
        try {
            $country = $request->input('country', '1');
            $connection = $this->getConnection($country);
            
            $categories = Category::on($connection)
                ->where('is_active', true)
                ->get();

            return view('content.dashboard.news.create', [
                'categories' => $categories,
                'country' => $country,
                'countries' => $this->countries
            ]);
        } catch (NotFoundHttpException $e) {
            abort(404, $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            Log::info('Starting news creation', $request->all());
            
            // التحقق من البيانات
            $validated = $request->validate([
                'country' => 'required|string',
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'category_id' => 'required|exists:categories,id',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'meta_description' => 'nullable|string|max:255',
                'keywords' => 'nullable|string|max:255',
                'alt' => 'nullable|string|max:255',
                'is_active' => 'sometimes|boolean',
                'is_featured' => 'sometimes|boolean'
            ]);

            $connection = $this->getConnection($validated['country']);
            Log::info('Using connection: ' . $connection);

            // معالجة الصورة
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('news', 'public');
            } else {
                // استخدام الصورة الافتراضية
                $defaultImage = 'assets/img/illustrations/default_news_image.jpg';
                // نسخ الصورة الافتراضية إلى مجلد التخزين العام
                if (!Storage::disk('public')->exists('news/default_news_image.jpg')) {
                    Storage::disk('public')->copy($defaultImage, 'news/default_news_image.jpg');
                }
                $imagePath = 'news/default_news_image.jpg';
            }

            // إنشاء الخبر
            DB::connection($connection)->beginTransaction();
            
            try {
                $news = new News();
                $news->setConnection($connection);
                $news->title = $validated['title'];
                $news->slug = Str::slug($validated['title']) . '-' . time();
                $news->content = $validated['content'];
                $news->category_id = $validated['category_id'];
                $news->image = $imagePath;
                $news->meta_description = $validated['meta_description'] ?? null;
                $news->keywords = $validated['keywords'] ?? null;
                // استخدام العنوان كقيمة افتراضية لـ alt إذا لم يتم تحديده
                $news->alt = $validated['alt'] ?: $validated['title'];
                $news->is_active = $request->boolean('is_active', true);
                $news->is_featured = $request->boolean('is_featured', false);
                $news->views = 0;
                $news->country = $validated['country'];
                $news->author_id = auth()->id(); // إضافة معرف المستخدم الحالي
                $news->save();

                DB::connection($connection)->commit();
                Log::info('News created successfully', ['news_id' => $news->id]);

                $request->session()->forget('_old_input');
                $this->clearDashboardCache('news');
                
                return redirect()
                    ->route('dashboard.news.index', ['country' => $validated['country']])
                    ->with('success', __('News created successfully'));

            } catch (\Exception $e) {
                DB::connection($connection)->rollBack();
                if (isset($imagePath)) {
                    Storage::disk('public')->delete($imagePath);
                }
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Error creating news: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->withInput()
                ->with('error', __('Error creating news: ') . $e->getMessage());
        }
    }

    public function edit($id, Request $request)
    {
        try {
            $country = $request->input('country', '1');
            $connection = $this->getConnection($country);

            $news = News::on($connection)->findOrFail($id);
            $categories = Category::on($connection)
                ->where('is_active', true)
                ->get();

            return view('content.dashboard.news.edit', [
                'news' => $news,
                'categories' => $categories,
                'country' => $country,
                'countries' => $this->countries
            ]);
        } catch (NotFoundHttpException $e) {
            abort(404, $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Error editing news: ' . $e->getMessage());
            abort(404, __('News not found'));
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'country' => ['required', 'string'],
                'category_id' => ['required', 'exists:' . $this->getConnection($request->input('country')) . '.categories,id'],
                'title' => ['required', 'string', 'max:255'],
                'content' => ['required', 'string'],
                'meta_description' => ['required', 'string', 'max:255'],
                'keywords' => ['required', 'string', 'max:255'],
                'alt' => ['nullable', 'string', 'max:255'],
                'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            ]);

            $connection = $this->getConnection($validated['country']);
            $news = News::on($connection)->findOrFail($id);

            DB::connection($connection)->beginTransaction();

            try {
                // معالجة الصورة الجديدة إذا تم تحميلها
                if ($request->hasFile('image')) {
                    // حذف الصورة القديمة إذا لم تكن الصورة الافتراضية
                    if ($news->image && $news->image !== 'news/default_news_image.jpg') {
                        Storage::disk('public')->delete($news->image);
                    }
                    $imagePath = $request->file('image')->store('news', 'public');
                    $news->image = $imagePath;
                }

                $news->title = $validated['title'];
                $news->slug = Str::slug($validated['title']) . '-' . time();
                $news->content = $validated['content'];
                $news->category_id = $validated['category_id'];
                $news->meta_description = $validated['meta_description'];
                $news->keywords = $validated['keywords'];
                // استخدام العنوان كقيمة افتراضية لـ alt إذا لم يتم تحديده
                $news->alt = $validated['alt'] ?: $validated['title'];
                $news->is_active = $request->boolean('is_active', true);
                $news->is_featured = $request->boolean('is_featured', false);
                $news->country = $validated['country'];
                $news->author_id = auth()->id(); // إضافة معرف المستخدم الحالي
                $news->save();

                DB::connection($connection)->commit();

                return redirect()
                    ->route('dashboard.news.index', ['country' => $validated['country']])
                    ->with('success', __('News updated successfully'));

            } catch (\Exception $e) {
                DB::connection($connection)->rollBack();
                if (isset($imagePath)) {
                    Storage::disk('public')->delete($imagePath);
                }
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Error updating news: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->withInput()
                ->with('error', __('Error updating news: ') . $e->getMessage());
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $country = $request->input('country', '1');
            $connection = $this->getConnection($country);

            $news = News::on($connection)->findOrFail($id);

            DB::connection($connection)->beginTransaction();

            try {
                // حذف الصورة
                if ($news->image) {
                    Storage::disk('public')->delete($news->image);
                }

                $news->delete();

                DB::connection($connection)->commit();

                return redirect()
                    ->route('dashboard.news.index', ['country' => $country])
                    ->with('success', __('News deleted successfully'));

            } catch (\Exception $e) {
                DB::connection($connection)->rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Error deleting news: ' . $e->getMessage());
            return back()->with('error', __('Error deleting news'));
        }
    }

    /**
     * Toggle the status of the specified news.
     *
     * @param \App\Models\News $news
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus(News $news)
    {
        try {
            $connection = $this->getConnection(request('country'));
            DB::connection($connection)->beginTransaction();

            $news->is_active = !$news->is_active;
            $news->save();

            DB::connection($connection)->commit();

            return response()->json([
                'success' => true,
                'message' => __('Status updated successfully')
            ]);
        } catch (\Exception $e) {
            DB::connection($connection)->rollBack();
            return response()->json([
                'success' => false,
                'message' => __('Failed to update status')
            ], 500);
        }
    }

    /**
     * Toggle the featured status of the specified news.
     *
     * @param \App\Models\News $news
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleFeatured(News $news)
    {
        try {
            $connection = $this->getConnection(request('country'));
            DB::connection($connection)->beginTransaction();

            $news->is_featured = !$news->is_featured;
            $news->save();

            DB::connection($connection)->commit();

            return response()->json([
                'success' => true,
                'message' => __('Featured status updated successfully')
            ]);
        } catch (\Exception $e) {
            DB::connection($connection)->rollBack();
            return response()->json([
                'success' => false,
                'message' => __('Failed to update featured status')
            ], 500);
        }
    }
}
