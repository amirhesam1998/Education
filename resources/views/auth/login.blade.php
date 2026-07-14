<!doctype html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ورود به پنل مدیریت</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --brand-50: #f1f7f6;
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
            --danger: #e0574c;
            --info: #3a7bd5;

            --radius-sm: 10px;
            --radius-md: 14px;
            --radius-lg: 20px;

            --shadow-sm: 0 1px 2px rgba(20, 30, 40, .04);
            --shadow-md: 0 8px 24px -8px rgba(20, 30, 40, .12);
            --shadow-lg: 0 30px 70px -20px rgba(31, 96, 88, .28);
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            height: 100%;
        }

        body {
            background: var(--bg);
            color: var(--ink-900);
            font-family: 'Vazirmatn', Tahoma, Arial, sans-serif;
            font-size: .95rem;
            -webkit-font-smoothing: antialiased;
            margin: 0;
            overflow-x: hidden;
        }

        a {
            text-decoration: none;
        }

        ::selection {
            background: var(--brand-200);
            color: var(--brand-700);
        }

        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--ink-300);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--ink-500);
        }

        /* ===========================================================
           Decorative animated backdrop — built entirely from
           pseudo-elements on the page wrapper, no extra markup.
        =========================================================== */
        .auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            position: relative;
            isolation: isolate;
            overflow: hidden;
            background:
                radial-gradient(900px 480px at 15% -10%, rgba(47, 143, 131, .14), transparent),
                radial-gradient(700px 420px at 105% 8%, rgba(58, 123, 213, .10), transparent);
        }

        .auth-page::before,
        .auth-page::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            z-index: -1;
            filter: blur(2px);
            opacity: .55;
        }

        .auth-page::before {
            width: 420px;
            height: 420px;
            background: radial-gradient(circle at 35% 30%, var(--brand-200), transparent 70%);
            top: -140px;
            right: -120px;
            animation: drift-a 16s ease-in-out infinite;
        }

        .auth-page::after {
            width: 340px;
            height: 340px;
            background: radial-gradient(circle at 60% 40%, #cfe3fb, transparent 70%);
            bottom: -120px;
            left: -100px;
            animation: drift-b 18s ease-in-out infinite;
        }

        @keyframes drift-a {

            0%,
            100% {
                transform: translate(0, 0) scale(1);
            }

            50% {
                transform: translate(-24px, 26px) scale(1.06);
            }
        }

        @keyframes drift-b {

            0%,
            100% {
                transform: translate(0, 0) scale(1);
            }

            50% {
                transform: translate(20px, -18px) scale(1.08);
            }
        }

        @media (prefers-reduced-motion: reduce) {

            .auth-page::before,
            .auth-page::after {
                animation: none;
            }
        }

        /* Fine grain dot texture, subtle */
        .auth-page .grain {
            position: absolute;
            inset: 0;
            z-index: -1;
            background-image: radial-gradient(rgba(28, 36, 48, .045) 1px, transparent 1px);
            background-size: 22px 22px;
            mask-image: radial-gradient(700px 500px at 50% 20%, #000, transparent 75%);
        }

        /* ===========================================================
           Login card
        =========================================================== */
        .login-wrap {
            width: 100%;
            max-width: 420px;
            position: relative;
            z-index: 1;
        }

        .brand-block {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .6rem;
            margin-bottom: 1.5rem;
            text-align: center;
            animation: fade-drop .55s ease both;
        }

        .brand-mark {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            background: linear-gradient(150deg, var(--brand-500), var(--brand-700));
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.6rem;
            box-shadow: 0 12px 26px -10px rgba(31, 96, 88, .55);
            position: relative;
        }

        .brand-mark::after {
            content: "";
            position: absolute;
            inset: -6px;
            border-radius: 20px;
            border: 1.5px solid var(--brand-200);
            opacity: .6;
            animation: pulse-ring 2.6s ease-out infinite;
        }

        @keyframes pulse-ring {
            0% {
                transform: scale(.92);
                opacity: .55;
            }

            70% {
                transform: scale(1.14);
                opacity: 0;
            }

            100% {
                transform: scale(1.14);
                opacity: 0;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .brand-mark::after {
                animation: none;
                display: none;
            }
        }

        .brand-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--ink-900);
        }

        .brand-subtitle {
            font-size: .82rem;
            color: var(--ink-500);
        }

        .login-card {
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            background: var(--surface);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            position: relative;
            animation: card-in .55s cubic-bezier(.2, .8, .2, 1) .08s both;
        }

        /* top accent hairline on the card */
        .login-card::before {
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            left: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--brand-500), var(--info), var(--brand-500));
            background-size: 200% 100%;
            animation: sheen 6s linear infinite;
        }

        @media (prefers-reduced-motion: reduce) {
            .login-card::before {
                animation: none;
            }
        }

        @keyframes sheen {
            0% {
                background-position: 0% 0;
            }

            100% {
                background-position: 200% 0;
            }
        }

        @keyframes card-in {
            from {
                opacity: 0;
                transform: translateY(18px) scale(.98);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes fade-drop {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (prefers-reduced-motion: reduce) {

            .login-card,
            .brand-block {
                animation: none;
            }
        }

        .login-card .card-body {
            padding: 2rem 1.85rem;
        }

        .login-title {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: .25rem;
        }

        .login-hint {
            font-size: .82rem;
            color: var(--ink-500);
            margin-bottom: 1.5rem;
        }

        /* ===========================================================
           Alerts
        =========================================================== */
        .alert {
            border: none;
            border-radius: var(--radius-md);
            padding: .85rem 1rem;
            font-size: .84rem;
            background: #fcecea;
            color: var(--danger);
            display: flex;
            align-items: flex-start;
            gap: .5rem;
            animation: shake-in .4s ease;
        }

        @keyframes shake-in {
            0% {
                transform: translateX(0);
                opacity: 0;
            }

            25% {
                transform: translateX(-6px);
                opacity: 1;
            }

            50% {
                transform: translateX(5px);
            }

            75% {
                transform: translateX(-3px);
            }

            100% {
                transform: translateX(0);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .alert {
                animation: none;
            }
        }

        /* ===========================================================
           Form
        =========================================================== */
        .form-label {
            font-size: .82rem;
            font-weight: 600;
            color: var(--ink-700);
            margin-bottom: .4rem;
        }

        .input-group-modern {
            position: relative;
        }

        .input-group-modern .field-icon {
            position: absolute;
            top: 50%;
            right: .95rem;
            transform: translateY(-50%);
            color: var(--ink-300);
            font-size: 1.05rem;
            transition: color .15s ease;
            pointer-events: none;
        }

        .form-control {
            border-radius: var(--radius-sm);
            border: 1.5px solid var(--border);
            font-size: .9rem;
            padding: .75rem 2.6rem .75rem .95rem;
            background: var(--bg);
            color: var(--ink-900);
            transition: border-color .15s ease, background .15s ease, box-shadow .15s ease;
        }

        .form-control:focus {
            border-color: var(--brand-500);
            background: var(--surface);
            box-shadow: 0 0 0 .2rem rgba(47, 143, 131, .14);
        }

        .form-control:focus~.field-icon {
            color: var(--brand-500);
        }

        .password-toggle {
            position: absolute;
            top: 50%;
            right: .65rem;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--ink-500);
            font-size: 1.1rem;
            padding: .35rem;
            line-height: 1;
            cursor: pointer;
            border-radius: 8px;
            transition: color .15s ease, background .15s ease;
        }

        .password-toggle:hover {
            color: var(--brand-600);
            background: var(--brand-50);
        }

        .password-toggle:focus-visible {
            outline: 2px solid var(--brand-500);
            outline-offset: 2px;
        }

        .form-control.has-toggle {
            padding-inline-end: 2.7rem;
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .form-check-input {
            width: 1.1rem;
            height: 1.1rem;
            border: 1.5px solid var(--ink-300);
        }

        .form-check-input:checked {
            background-color: var(--brand-500);
            border-color: var(--brand-500);
        }

        .form-check-input:focus {
            box-shadow: 0 0 0 .2rem rgba(47, 143, 131, .14);
        }

        .form-check-label {
            font-size: .85rem;
            color: var(--ink-700);
        }

        .ltr {
            direction: ltr;
            text-align: left;
        }

        /* ===========================================================
           Button
        =========================================================== */
        .btn-primary {
            background: linear-gradient(135deg, var(--brand-500), var(--brand-700));
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: .92rem;
            padding: .8rem 1rem;
            box-shadow: 0 10px 22px -10px rgba(31, 96, 88, .55);
            transition: transform .15s ease, box-shadow .15s ease, filter .15s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-primary::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(120deg, transparent, rgba(255, 255, 255, .28), transparent);
            transform: translateX(-120%);
            transition: transform .5s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 28px -10px rgba(31, 96, 88, .6);
            filter: brightness(1.03);
        }

        .btn-primary:hover::after {
            transform: translateX(120%);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-primary .btn-spinner {
            display: none;
            width: 15px;
            height: 15px;
            border: 2px solid rgba(255, 255, 255, .5);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .7s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .btn-primary.is-loading .btn-spinner {
            display: inline-block;
        }

        .btn-primary.is-loading .btn-label {
            opacity: .85;
        }

        .login-footer {
            text-align: center;
            font-size: .76rem;
            color: var(--ink-500);
            margin-top: 1.5rem;
            animation: fade-drop .6s ease .15s both;
        }

        @media (max-width: 420px) {
            .login-card .card-body {
                padding: 1.6rem 1.3rem;
            }

            .brand-mark {
                width: 50px;
                height: 50px;
                font-size: 1.4rem;
            }
        }
    </style>
</head>

<body>
    <div class="auth-page">
        <div class="grain"></div>

        <div class="login-wrap">
            <div class="brand-block">
                <span class="brand-mark"><i class="ri-heart-pulse-line"></i></span>
                <div>
                    <div class="brand-title">سامانه رزرو مشاوره</div>
                    <div class="brand-subtitle">پنل مدیریت آموزشگاه</div>
                </div>
            </div>

            <div class="card login-card">
                <div class="card-body">
                    <h1 class="login-title">ورود به پنل مدیریت</h1>
                    <div class="login-hint">برای دسترسی به پنل، اطلاعات حساب خود را وارد کنید.</div>

                    @if($errors->any())
                        <div class="alert mb-3">
                            <i class="ri-error-warning-line mt-1"></i>
                            <div>
                                @foreach($errors->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <form method="post" action="{{ route('login.store') }}" id="loginForm">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">ایمیل</label>
                            <div class="input-group-modern">
                                <input type="email" name="email" value="{{ old('email') }}" class="form-control ltr"
                                    required autofocus placeholder="you@example.com">
                                <i class="ri-mail-line field-icon"></i>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">رمز عبور</label>
                            <div class="input-group-modern">
                                <input type="password" name="password" id="passwordField"
                                    class="form-control ltr has-toggle" required placeholder="••••••••">
                                <button type="button" class="password-toggle" id="togglePassword"
                                    aria-label="نمایش یا مخفی کردن رمز عبور" aria-pressed="false">
                                    <i class="ri-eye-line" id="togglePasswordIcon"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" name="remember" value="1" id="remember">
                            <label class="form-check-label" for="remember">مرا به خاطر بسپار</label>
                        </div>

                        <button class="btn btn-primary w-100" type="submit" id="loginSubmit">
                            <span class="btn-spinner"></span>
                            <span class="btn-label">ورود</span>
                        </button>
                    </form>
                </div>
            </div>

            <div class="login-footer">
                &copy; {{ date('Y') }} سامانه رزرو مشاوره — تمامی حقوق محفوظ است.
            </div>
        </div>
    </div>

    <script>
        (function () {
            const toggleBtn = document.getElementById('togglePassword');
            const toggleIcon = document.getElementById('togglePasswordIcon');
            const passwordField = document.getElementById('passwordField');

            toggleBtn?.addEventListener('click', function () {
                const isHidden = passwordField.type === 'password';
                passwordField.type = isHidden ? 'text' : 'password';
                toggleBtn.setAttribute('aria-pressed', String(isHidden));
                toggleIcon.className = isHidden ? 'ri-eye-off-line' : 'ri-eye-line';
            });

            const form = document.getElementById('loginForm');
            const submitBtn = document.getElementById('loginSubmit');

            form?.addEventListener('submit', function () {
                submitBtn.classList.add('is-loading');
                submitBtn.setAttribute('disabled', 'true');
            });
        })();
    </script>
</body>

</html>