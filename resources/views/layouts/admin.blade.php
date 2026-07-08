<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'پنل مدیریت')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css" rel="stylesheet">
    <style>
        body { background: #f6f7fb; font-family: Tahoma, Arial, sans-serif; }
        .navbar { border-bottom: 1px solid #e5e7eb; }
        .sidebar { min-height: calc(100vh - 57px); background: #fff; border-left: 1px solid #e5e7eb; }
        .sidebar a { color: #374151; text-decoration: none; display: block; padding: .65rem .85rem; border-radius: .5rem; }
        .sidebar a:hover, .sidebar a.active { background: #eef2ff; color: #1d4ed8; }
        .card { border-radius: .5rem; border-color: #e5e7eb; box-shadow: 0 1px 2px rgba(15, 23, 42, .04); }
        .table > :not(caption) > * > * { vertical-align: middle; }
        .badge { font-weight: 500; }
        .ltr { direction: ltr; text-align: left; }
        .jalali-date-picker, .jalali-datetime-picker { direction: ltr; text-align: left; }
        .datepicker-plot-area { font-family: Tahoma, Arial, sans-serif; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg bg-white sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="{{ route('admin.dashboard') }}">سامانه رزرو مشاوره</a>
        <div class="d-flex align-items-center gap-3">
            <span class="text-muted small">{{ auth()->user()->name ?? '' }}</span>
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-outline-secondary btn-sm">خروج</button>
            </form>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <aside class="col-lg-2 col-md-3 sidebar p-3">
            @can('view_dashboard') <a href="{{ route('admin.dashboard') }}">داشبورد</a> @endcan
            @can('view_slots') <a href="{{ route('admin.slots.index') }}">تایمها</a> @endcan
            @can('view_reservations') <a href="{{ route('admin.reservations.index') }}">رزروها</a> @endcan
            @can('view_payments') <a href="{{ route('admin.payments.index') }}">فیشهای پرداخت</a> @endcan
            @can('manage_users') <a href="{{ route('admin.users.index') }}">کاربران</a> @endcan
            @can('manage_roles') <a href="{{ route('admin.roles.index') }}">نقشها</a> @endcan
            @can('manage_settings') <a href="{{ route('admin.settings.edit') }}">تنظیمات</a> @endcan
        </aside>
        <main class="col-lg-10 col-md-9 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h4 mb-0">@yield('title')</h1>
                <div>@yield('actions')</div>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/persian-date@1.1.0/dist/persian-date.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (!window.jQuery || !jQuery.fn.persianDatepicker) {
            return;
        }

        const baseOptions = {
            calendar: {
                persian: { locale: 'fa' }
            },
            initialValue: false,
            observer: true,
            autoClose: true,
            toolbox: {
                calendarSwitch: { enabled: false }
            }
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
    });
</script>
</body>
</html>
