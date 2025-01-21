@php
$database = session('database', 'jo');
$filterUrl = route('content.frontend.news.filter', ['database' => $database]);
use Illuminate\Support\Str;
@endphp


@extends('layouts/layoutFront')

@section('title', __('news_title'))

@section('content')
<section class="section-py first-section-pt help-center-header position-relative overflow-hidden" style="background: linear-gradient(226deg, #202c45 0%, #286aad 100%);">
    <!-- Background Pattern -->
    <div class="position-absolute w-100 h-100" style="background: linear-gradient(45deg, rgba(40, 106, 173, 0.1), transparent); top: 0; left: 0;"></div>

    <!-- Animated Shapes -->
    <div class="position-absolute" style="width: 300px; height: 300px; background: radial-gradient(circle, rgba(40, 106, 173, 0.1) 0%, transparent 70%); top: -150px; right: -150px; border-radius: 50%;"></div>
    <div class="position-absolute" style="width: 200px; height: 200px; background: radial-gradient(circle, rgba(40, 106, 173, 0.1) 0%, transparent 70%); bottom: -100px; left: -100px; border-radius: 50%;"></div>

    <div class="container position-relative">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8 text-center">

                <!-- Main Title with Animation -->
                <h2 class="display-6 text-white mb-4 animate__animated animate__fadeInDown" style="text-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                 
                </h2>
                @guest
                <!-- Call to Action Buttons -->
                <div class="d-flex justify-content-center gap-3 animate__animated animate__fadeInUp animate__delay-1s">
                    <a href="{{ route('login') }}" class="btn btn-primary btn-lg" style="background: linear-gradient(45deg, #3498db, #2980b9); border: none; box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);">
                        <i class="ti ti-user-plus me-2"></i>{{ __('Get Started') }}
                    </a>
                    <a href="#features" class="btn btn-outline-light btn-lg">
                        <i class="ti ti-info-circle me-2"></i>{{ __('Learn More') }}
                    </a>
                </div>
                @endguest
            </div>
        </div>
    </div>

    <!-- Wave Shape Divider -->
    <div class="position-absolute bottom-0 start-0 w-100 overflow-hidden" style="height: 60px;">
        <svg viewBox="0 0 1200 120" preserveAspectRatio="none" style="width: 100%; height: 60px; transform: rotate(180deg);">
            <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" style="fill: #ffffff;"></path>
        </svg>
    </div>
</section>

<div class="container px-4 mt-2">
  <ol class="breadcrumb breadcrumb-style2 mx-auto" aria-label="breadcrumbs">
    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="ti ti-home-check"></i>{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('content.frontend.news.index', ['database' => $database]) }}">{{ __('news') }}</a></li>
  </ol>
  <div class="progress mt-2 mx-auto">
    <div class="progress-bar" role="progressbar" style="width: 50%;"></div>
  </div>
</div>

<section class="section-py bg-body first-section-pt">
  <div class="container">
    <div class="row flex-column-reverse flex-lg-row">
      <div class="col-lg-3 mb-4">
        <div class="list-group">
          <a href="{{ route('content.frontend.news.index', ['database' => $database]) }}" class="list-group-item list-group-item-action {{ request('category') ? '' : 'active' }}">
            {{ __('all_categories') }}
          </a>
          @foreach($categories as $category)
          <a href="{{ route('content.frontend.news.index', ['database' => $database, 'category' => $category->slug]) }}" class="list-group-item list-group-item-action {{ request('category') == $category->slug ? 'active' : '' }}">
            {{ $category->name }}
          </a>
          @endforeach
        </div>
      </div>

      <div class="col-lg-9">
        <div id="news-list" class="row">
          @foreach($news as $newsItem)
          <div class="col-md-6 col-lg-3 mb-4">
            <div class="card h-100 shadow-sm d-flex flex-column">
              <img src="{{ asset('storage/' . $newsItem->image) }}" class="card-img-top img-fluid" alt="{{ $newsItem->alt }}" style="height: 200px; object-fit: cover;">
              <div class="card-body d-flex flex-column">
                <h5 class="card-title">{{ $newsItem->title }}</h5>
                <p class="card-text">{{ Str::limit(strip_tags($newsItem->description), 60) }}</p>
                <div class="mt-auto">
                  <a href="{{ route('content.frontend.news.show', ['database' => $database, 'id' => $newsItem->id]) }}" class="btn btn-primary btn-sm">{{ __('read_more') }}</a>
                </div>
              </div>
              <div class="card-footer text-muted">
                {{ __('published_on') }} {{ $newsItem->created_at->format('d M Y') }}
              </div>
            </div>
          </div>
          @endforeach
        </div>

        <div class="pagination pagination-outline-secondary">
          {{ $news->links('components.pagination.custom') }}
        </div>
      </div>
    </div>
  </div>
</section>

@endsection
