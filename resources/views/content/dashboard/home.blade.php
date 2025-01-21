@php
use Illuminate\Support\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Str;


// إنشاء التواريخ
$now = Carbon::now();
$period = CarbonPeriod::create($now->startOfMonth(), $now->endOfMonth());
$dates = collect($period)->map(fn ($date) => $date->format('Y-m-d'));
$datesFormatted = $dates->map(fn ($date) => Carbon::parse($date)->format('M d'));

// البيانات
$models = [
    'articles' => \App\Models\Article::class,
    'news' => \App\Models\News::class,
    'users' => \App\Models\User::class,
];

$dataArrays = [];
foreach ($models as $key => $model) {
    $data = $model::selectRaw('DATE(created_at) as date, COUNT(*) as count')
        ->whereBetween('created_at', [$now->startOfMonth(), $now->endOfMonth()])
        ->groupBy('date')
        ->get()
        ->pluck('count', 'date');
    $dataArrays[$key] = $dates->mapWithKeys(fn ($date) => [$date => $data->get($date, 0)]);
}

$articlesData = array_values($dataArrays['articles']->toArray());
$newsData = array_values($dataArrays['news']->toArray());
$usersData = array_values($dataArrays['users']->toArray());

$counts = [
    'news' => \App\Models\News::count(),
    'articles' => \App\Models\Article::count(),
    'users' => \App\Models\User::count(),
];

// إحصائيات إضافية
$activeUsers = \App\Models\User::where('last_seen', '>=', now()->subMinutes(5))->count();
$totalVisits = \App\Models\PageVisit::count();
$todayVisits = \App\Models\PageVisit::whereDate('created_at', today())->count();
$onlineUsers = \App\Models\User::where('last_seen', '>=', now()->subMinutes(5))
    ->with('roles')
    ->take(6)
    ->get();

// أحدث الأخبار والمقالات
$latestContent = collect([])
    ->merge(\App\Models\News::latest()->take(3)->get())
    ->merge(\App\Models\Article::latest()->take(3)->get())
    ->sortByDesc('created_at')
    ->take(5);

// إحصائيات الأمان
$securityStats = [
    'blocked_ips' => \App\Models\BlockedIp::count(),
    'security_logs' => \App\Models\SecurityLog::whereDate('created_at', today())->count(),
];
@endphp

@extends('layouts/contentNavbarLayout')

@section('title', __('Dashboard'))

@section('vendor-style')
@vite([
    'resources/assets/vendor/libs/apex-charts/apex-charts.scss',
    'resources/assets/vendor/libs/swiper/swiper.scss',
    'resources/assets/vendor/libs/animate-css/animate.scss',
])
@endsection

