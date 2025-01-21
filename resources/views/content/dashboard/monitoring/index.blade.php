@extends('layouts/contentNavbarLayout')

@section('title', __('System Monitoring'))

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/apex-charts/apex-charts.scss',
  'resources/assets/vendor/libs/leaflet/leaflet.scss'
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/apex-charts/apexcharts.js',
  'resources/assets/vendor/libs/leaflet/leaflet.js'
])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 mb-4">
        <span class="text-muted fw-light">{{ __('Dashboard') }} /</span> {{ __('System Monitoring') }}
    </h4>

    <!-- Stats Cards -->
    <div class="row">
        <!-- Visitors Online -->
        <div class="col-sm-6 col-lg-3 mb-4">
            <div class="card card-border-shadow-primary h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti ti-users ti-md"></i>
                            </span>
                        </div>
                        <h4 class="ms-1 mb-0" id="visitors-count">-</h4>
                    </div>
                    <p class="mb-1">{{ __('Online Visitors') }}</p>
                    <p class="mb-0">
                        <small class="text-muted" id="visitors-change">{{ __('Loading...') }}</small>
                    </p>
                </div>
            </div>
        </div>

        <!-- CPU Usage -->
        <div class="col-sm-6 col-lg-3 mb-4">
            <div class="card card-border-shadow-danger h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-danger">
                                <i class="ti ti-cpu ti-md"></i>
                            </span>
                        </div>
                        <h4 class="ms-1 mb-0" id="cpu-usage">-</h4>
                    </div>
                    <p class="mb-1">{{ __('CPU Usage') }}</p>
                    <div class="progress" style="height: 6px">
                        <div class="progress-bar bg-danger" id="cpu-progress" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Memory Usage -->
        <div class="col-sm-6 col-lg-3 mb-4">
            <div class="card card-border-shadow-success h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-memory ti-md"></i>
                            </span>
                        </div>
                        <h4 class="ms-1 mb-0" id="memory-usage">-</h4>
                    </div>
                    <p class="mb-1">{{ __('Memory Usage') }}</p>
                    <div class="progress" style="height: 6px">
                        <div class="progress-bar bg-success" id="memory-progress" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Error Rate -->
        <div class="col-sm-6 col-lg-3 mb-4">
            <div class="card card-border-shadow-warning h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-alert-triangle ti-md"></i>
                            </span>
                        </div>
                        <h4 class="ms-1 mb-0" id="error-count">-</h4>
                    </div>
                    <p class="mb-1">{{ __('Error Rate (24h)') }}</p>
                    <p class="mb-0">
                        <small class="text-muted" id="error-change">{{ __('Loading...') }}</small>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row">
        <!-- Visitors Chart -->
        <div class="col-12 col-xl-8 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">{{ __('Visitor Traffic') }}</h5>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="visitorTrafficDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            {{ __('Last 24 Hours') }}
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="visitorTrafficDropdown">
                            <li><a class="dropdown-item" href="javascript:void(0);">{{ __('Last 24 Hours') }}</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);">{{ __('Last 7 Days') }}</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);">{{ __('Last 30 Days') }}</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div id="visitorChart" style="min-height: 300px;"></div>
                </div>
            </div>
        </div>

        <!-- Visitor Map -->
        <div class="col-12 col-xl-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ __('Visitor Locations') }}</h5>
                </div>
                <div class="card-body">
                    <div id="visitorMap" style="height: 300px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- System Logs & Errors -->
    <div class="row">
        <!-- Recent Errors -->
        <div class="col-12 col-xl-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">{{ __('Recent Errors') }}</h5>
                    <button type="button" class="btn btn-sm btn-primary" id="clearErrors">
                        {{ __('Clear All') }}
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover" id="errorTable">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('Time') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ __('Message') }}</th>
                                    <th>{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="4" class="text-center">{{ __('Loading...') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Information -->
        <div class="col-12 col-xl-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ __('System Information') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="bg-label-primary rounded p-3">
                                <div class="d-flex align-items-center">
                                    <i class="ti ti-server ti-xl me-3"></i>
                                    <div>
                                        <h6 class="mb-0">{{ __('Server') }}</h6>
                                        <small id="server-info">{{ __('Loading...') }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="bg-label-success rounded p-3">
                                <div class="d-flex align-items-center">
                                    <i class="ti ti-database ti-xl me-3"></i>
                                    <div>
                                        <h6 class="mb-0">{{ __('Database') }}</h6>
                                        <small id="database-info">{{ __('Loading...') }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="bg-label-info rounded p-3">
                                <div class="d-flex align-items-center">
                                    <i class="ti ti-brand-php ti-xl me-3"></i>
                                    <div>
                                        <h6 class="mb-0">{{ __('PHP Version') }}</h6>
                                        <small id="php-info">{{ __('Loading...') }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
@vite(['resources/assets/js/pages/monitoring.js'])
@endsection
