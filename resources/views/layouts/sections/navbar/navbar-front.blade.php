@php
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

$currentRouteName = Route::currentRouteName();
$randomAvatarNumber = rand(1, 5);
$defaultAvatar = 'assets/img/avatars/' . $randomAvatarNumber . '.png';
$defaultLogo = 'assets/img/logo/logo.png';
$defaultFavicon = 'assets/img/favicon/favicon.ico';
@endphp

<nav class="layout-navbar container-xxl navbar navbar-expand-lg navbar-detached align-items-center bg-navbar-theme">
    <div class="navbar-nav-right d-flex align-items-center w-100" id="navbar-collapse">
        <div class="d-flex align-items-center justify-content-between w-100">
            <!-- Logo -->
            <div class="navbar-brand app-brand demo py-0 me-4">
                <a href="{{ url('/') }}" class="app-brand-link gap-2">
                    <img src="{{ asset('storage/' . config('settings.site_logo', $defaultLogo)) }}"
                         alt="{{ config('settings.site_name', 'My Website') }}"
                         class="app-brand-logo"
                         width="32"
                         height="32">
                    <span class="app-brand-text demo menu-text fw-bold">{{ config('settings.site_name', 'My Website') }}</span>
                </a>
            </div>

            <!-- Right Elements -->
            <div class="d-flex align-items-center">
                <!-- Country Selector -->
                <div class="me-2">
                    <form method="POST" action="{{ route('setDatabase') }}" id="databaseForm">
                        @csrf
                        <input type="hidden" name="database" id="databaseInput" value="{{ session('database', 'jo') }}">
                        <div class="dropdown">
                            <button type="button" class="btn btn-outline-warning btn-icon rounded-pill dropdown-toggle hide-arrow" data-bs-toggle="dropdown" aria-expanded="false">
                                @php
                                $currentCountry = session('database', 'jo');
                                $flag = match ($currentCountry) {
                                    'sa' => 'saudi.svg',
                                    'eg' => 'egypt.svg',
                                    'ps' => 'palestine.svg',
                                    default => 'jordan.svg',
                                };
                                @endphp
                                <img alt="Current Country" src="{{ asset('assets/img/flags/' . $flag) }}" style="width: 20px; height: 20px;" loading="lazy">
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                <li><a class="dropdown-item" href="#" onclick="setDatabase('jo')"><img alt="Jordan" src="{{ asset('assets/img/flags/jordan.svg') }}" style="width: 20px; height: 20px;" loading="lazy"> الأردن</a></li>
                                <li><a class="dropdown-item" href="#" onclick="setDatabase('sa')"><img alt="Saudi Arabia" src="{{ asset('assets/img/flags/saudi.svg') }}" style="width: 20px; height: 20px;" loading="lazy"> السعودية</a></li>
                                <li><a class="dropdown-item" href="#" onclick="setDatabase('eg')"><img alt="Egypt" src="{{ asset('assets/img/flags/egypt.svg') }}" style="width: 20px; height: 20px;" loading="lazy"> مصر</a></li>
                                <li><a class="dropdown-item" href="#" onclick="setDatabase('ps')"><img alt="Palestine" src="{{ asset('assets/img/flags/palestine.svg') }}" style="width: 20px; height: 20px;" loading="lazy"> فلسطين</a></li>
                            </ul>
                        </div>
                    </form>
                    <script>
                        function setDatabase(database) {
                            document.getElementById('databaseInput').value = database;
                            document.getElementById('databaseForm').submit();
                        }
                    </script>
                </div>

                <!-- User Menu -->
                @auth
                    <div class="nav-item navbar-dropdown dropdown-user dropdown">
                        <a class="nav-link dropdown-toggle hide-arrow p-0" href="#" data-bs-toggle="dropdown">
                            <div class="avatar avatar-online">
                                <img src="{{ Auth::check() && Auth::user()->profile_photo_path ? asset('storage/' . Auth::user()->profile_photo_path) : asset($defaultAvatar) }}"
                                     alt="{{ Auth::check() ? Auth::user()->name : 'Default Avatar' }}"
                                     class="rounded-circle w-px-30 h-px-30">
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="{{ route('dashboard.users.show', Auth::user()->id ?? '') }}">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 me-3">
                                            <div class="avatar avatar-online">
                                                <img src="{{ Auth::check() && Auth::user()->profile_photo_path ? asset('storage/' . Auth::user()->profile_photo_path) : asset($defaultAvatar) }}"
                                                     alt="{{ Auth::check() ? Auth::user()->name : 'Default Avatar' }}"
                                                     class="rounded-circle w-px-30 h-px-30">
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <span class="fw-semibold d-block">{{ Auth::check() ? Auth::user()->name : '' }}</span>
                                            <small class="text-muted">{{ Auth::check() ? Auth::user()->email : '' }}</small>
                                        </div>
                                    </div>
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center" href="{{ route('dashboard.index') }}">
                                    <i class="ti ti-dashboard me-2 ti-sm"></i>
                                    <span class="align-middle">{{ __('Dashboard') }}</span>
                                </a>
                            </li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}" id="logout-form" class="mb-0">
                                    @csrf
                                    <a class="dropdown-item d-flex align-items-center" href="#"
                                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                        <i class="ti ti-logout me-2 ti-sm"></i>
                                        <span class="align-middle">{{ __('Logout') }}</span>
                                    </a>
                                </form>
                            </li>
                        </ul>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary btn-sm">
                        <i class="ti ti-login me-1 ti-sm"></i>
                        <span class="d-none d-md-inline-block">{{ __('Login/Register') }}</span>
                    </a>
                @endauth
            </div>
        </div>
    </div>
</nav>
