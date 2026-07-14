<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>@yield('title', 'رزرو مشاوره')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* ===========================================================
           Design tokens — public / student-facing experience
           Warm violet–indigo base with an amber accent. Mobile-first:
           every rule below is written for a phone screen and then
           widened at the sm/md/lg breakpoints.
        =========================================================== */
        :root{
            --pub-50:  #f2f1fd;
            --pub-100: #e4e1fb;
            --pub-200: #c6c0f6;
            --pub-400: #8b7cf0;
            --pub-500: #6552e8;
            --pub-600: #5238dc;
            --pub-700: #422bc0;
            --pub-900: #2a1b7a;

            --accent-400: #fbbf51;
            --accent-500: #f5a623;
            --accent-600: #dd8b0c;

            --ink-900: #1e1b31;
            --ink-700: #514c6b;
            --ink-500: #837da0;
            --ink-300: #d3cfe6;
            --ink-100: #f1effa;

            --bg: #f7f6fd;
            --surface: #ffffff;
            --border: #e7e4f5;

            --success: #1fa971;
            --success-bg: #e4f7ee;
            --warning: #d9930f;
            --warning-bg: #fdf1dc;
            --danger: #e0514c;
            --danger-bg: #fceae9;
            --info: #3a7ee0;
            --info-bg: #e8f0fd;
            --neutral: #7a7593;
            --neutral-bg: #efedf7;

            --radius-sm: 10px;
            --radius-md: 16px;
            --radius-lg: 22px;

            --shadow-sm: 0 1px 3px rgba(42, 27, 122, .06);
            --shadow-md: 0 12px 28px -12px rgba(42, 27, 122, .18);
            --shadow-lg: 0 24px 56px -20px rgba(42, 27, 122, .25);

            --tap: 48px; /* minimum comfortable touch target */
        }

        *{ box-sizing: border-box; }
        html{ -webkit-text-size-adjust: 100%; scroll-behavior: smooth; }
        html, body{ min-height: 100%; }

        body{
            background: var(--bg);
            background-image:
                radial-gradient(480px 240px at 12% -8%, rgba(101,82,232,.10), transparent),
                radial-gradient(420px 220px at 108% 0%, rgba(245,166,35,.09), transparent);
            background-repeat: no-repeat;
            color: var(--ink-900);
            font-family: 'Vazirmatn', Tahoma, Arial, sans-serif;
            font-size: 15px;
            line-height: 1.7;
            -webkit-font-smoothing: antialiased;
            margin: 0;
            padding-bottom: env(safe-area-inset-bottom);
        }

        a{ text-decoration: none; color: var(--pub-600); }
        h1, h2, h3, h4{ margin: 0; }
        ::selection{ background: var(--pub-200); color: var(--pub-900); }

        ::-webkit-scrollbar{ width: 8px; height: 8px; }
        ::-webkit-scrollbar-track{ background: transparent; }
        ::-webkit-scrollbar-thumb{ background: var(--ink-300); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover{ background: var(--pub-400); }

        .ltr{ direction: ltr; text-align: left; }

        /* ===========================================================
           Page shell — mobile first, widens at larger breakpoints
        =========================================================== */
        .page{
            max-width: 820px;
            margin: 0 auto;
            padding: 1.1rem .9rem 2.5rem;
            animation: page-in .45s ease both;
        }

        @keyframes page-in{
            from{ opacity: 0; transform: translateY(10px); }
            to{ opacity: 1; transform: translateY(0); }
        }
        @media (prefers-reduced-motion: reduce){
            .page{ animation: none; }
        }

        @media (min-width: 576px){
            .page{ padding: 1.75rem 1.25rem 3rem; }
        }
        @media (min-width: 768px){
            .page{ padding: 2.5rem 1.5rem 3.5rem; }
        }

        /* ===========================================================
           Cards
        =========================================================== */
        .card{
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            margin-bottom: 1rem;
            transition: box-shadow .2s ease;
            animation: page-in .5s ease both;
        }
        .card:hover{ box-shadow: var(--shadow-md); }
        .card-body{ padding: 1.15rem; }

        .card:nth-of-type(1){ animation-delay: .03s; }
        .card:nth-of-type(2){ animation-delay: .1s; }
        .card:nth-of-type(3){ animation-delay: .17s; }
        .card:nth-of-type(4){ animation-delay: .24s; }
        @media (prefers-reduced-motion: reduce){
            .card{ animation: none; }
        }

        @media (min-width: 576px){
            .card-body{ padding: 1.5rem; }
        }

        /* ===========================================================
           Alerts
        =========================================================== */
        .alert{
            border: none;
            border-radius: var(--radius-md);
            padding: .95rem 1.1rem;
            font-size: .87rem;
            display: flex;
            align-items: flex-start;
            gap: .55rem;
            margin-bottom: 1rem;
            animation: page-in .4s ease both;
        }
        .alert ul{ padding-inline-start: 1.1rem; margin: 0; }
        .alert-success{ background: var(--success-bg); color: var(--success); }
        .alert-warning{ background: var(--warning-bg); color: var(--warning); }
        .alert-danger { background: var(--danger-bg);  color: var(--danger); }
        .alert-info   { background: var(--info-bg);    color: var(--info); }
        .alert-secondary{ background: var(--neutral-bg); color: var(--ink-700); }

        /* ===========================================================
           Forms — sized for thumbs, not cursors
        =========================================================== */
        .form-label{
            font-size: .82rem;
            font-weight: 600;
            color: var(--ink-700);
            margin-bottom: .4rem;
            display: inline-block;
        }

        .form-control, .form-select{
            border-radius: var(--radius-sm);
            border: 1.5px solid var(--border);
            font-size: 16px; /* prevents iOS auto-zoom on focus */
            min-height: var(--tap);
            padding: .65rem .95rem;
            background: var(--bg);
            color: var(--ink-900);
            transition: border-color .15s ease, background .15s ease, box-shadow .15s ease;
        }
        .form-control:focus, .form-select:focus{
            border-color: var(--pub-500);
            background: var(--surface);
            box-shadow: 0 0 0 .2rem rgba(101,82,232,.14);
        }
        .form-control::placeholder{ color: var(--ink-300); }

        textarea.form-control{ min-height: 90px; }

        input[type="file"].form-control{
            padding: .55rem .8rem;
            min-height: auto;
        }

        .form-check{
            display: flex;
            align-items: center;
            gap: .55rem;
            min-height: var(--tap);
        }
        .form-check-input{
            width: 1.2rem;
            height: 1.2rem;
            border: 1.5px solid var(--ink-300);
            flex-shrink: 0;
        }
        .form-check-input:checked{
            background-color: var(--pub-500);
            border-color: var(--pub-500);
        }
        .form-check-input:focus{
            box-shadow: 0 0 0 .2rem rgba(101,82,232,.14);
        }

        /* ===========================================================
           Buttons — full-width, thumb-friendly on mobile
        =========================================================== */
        .btn{
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: .92rem;
            min-height: var(--tap);
            padding: .7rem 1.4rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .4rem;
            transition: transform .15s ease, box-shadow .15s ease, background-color .15s ease, filter .15s ease;
            border: 1.5px solid transparent;
        }
        .btn:active{ transform: scale(.98); }

        .btn-primary{
            background: linear-gradient(135deg, var(--pub-500), var(--pub-700));
            border: none;
            color: #fff;
            box-shadow: 0 10px 22px -10px rgba(101,82,232,.6);
        }
        .btn-primary:hover{
            box-shadow: 0 14px 28px -10px rgba(101,82,232,.65);
            filter: brightness(1.03);
            color: #fff;
        }

        .btn-outline-primary{
            background: var(--surface);
            border-color: var(--pub-200);
            color: var(--pub-600);
        }
        .btn-outline-primary:hover{
            background: var(--pub-50);
            border-color: var(--pub-500);
            color: var(--pub-700);
        }

        .btn-outline-secondary{
            background: var(--surface);
            border-color: var(--border);
            color: var(--ink-700);
        }
        .btn-outline-secondary:hover{ background: var(--ink-100); }

        .btn-outline-danger{
            background: var(--surface);
            border-color: #f0c8c4;
            color: var(--danger);
        }
        .btn-outline-danger:hover{
            background: var(--danger);
            border-color: var(--danger);
            color: #fff;
        }

        @media (max-width: 575.98px){
            .btn.w-100-mobile, form > .btn:only-child{ width: 100%; }
        }

        /* ===========================================================
           Small utility polish
        =========================================================== */
        .badge{
            font-weight: 500;
            border-radius: 999px;
        }

        hr{ border-color: var(--border); opacity: 1; }
    </style>

    @stack('styles')
</head>
<body>
<main class="page px-3">
    @if(session('success'))
        <div class="alert alert-success">
            <i class="ri-checkbox-circle-line"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <i class="ri-error-warning-line"></i>
            <div>
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    @yield('content')
</main>

@stack('scripts')
</body>
</html>
