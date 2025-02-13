<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\User;
use App\Models\Category;
use Illuminate\Http\Request;

class FrontendNewsController extends Controller
{
  public function setDatabase(Request $request)
  {
      $request->validate([
          'database' => 'required|string|in:jo,sa,eg,ps'
      ]);

      $request->session()->put('database', $request->input('database'));

      return redirect()->back();
  }

  private function getConnection(Request $request)
  {
      return $request->session()->get('database', 'jo');
  }

  public function index(Request $request)
  {
      $database = $this->getConnection($request);

      $categories = Category::on($database)->select('id', 'name', 'slug')->get();

      $query = News::on($database)->with('category');

      if ($request->has('category') && !empty($request->input('category'))) {
          $categorySlug = $request->input('category');

          $category = Category::on($database)->where('slug', $categorySlug)->first();

          if ($category) {
              $query->where('category_id', $category->id);
          } else {

              $query->whereNull('category_id');
          }
      }

      $news = $query->paginate(10);

      return view('content.frontend.news.index', compact('news', 'categories', 'database'));
  }


  public function show(Request $request, $database, string $id)
  {
      // الاتصال بقاعدة البيانات الفرعية
      $connection = $this->getConnection($request);

      // جلب الخبر من قاعدة البيانات الفرعية مع العلاقة الخاصة بالفئة فقط
      $news = News::on($connection)->with('category')->findOrFail($id);

      // جلب المؤلف من قاعدة البيانات الرئيسية فقط
      $news->author = User::on('jo')->find($news->author_id);

      // معالجة الكلمات الدلالية فقط إذا كانت موجودة
      if ($news->description && $news->keywords) {
          $news->description = $this->replaceKeywordsWithLinks($news->description, $news->keywords);
          $news->description = $this->createInternalLinks($news->description, $news->keywords);
      }

      return view('content.frontend.news.show', compact('news', 'database'));
  }

    private function replaceKeywordsWithLinks($description, $keywords)
    {
        if (empty($keywords)) {
            return $description;
        }

        if (is_string($keywords)) {
            $keywordsArray = array_filter(array_map('trim', explode(',', $keywords)));
        } else {
            return $description;
        }

        foreach ($keywordsArray as $keyword) {
            if (!empty($keyword)) {
                $database = session('database', 'jo');
                $keywordLink = route('content.frontend.news.index', ['database' => $database, 'keyword' => $keyword]);
                $description = preg_replace('/\b' . preg_quote($keyword, '/') . '\b/ui', '<a href="' . $keywordLink . '">' . $keyword . '</a>', $description);
            }
        }

        return $description;
    }

    private function createInternalLinks($description, $keywords)
    {
        if (empty($keywords)) {
            return $description;
        }

        if (is_string($keywords)) {
            $keywordsArray = array_filter(array_map('trim', explode(',', $keywords)));
        } else {
            return $description;
        }

        foreach ($keywordsArray as $keyword) {
            if (!empty($keyword)) {
                $database = session('database', 'jo');
                $url = route('content.frontend.news.index', ['database' => $database, 'keyword' => $keyword]);
                $description = str_ireplace($keyword, '<a href="' . $url . '">' . $keyword . '</a>', $description);
            }
        }

        return $description;
    }

    public function category($translatedCategory, Request $request)
    {
        $connection = $this->getConnection($request);

        $category = Category::on($connection)->where('name', $translatedCategory)->first();

        if (!$category) {
            abort(404);
        }

        $categories = Category::on($connection)->pluck('name', 'id');

        $news = News::on($connection)->where('category_id', $category->id)->paginate(10);

        return view('frontend.news.category', compact('news', 'categories', 'category'));
    }

    public function filterNewsByCategory(Request $request, $database)
    {
        $connection = $this->getConnection($request);

        $categorySlug = $request->input('category');

        $category = Category::on($connection)->where('slug', $categorySlug)->first();

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $news = News::on($connection)
            ->where('category_id', $category->id)
            ->paginate(10);

        if ($news->isEmpty()) {
            return response()->json(['message' => 'No news found for the selected category'], 404);
        }

        return view('content.frontend.news.partials.news-items', compact('news'))->render();
    }


}
