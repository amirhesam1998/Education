<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>@yield('title', 'پنل مدیریت')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* ===========================================================
           Design tokens — سامانه رزرو مشاوره
           Calm, minimal, "counseling center" palette: soft teal + indigo
        =========================================================== */
        :root{
            --brand-50:  #f1f7f6;
            --brand-100: #dcece9;
            --brand-200: #b3d9d3;
            --brand-500: #2f8f83;
            --brand-600: #26786e;
            --brand-700: #1f6058;

            --ink-900: #1c2430;
            --ink-700: #4b5563;
            --ink-500: #7b8794;
            --ink-300: #cbd3da;
            --ink-100: #eef1f4;

            --bg: #f6f8f8;
            --surface: #ffffff;
            --border: #e7ebee;

            --success: #1f9d6e;
            --warning: #d99a2b;
            --danger:  #e0574c;
            --info:    #3a7bd5;

            --radius-sm: 10px;
            --radius-md: 14px;
            --radius-lg: 20px;

            --shadow-sm: 0 1px 2px rgba(20, 30, 40, .04);
            --shadow-md: 0 8px 24px -8px rgba(20, 30, 40, .12);

            --sidebar-w: 264px;
            --topbar-h: 68px;
        }

        *{ box-sizing: border-box; }

        html, body{ height: 100%; }

        body{
            background: var(--bg);
            color: var(--ink-900);
            font-family: 'Vazirmatn', Tahoma, Arial, sans-serif;
            font-size: .95rem;
            -webkit-font-smoothing: antialiased;
        }

        a{ text-decoration: none; }

        ::selection{ background: var(--brand-200); color: var(--brand-700); }

        /* Slim custom scrollbar */
        ::-webkit-scrollbar{ width: 8px; height: 8px; }
        ::-webkit-scrollbar-track{ background: transparent; }
        ::-webkit-scrollbar-thumb{ background: var(--ink-300); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover{ background: var(--ink-500); }

        /* ===========================================================
           Topbar
        =========================================================== */
        .topbar{
            height: var(--topbar-h);
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 1030;
        }

        .topbar-inner{
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0 1.25rem;
        }

        .brand{
            display: flex;
            align-items: center;
            gap: .6rem;
            font-weight: 700;
            font-size: 1.02rem;
            color: var(--ink-900);
        }

        .brand-mark{
            width: 36px; height: 36px;
            border-radius: 10px;
            background: linear-gradient(150deg, var(--brand-500), var(--brand-700));
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 1.15rem; flex-shrink: 0;
            box-shadow: var(--shadow-sm);
        }

        .sidebar-toggle-btn{
            display: none;
            width: 40px; height: 40px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
            background: var(--surface);
            align-items: center; justify-content: center;
            color: var(--ink-700);
            font-size: 1.2rem;
        }
        .sidebar-toggle-btn:hover{ background: var(--ink-100); }

        .topbar-user{
            display: flex; align-items: center; gap: .65rem;
        }

        .user-avatar{
            width: 38px; height: 38px;
            border-radius: 50%;
            background: var(--brand-100);
            color: var(--brand-700);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700;
            flex-shrink: 0;
        }

        .user-name{
            font-size: .85rem;
            color: var(--ink-700);
            font-weight: 500;
        }

        .btn-logout{
            display: inline-flex; align-items: center; gap: .35rem;
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--ink-700);
            border-radius: var(--radius-sm);
            padding: .45rem .85rem;
            font-size: .82rem;
            transition: .15s;
        }
        .btn-logout:hover{ background: #fdf1f0; color: var(--danger); border-color: #f4cfcb; }

        /* ===========================================================
           Layout shell
        =========================================================== */
        .app-shell{
            display: flex;
            min-height: calc(100vh - var(--topbar-h));
        }

        /* ===========================================================
           Sidebar
        =========================================================== */
        .sidebar{
            width: var(--sidebar-w);
            flex-shrink: 0;
            background: var(--surface);
            border-left: 1px solid var(--border);
            padding: 1.25rem .9rem;
        }

        .nav-section-label{
            font-size: .72rem;
            color: var(--ink-500);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .04em;
            padding: .5rem .6rem .4rem;
        }

        .sidebar .nav-link{
            display: flex;
            align-items: center;
            gap: .7rem;
            color: var(--ink-700);
            padding: .68rem .75rem;
            border-radius: var(--radius-sm);
            font-size: .89rem;
            font-weight: 500;
            margin-bottom: .15rem;
            transition: background .15s, color .15s;
        }

        .sidebar .nav-link i{
            font-size: 1.15rem;
            color: var(--ink-500);
            transition: color .15s;
        }

        .sidebar .nav-link:hover{
            background: var(--brand-50);
            color: var(--brand-700);
        }
        .sidebar .nav-link:hover i{ color: var(--brand-600); }

        .sidebar .nav-link.active{
            background: var(--brand-500);
            color: #fff;
        }
        .sidebar .nav-link.active i{ color: #fff; }

        /* ===========================================================
           Main content
        =========================================================== */
        .content{
            flex: 1;
            min-width: 0;
            padding: 1.75rem;
        }

        .page-head{
            display: flex;
            flex-wrap: wrap;
            gap: .75rem;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .page-title{
            font-size: 1.35rem;
            font-weight: 700;
            margin: 0;
            color: var(--ink-900);
        }

        .page-subtitle{
            font-size: .85rem;
            color: var(--ink-500);
            margin-top: .2rem;
        }

        /* ===========================================================
           Components: alerts, cards, tables, badges
        =========================================================== */
        .alert{
            border: none;
            border-radius: var(--radius-md);
            padding: .9rem 1.1rem;
            font-size: .88rem;
        }
        .alert-success{ background: #eaf7f1; color: var(--success); }
        .alert-danger{  background: #fcecea; color: var(--danger); }

        .card{
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            background: var(--surface);
            overflow: hidden;
        }

        .card-header{
            background: transparent;
            border-bottom: 1px solid var(--border);
            font-weight: 600;
            padding: 1.1rem 1.25rem;
        }

        .table{
            margin-bottom: 0;
            font-size: .87rem;
        }
        .table > :not(caption) > * > *{
            vertical-align: middle;
            padding: .9rem 1rem;
        }
        .table thead th{
            background: var(--ink-100);
            color: var(--ink-700);
            font-weight: 600;
            border: none;
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .03em;
        }
        .table tbody tr{ border-bottom: 1px solid var(--border); }
        .table tbody tr:last-child{ border-bottom: none; }
        .table tbody tr:hover{ background: var(--brand-50); }

        .badge{
            font-weight: 500;
            font-size: .74rem;
            padding: .4em .7em;
            border-radius: 999px;
        }
        .badge.bg-success{ background: #e2f5ec !important; color: var(--success) !important; }
        .badge.bg-warning{ background: #fbf1de !important; color: var(--warning) !important; }
        .badge.bg-danger { background: #fbe6e4 !important; color: var(--danger) !important; }
        .badge.bg-info   { background: #e5eefb !important; color: var(--info) !important; }

        .btn-primary{
            background: var(--brand-500);
            border-color: var(--brand-500);
        }
        .btn-primary:hover{
            background: var(--brand-600);
            border-color: var(--brand-600);
        }
        .btn-outline-secondary{
            color: var(--ink-700);
            border-color: var(--border);
        }

        .form-control, .form-select{
            border-radius: var(--radius-sm);
            border-color: var(--border);
            font-size: .88rem;
            padding: .55rem .8rem;
        }
        .form-control:focus, .form-select:focus{
            border-color: var(--brand-500);
            box-shadow: 0 0 0 .2rem rgba(47,143,131,.12);
        }

        .ltr{ direction: ltr; text-align: left; }
        .jalali-date-picker, .jalali-datetime-picker{ direction: ltr; text-align: left; }
        .datepicker-plot-area{ font-family: 'Vazirmatn', Tahoma, Arial, sans-serif; }

        /* ===========================================================
           Mobile / tablet
        =========================================================== */
        @media (max-width: 991.98px){
            .sidebar-toggle-btn{ display: inline-flex; }

            .sidebar{
                position: fixed;
                inset-inline-start: 0;
                top: 0;
                bottom: 0;
                z-index: 1045;
                transform: translateX(105%);
                transition: transform .25s ease;
                box-shadow: var(--shadow-md);
                overflow-y: auto;
                padding-top: 1.1rem;
            }
            html[dir="rtl"] .sidebar{ transform: translateX(100%); }
            .sidebar.is-open{ transform: translateX(0) !important; }

            .sidebar-backdrop{
                display: none;
                position: fixed; inset: 0;
                background: rgba(20,30,40,.35);
                z-index: 1040;
            }
            .sidebar-backdrop.is-open{ display: block; }

            .content{ padding: 1.1rem; }
            .page-title{ font-size: 1.15rem; }
            .user-name{ display: none; }
        }

        @media (max-width: 575.98px){
            .topbar-inner{ padding: 0 .85rem; }
            .content{ padding: .85rem; }
            .card-header{ padding: .9rem 1rem; }
            .table{ font-size: .82rem; }
        }
    </style>

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
                <span class="brand-mark"><i class="ri-heart-pulse-line"></i></span>
                <span>سامانه رزرو مشاوره</span>
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
                <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                    <i class="ri-dashboard-3-line"></i> داشبورد
                </a>
            @endcan
            @can('view_slots')
                <a class="nav-link {{ request()->routeIs('admin.slots.*') ? 'active' : '' }}" href="{{ route('admin.slots.index') }}">
                    <i class="ri-calendar-2-line"></i> تایمها
                </a>
            @endcan
            @can('view_reservations')
                <a class="nav-link {{ request()->routeIs('admin.reservations.*') ? 'active' : '' }}" href="{{ route('admin.reservations.index') }}">
                    <i class="ri-file-list-3-line"></i> رزروها
                </a>
            @endcan
            @can('view_payments')
                <a class="nav-link {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}" href="{{ route('admin.payments.index') }}">
                    <i class="ri-bank-card-line"></i> فیشهای پرداخت
                </a>
            @endcan
            @can('view_reports')
                <a class="nav-link {{ request()->routeIs('admin.study-programs.*') ? 'active' : '' }}" href="{{ route('admin.study-programs.index') }}">
                    <i class="ri-graduation-cap-line"></i> رشته‌محل‌ها
                </a>
            @endcan
        </nav>

        @canany(['manage_users', 'manage_roles', 'manage_settings'])
            <div class="nav-section-label mt-3">مدیریت</div>
            <nav class="nav flex-column">
                @can('manage_users')
                    <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                        <i class="ri-team-line"></i> کاربران
                    </a>
                @endcan
                @can('manage_roles')
                    <a class="nav-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}" href="{{ route('admin.roles.index') }}">
                        <i class="ri-shield-user-line"></i> نقشها
                    </a>
                @endcan
                @can('manage_settings')
                    <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.edit') }}">
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
        const sidebar   = document.getElementById('appSidebar');
        const backdrop  = document.getElementById('sidebarBackdrop');
        const toggleBtn = document.getElementById('sidebarToggle');

        function openSidebar () {
            sidebar.classList.add('is-open');
            backdrop.classList.add('is-open');
        }
        function closeSidebar () {
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
