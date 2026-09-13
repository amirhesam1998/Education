<!doctype html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>@yield('title', 'پنل مدیریت')</title>

    <link href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css"
        rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/css/admin.css'])
    @stack('styles')
</head>

<body>

    <nav class="topbar">
        <div class="topbar-inner">
            <div class="d-flex align-items-center gap-2">
                <button class="sidebar-toggle-btn" id="sidebarToggle" type="button" aria-label="باز کردن منو">
                    <i class="ri-menu-line"></i>
                </button>
                <a class="brand" href="{{ route('admin.dashboard') }}">
                    <img src="{{ asset('images/behrozan-logo.webp') }}" alt="بهروزان" class="brand-logo-img">
                </a>
            </div>

            <div class="topbar-user">
                <div class="user-avatar">{{ mb_substr(auth()->user()->name ?? 'کاربر', 0, 1) }}</div>
                <span class="user-name">{{ auth()->user()->name ?? '' }}</span>
                <form method="post" action="{{ route('logout') }}" class="m-0">
                    @csrf
                    <button class="btn-logout" type="submit">
                        <i class="ri-logout-box-r-line"></i>
                        <span>خروج</span>
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <div class="app-shell">

        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

        <aside class="sidebar" id="appSidebar">
            <div class="nav-section-label">منوی اصلی</div>
            <nav class="nav flex-column">
                @can('view_dashboard')
                    <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                        href="{{ route('admin.dashboard') }}">
                        <i class="ri-dashboard-3-line"></i> داشبورد
                    </a>
                @endcan
                @can('view_slots')
                    <a class="nav-link {{ request()->routeIs('admin.slots.*') ? 'active' : '' }}"
                        href="{{ route('admin.slots.index') }}">
                        <i class="ri-calendar-2-line"></i> تایمها
                    </a>
                @endcan
                @can('view_reservations')
                    <a class="nav-link {{ request()->routeIs('admin.reservations.*') ? 'active' : '' }}"
                        href="{{ route('admin.reservations.index') }}">
                        <i class="ri-file-list-3-line"></i> رزروها
                    </a>
                @endcan
                @can('view_payments')
                    <a class="nav-link {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}"
                        href="{{ route('admin.payments.index') }}">
                        <i class="ri-bank-card-line"></i> فیشهای پرداخت
                    </a>
                @endcan
                @can('view_study_programs')
                    <a class="nav-link {{ request()->routeIs('admin.study-programs.*') ? 'active' : '' }}"
                        href="{{ route('admin.study-programs.index') }}">
                        <i class="ri-graduation-cap-line"></i> رشته‌محل‌ها
                    </a>
                @endcan
            </nav>

            @canany(['manage_users', 'manage_roles', 'manage_settings'])
                <div class="nav-section-label mt-3">مدیریت</div>
                <nav class="nav flex-column">
                    @can('manage_users')
                        <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"
                            href="{{ route('admin.users.index') }}">
                            <i class="ri-team-line"></i> کاربران
                        </a>
                    @endcan
                    @can('manage_roles')
                        <a class="nav-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}"
                            href="{{ route('admin.roles.index') }}">
                            <i class="ri-shield-user-line"></i> نقشها
                        </a>
                    @endcan
                    @can('manage_settings')
                        <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}"
                            href="{{ route('admin.settings.edit') }}">
                            <i class="ri-settings-3-line"></i> تنظیمات
                        </a>
                    @endcan
                </nav>
            @endcanany
        </aside>

        <main class="content">
            <div class="page-head">
                <div>
                    <h1 class="page-title">@yield('title')</h1>
                    @hasSection('subtitle')
                        <div class="page-subtitle">@yield('subtitle')</div>
                    @endif
                </div>
                <div>@yield('actions')</div>
            </div>

            @if(session('success'))
                <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
                    <i class="ri-checkbox-circle-line"></i> {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger mb-4">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/persian-date@1.1.0/dist/persian-date.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            // ---- Jalali date pickers ------------------------------------------------
            if (window.jQuery && jQuery.fn.persianDatepicker) {
                const baseOptions = {
                    calendar: { persian: { locale: 'fa' } },
                    initialValue: false,
                    observer: true,
                    autoClose: true,
                    toolbox: { calendarSwitch: { enabled: false } }
                };

                jQuery('.jalali-date-picker').persianDatepicker({
                    ...baseOptions,
                    format: 'YYYY/MM/DD'
                });

                jQuery('.jalali-datetime-picker').persianDatepicker({
                    ...baseOptions,
                    format: 'YYYY/MM/DD HH:mm',
                    timePicker: {
                        enabled: true,
                        second: { enabled: false },
                        meridiem: { enabled: false }
                    }
                });
            }

            // ---- Responsive sidebar (mobile / tablet) --------------------------------
            const sidebar = document.getElementById('appSidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            const toggleBtn = document.getElementById('sidebarToggle');

            function openSidebar() {
                sidebar.classList.add('is-open');
                backdrop.classList.add('is-open');
            }
            function closeSidebar() {
                sidebar.classList.remove('is-open');
                backdrop.classList.remove('is-open');
            }

            toggleBtn?.addEventListener('click', function () {
                sidebar.classList.contains('is-open') ? closeSidebar() : openSidebar();
            });
            backdrop?.addEventListener('click', closeSidebar);

            // Close the drawer automatically after tapping a nav link on mobile
            sidebar?.querySelectorAll('.nav-link').forEach(function (link) {
                link.addEventListener('click', function () {
                    if (window.innerWidth < 992) closeSidebar();
                });
            });

            // Close drawer if the viewport is resized back to desktop
            window.addEventListener('resize', function () {
                if (window.innerWidth >= 992) closeSidebar();
            });
        });
    </script>

    @stack('scripts')
</body>

</html>