@section('page-style')
@vite(['resources/assets/css/dashboard.css'])
<style>
    .welcome-card {
        background: linear-gradient(45deg, #6b21a8, #3730a3);
        border: none;
        border-radius: 15px;
    }
    .stats-card {
        transition: transform 0.3s ease;
        border-radius: 12px;
        border: none;
        box-shadow: 0 4px 6px rgba(0,0,0,0.07);
    }
    .stats-card:hover {
        transform: translateY(-5px);
    }
    .stats-icon {
        width: 45px;
        height: 45px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        background: rgba(var(--bs-primary-rgb), 0.1);
    }
    .quick-action-card {
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .quick-action-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
    }
    .content-card {
        border-radius: 12px;
        border: none;
        box-shadow: 0 4px 6px rgba(0,0,0,0.07);
    }
    .security-alert {
        border-left: 4px solid var(--bs-primary);
    }
</style>
@endsection

@section('content')
<!-- Row 1: Welcome & Quick Stats -->
<div class="row mb-4">
    <div class="col-lg-8 mb-4 mb-lg-0">
        <div class="card welcome-card">
            <div class="d-flex align-items-center row g-0">
                <div class="col-sm-7">
                    <div class="card-body">
                        <h4 class="card-title text-white mb-3">{{ __('Welcome back') }}, {{ auth()->user()->name }} 👋</h4>
                        <p class="mb-4 text-white opacity-75">{{ __('Here\'s what\'s happening with your website today.') }}</p>
                        <div class="d-flex gap-3">
                            <div class="bg-black bg-opacity-10 p-3 rounded-3">
                                <h5 class="text-white mb-1">{{ $todayVisits }}</h5>
                                <small class="text-white opacity-75">{{ __('Today\'s Visits') }}</small>
                            </div>
                            <div class="bg-black bg-opacity-10 p-3 rounded-3">
                                <h5 class="text-white mb-1">{{ $activeUsers }}</h5>
                                <small class="text-white opacity-75">{{ __('Active Users') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-5 text-center">
                    <img src="{{ Auth::user() && Auth::user()->profile_photo_path ? asset('storage/' . Auth::user()->profile_photo_path) : asset($defaultAvatar) }}" 
                         alt="{{ __('Avatar') }}" 
                         class="rounded-circle" 
                         style="width: 180px; height: 180px; object-fit: cover;">
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="row g-3">
            <div class="col-6">
                <div class="card stats-card h-100">
                    <div class="card-body">
                        <div class="stats-icon mb-3 text-primary">
                            <i class="ti ti-article fs-3"></i>
                        </div>
                        <h5 class="mb-1">{{ $counts['articles'] }}</h5>
                        <p class="text-muted mb-0">{{ __('Articles') }}</p>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card stats-card h-100">
                    <div class="card-body">
                        <div class="stats-icon mb-3 text-success">
                            <i class="ti ti-news fs-3"></i>
                        </div>
                        <h5 class="mb-1">{{ $counts['news'] }}</h5>
                        <p class="text-muted mb-0">{{ __('News') }}</p>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stats-icon text-warning mb-3">
                                    <i class="ti ti-users fs-3"></i>
                                </div>
                                <h5 class="mb-1">{{ $counts['users'] }}</h5>
                                <p class="text-muted mb-0">{{ __('Total Users') }}</p>
                            </div>
                            <div class="text-end">
                                <small class="text-success d-block mb-1">
                                    <i class="ti ti-trending-up"></i> +{{ $activeUsers }}
                                </small>
                                <small class="text-muted">{{ __('Active Now') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Row 2: Charts & Activity -->
<div class="row mb-4">
    <div class="col-lg-8 mb-4 mb-lg-0">
        <div class="card content-card">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0">{{ __('Content Analytics') }}</h5>
            </div>
            <div class="card-body">
                <div id="contentChart" style="height: 350px;"></div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card content-card">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0">{{ __('Online Users') }}</h5>
            </div>
            <div class="card-body">
                <div class="d-flex flex-column gap-3">
                    @forelse($onlineUsers as $user)
                    <div class="d-flex align-items-center gap-3">
                        <img src="{{ $user->profile_photo_path ? asset('storage/' . $user->profile_photo_path) : asset($defaultAvatar) }}" 
                             class="user-avatar"
                             alt="{{ $user->name }}">
                        <div>
                            <h6 class="mb-0">{{ $user->name }}</h6>
                            <small class="text-muted">
                                {{ $user->roles->first()?->name ?? __('User') }}
                            </small>
                        </div>
                        <div class="ms-auto">
                            <span class="badge bg-success">{{ __('Online') }}</span>
                        </div>
                    </div>
                    @empty
                    <p class="text-muted text-center mb-0">{{ __('No users online') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Row 3: Latest Content & Security -->
<div class="row">
    <div class="col-lg-8 mb-4 mb-lg-0">
        <div class="card content-card">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0">{{ __('Latest Content') }}</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>{{ __('Title') }}</th>
                                <th>{{ __('Type') }}</th>
                                 
                                <th>{{ __('Date') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($latestContent as $content)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="ti {{ get_class($content) === 'App\Models\News' ? 'ti-news' : 'ti-article' }} text-primary"></i>
                                        {{ Str::limit($content->title, 40) }}
                                    </div>
                                </td>
                                <td>{{ get_class($content) === 'App\Models\News' ? __('News') : __('Article') }}</td>
                                 
                                <td>{{ $content->created_at->diffForHumans() }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center">{{ __('No content available') }}</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card content-card">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0">{{ __('Security Overview') }}</h5>
            </div>
            <div class="card-body">
                <div class="d-flex flex-column gap-4">
                    <div class="security-alert p-3 bg-light rounded">
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <div class="stats-icon bg-primary text-white">
                                <i class="ti ti-shield-lock fs-4"></i>
                            </div>
                            <h6 class="mb-0">{{ __('Security Status') }}</h6>
                        </div>
                        <div class="d-flex justify-content-between mt-3">
                            <div>
                                <small class="text-muted d-block">{{ __('Blocked IPs') }}</small>
                                <h6 class="mb-0">{{ $securityStats['blocked_ips'] }}</h6>
                            </div>
                            <div>
                                <small class="text-muted d-block">{{ __('Today\'s Logs') }}</small>
                                <h6 class="mb-0">{{ $securityStats['security_logs'] }}</h6>
                            </div>
                        </div>
                    </div>
                    
                    <div class="quick-actions">
                        <h6 class="mb-3">{{ __('Quick Actions') }}</h6>
                        <div class="d-grid gap-2">
                            <a href="{{ route('dashboard.security.index') }}" class="btn btn-outline-primary">
                                <i class="ti ti-shield me-2"></i>{{ __('Security Center') }}
                            </a>
                            <a href="{{ route('dashboard.performance.index') }}" class="btn btn-outline-success">
                                <i class="ti ti-chart-bar me-2"></i>{{ __('Performance Report') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('vendor-script')
@vite([
    'resources/assets/vendor/libs/apex-charts/apexcharts.js',
    'resources/assets/vendor/libs/jquery/jquery.js'
])
 
@endsection

@section('page-script')
@vite([
    'resources/assets/js/dashboards-analytics.js',
    'resources/assets/js/cards-statistics.js'
])


@endsection
