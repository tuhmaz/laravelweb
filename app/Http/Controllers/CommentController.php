<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\News;
use App\Models\Article;
use Illuminate\Http\Request;

class CommentController extends Controller
{
  public function store(Request $request)
  {
    $request->validate([
      'body' => 'required',
      'commentable_id' => 'required',
      'commentable_type' => 'required',
    ]);

    // استخدام قاعدة البيانات من الجلسة
    $database = session('database', 'jo');

    Comment::create([
      'body' => $request->body,
      'user_id' => auth()->id(),
      'commentable_id' => $request->commentable_id,
      'commentable_type' => $request->commentable_type,
      'database' => $database, // إضافة قاعدة البيانات من الجلسة
    ]);

    return redirect()->back()->with('success', 'تم إضافة التعليق بنجاح!');
  }

  public function destroy($id)
  {
    try {
      $comment = Comment::findOrFail($id);

      // التحقق من أن المستخدم هو صاحب التعليق أو لديه صلاحية الحذف
      if (auth()->id() !== $comment->user_id && !auth()->user()->can('delete comments')) {
        return redirect()->back()->with('error', 'غير مصرح لك بحذف هذا التعليق');
      }

      $comment->delete();
      return redirect()->back()->with('success', 'تم حذف التعليق بنجاح');

    } catch (\Exception $e) {
      return redirect()->back()->with('error', 'فشل في حذف التعليق');
    }
  }
}
