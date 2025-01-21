@extends('layouts/contentNavbarLayout')

@section('title', __('System Performance'))

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/apex-charts/apex-charts.scss'
])
@endsection

@section('page-style')
@vite(['resources/assets/css/pages/performance.css'])
@endsection

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div class="row g-4">
    <!-- CPU Usage -->
    <div class="col-xl-3 col-md-6">
        <div class="card performance-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="metric-icon bg-label-primary">
                        <i class="ti ti-cpu"></i>
                    </div>
                    <div>
                        <div class="metric-label">{{ __('CPU Usage') }}</div>
                        <div class="d-flex align-items-center">
                            <div class="metric-value" id="cpu-usage">0%</div>
                            <span class="metric-trend up" id="cpu-trend">
                                <i class="ti ti-trending-up me-1"></i>
                                <span>2.5%</span>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="progress progress-thin mb-1">
                    <div class="progress-bar bg-primary" id="cpu-progress" role="progressbar" style="width: 0%"></div>
                </div>
                <small class="text-muted" id="cpu-cores"></small>
            </div>
        </div>
    </div>

    <!-- Memory Usage -->
    <div class="col-xl-3 col-md-6">
        <div class="card performance-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="metric-icon bg-label-success">
                        <i class="ti ti-memory"></i>
                    </div>
                    <div>
                        <div class="metric-label">{{ __('Memory') }}</div>
                        <div class="d-flex align-items-center">
                            <div class="metric-value" id="memory-usage">0%</div>
                            <span class="metric-trend down" id="memory-trend">
                                <i class="ti ti-trending-down me-1"></i>
                                <span>1.2%</span>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="progress progress-thin mb-1">
                    <div class="progress-bar bg-success" id="memory-progress" role="progressbar" style="width: 0%"></div>
                </div>
                <small class="text-muted" id="memory-details"></small>
            </div>
        </div>
    </div>

    <!-- Disk Usage -->
    <div class="col-xl-3 col-md-6">
        <div class="card performance-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="metric-icon bg-label-warning">
                        <i class="ti ti-database"></i>
                    </div>
                    <div>
                        <div class="metric-label">{{ __('Disk Space') }}</div>
                        <div class="metric-value" id="disk-usage">0%</div>
                    </div>
                </div>
                <div class="progress progress-thin mb-1">
                    <div class="progress-bar bg-warning" id="disk-progress" role="progressbar" style="width: 0%"></div>
                </div>
                <small class="text-muted" id="disk-details"></small>
            </div>
        </div>
    </div>

    <!-- Response Time -->
    <div class="col-xl-3 col-md-6">
        <div class="card performance-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="metric-icon bg-label-info">
                        <i class="ti ti-clock"></i>
                    </div>
                    <div>
                        <div class="metric-label">{{ __('Response Time') }}</div>
                        <div class="metric-value" id="response-time">0ms</div>
                    </div>
                </div>
                <div id="response-chart" class="chart-container"></div>
                <small class="text-muted" id="response-details"></small>
            </div>
        </div>
    </div>
</div>

<!-- Database Stats -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center py-3">
                <h5 class="card-title mb-0">{{ __('Database Performance') }}</h5>
                <div class="dropdown">
                    <button class="btn btn-text-primary btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                        <i class="ti ti-dots-vertical"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <a class="dropdown-item" href="javascript:void(0);">
                            <i class="ti ti-refresh me-1"></i>
                            {{ __('Refresh') }}
                        </a>
                        <a class="dropdown-item" href="javascript:void(0);">
                            <i class="ti ti-chart me-1"></i>
                            {{ __('View Details') }}
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-sm-6 col-lg-3">
                        <div class="metric-card">
                            <div class="metric-label">{{ __('Active Connections') }}</div>
                            <div class="metric-value" id="db-connections">0</div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="metric-card">
                            <div class="metric-label">{{ __('Queries/Second') }}</div>
                            <div class="metric-value" id="db-queries">0</div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="metric-card">
                            <div class="metric-label">{{ __('Database Size') }}</div>
                            <div class="metric-value" id="db-size">0 MB</div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="metric-card">
                            <div class="metric-label">{{ __('Uptime') }}</div>
                            <div class="metric-value" id="db-uptime">0</div>
                        </div>
                    </div>
                </div>
                <div class="chart-container mt-4" id="queries-chart"></div>
            </div>
        </div>
    </div>
</div>

<!-- Cache and Server Load -->
<div class="row mt-4 g-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center py-3">
                <h5 class="card-title mb-0">{{ __('Cache Performance') }}</h5>
                <button class="btn btn-text-primary btn-icon">
                    <i class="ti ti-refresh"></i>
                </button>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-6">
                        <div class="metric-card">
                            <div class="metric-label">{{ __('Hit Ratio') }}</div>
                            <div class="metric-value" id="cache-hit-ratio">0%</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="metric-card">
                            <div class="metric-label">{{ __('Cache Size') }}</div>
                            <div class="metric-value" id="cache-size">0 MB</div>
                        </div>
                    </div>
                </div>
                <div class="chart-container mt-4" id="cache-chart"></div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center py-3">
                <h5 class="card-title mb-0">{{ __('Server Load Average') }}</h5>
                <button class="btn btn-text-primary btn-icon">
                    <i class="ti ti-refresh"></i>
                </button>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-4">
                        <div class="metric-card">
                            <div class="metric-label">{{ __('1 min') }}</div>
                            <div class="metric-value" id="load-1">0</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="metric-card">
                            <div class="metric-label">{{ __('5 min') }}</div>
                            <div class="metric-value" id="load-5">0</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="metric-card">
                            <div class="metric-label">{{ __('15 min') }}</div>
                            <div class="metric-value" id="load-15">0</div>
                        </div>
                    </div>
                </div>
                <div class="chart-container mt-4" id="load-chart"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/apex-charts/apexcharts.js',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
])
@endsection

@section('page-script')
@vite(['resources/assets/js/pages/performance.js'])
@endsection
