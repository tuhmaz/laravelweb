<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Article;
use App\Models\News;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    public function index()
    {
        $defaultAvatar = 'assets/img/avatars/default.png';

        // Get statistics for the last 7 days
        $dates = collect(range(6, 0))->map(function ($days) {
            return Carbon::now()->subDays($days)->format('Y-m-d');
        });

        // Articles statistics
        $articlesData = $dates->map(function ($date) {
            return Article::whereDate('created_at', $date)->count();
        })->toArray();

        // News statistics
        $newsData = $dates->map(function ($date) {
            return News::whereDate('created_at', $date)->count();
        })->toArray();

        // Users statistics
        $usersData = $dates->map(function ($date) {
            return User::whereDate('created_at', $date)->count();
        })->toArray();

        // Get recent articles instead of top articles since we don't have views
        $recentArticles = Article::latest()
            ->take(5)
            ->get();

        // Get recent activities
        $recentActivities = Activity::with('causer')
            ->latest()
            ->take(5)
            ->get();

        // Get online users
        $onlineUsers = User::where('is_online', true)
            ->orderBy('last_seen', 'desc')
            ->take(5)
            ->get();

        // Calculate percentages
        $totalArticles = Article::count();
        $totalNews = News::count();
        $totalUsers = User::count();
        $onlineUsersCount = User::where('is_online', true)->count();

        $articlesTrend = $this->calculateTrend(Article::class);
        $newsTrend = $this->calculateTrend(News::class);
        $usersTrend = $this->calculateTrend(User::class);

        return view('content.dashboard.home', compact(
            'defaultAvatar',
            'dates',
            'articlesData',
            'newsData',
            'usersData',
            'recentArticles',
            'recentActivities',
            'onlineUsers',
            'totalArticles',
            'totalNews',
            'totalUsers',
            'onlineUsersCount',
            'articlesTrend',
            'newsTrend',
            'usersTrend'
        ));
    }

    private function calculateTrend($model)
    {
        $today = $model::whereDate('created_at', Carbon::today())->count();
        $yesterday = $model::whereDate('created_at', Carbon::yesterday())->count();
        
        if ($yesterday == 0) {
            return ['percentage' => 100, 'trend' => 'up'];
        }
        
        $difference = $today - $yesterday;
        $percentage = round(($difference / max(1, $yesterday)) * 100);
        
        return [
            'percentage' => abs($percentage),
            'trend' => $difference >= 0 ? 'up' : 'down'
        ];
    }
}
