<!doctype html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ورود به بهروزان</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            /* Behrozan palette */
            --brand-500: #8CC63F;
            /* primary lime green */
            --brand-600: #6BBF3A;
            /* secondary fresh green */
            --brand-700: #2E4E24;
            /* deep green — outline / shadow */

            --icon-green: #4CAF50;
            --icon-gray: #8A8A8A;
            --placeholder: #8A8F98;

            --ink-900: #2B2B2B;
            /* charcoal text */
            --ink-500: #8A8F98;

            --bg: #FAF8F2;
            /* warm off-white */
            --surface: #ffffff;
            --border: #E6E8EC;
            --glow: #E8F5D8;
            /* soft glow green */

            --danger: #e0574c;
            --success: #1f9d6e;

            --radius-md: 16px;
            --shadow-btn: 0 8px 24px rgba(76, 175, 80, .28);
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
            font-weight: 500;
            font-size: .95rem;
            -webkit-font-smoothing: antialiased;
            margin: 0;
            overflow-x: hidden;
        }

        a {
            text-decoration: none;
        }

        ::selection {
            background: var(--glow);
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
            background: #ddd8c8;
            border-radius: 10px;
        }

        /* ===========================================================
           Backdrop — Behrozan background texture
        =========================================================== */
        .auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem 2rem;
            position: relative;
            overflow: hidden;
            background: var(--bg) url('{{ asset('images/behrozan-bg-texture.webp') }}') center top / cover no-repeat;
        }

        /* ===========================================================
           Brand — logo + greeting
        =========================================================== */
        .login-wrap {
            width: 100%;
            max-width: 400px;
            position: relative;
            z-index: 1;
        }

        .brand-block {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .6rem;
            margin-bottom: 2.75rem;
            text-align: center;
            animation: fade-drop .55s ease both;
        }

        .brand-logo-img {
            width: min(310px, 78vw);
            height: auto;
            display: block;
        }

        .brand-greeting {
            font-size: 1.05rem;
            font-weight: 500;
            color: var(--ink-900);
        }

        @media (prefers-reduced-motion: reduce) {
            .brand-block {
                animation: none;
            }
        }

        /* ===========================================================
           Alerts
        =========================================================== */
        .alert {
            border: none;
            border-radius: 14px;
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
           Form — inputs
        =========================================================== */
        form {
            animation: fade-drop .55s ease .08s both;
        }

        @media (prefers-reduced-motion: reduce) {
            form {
                animation: none;
            }
        }

        @keyframes fade-drop {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .input-group-modern {
            position: relative;
        }

        .input-group-modern .field-icon {
            position: absolute;
            top: 50%;
            right: 1.1rem;
            transform: translateY(-50%);
            height: 22px;
            width: auto;
            pointer-events: none;
        }

        .form-control {
            width: 100%;
            height: 56px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--ink-900);
            font-size: .95rem;
            font-family: inherit;
            padding: 0 3.2rem 0 1.1rem;
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        .form-control::placeholder {
            color: var(--placeholder);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--brand-500);
            box-shadow: 0 0 0 .2rem rgba(140, 198, 63, .18);
        }

        .form-control.has-toggle {
            padding-left: 3rem;
        }

        .password-toggle {
            position: absolute;
            top: 50%;
            left: .55rem;
            transform: translateY(-50%);
            background: none;
            border: none;
            padding: .35rem;
            line-height: 1;
            cursor: pointer;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background .15s ease, opacity .15s ease;
        }

        .password-toggle img {
            height: 20px;
            width: auto;
        }

        .password-toggle[aria-pressed="true"] img {
            opacity: .6;
        }

        .password-toggle:hover {
            background: var(--glow);
        }

        .password-toggle:focus-visible {
            outline: 2px solid var(--brand-500);
            outline-offset: 2px;
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .form-check-input {
            width: 1.1rem;
            height: 1.1rem;
            border: 1.5px solid var(--border);
            accent-color: var(--brand-500);
        }

        .form-check-input:checked {
            background-color: var(--brand-500);
            border-color: var(--brand-500);
        }

        .form-check-input:focus {
            box-shadow: 0 0 0 .2rem rgba(140, 198, 63, .18);
        }

        .form-check-label {
            font-size: .85rem;
            color: var(--ink-900);
        }

        /* ===========================================================
           Primary button
        =========================================================== */
        .btn-primary {
            width: 100%;
            height: 56px;
            background: linear-gradient(135deg, var(--brand-500), var(--brand-600));
            border: none;
            border-radius: var(--radius-md);
            color: #fff;
            font-weight: 700;
            font-size: .98rem;
            box-shadow: var(--shadow-btn);
            transition: transform .15s ease, box-shadow .15s ease, filter .15s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            cursor: pointer;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            filter: brightness(1.03);
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

        /* ===========================================================
           Terms / privacy helper text (currently disabled, see markup)
        =========================================================== */
        .login-terms {
            text-align: center;
            font-size: 13px;
            color: var(--placeholder);
            margin-top: 1.25rem;
        }

        .login-terms a {
            color: var(--icon-green);
            font-weight: 600;
        }

        @media (max-width: 380px) {
            .brand-block {
                margin-bottom: 1.5rem;
            }
        }

        .direction-rtl {
            direction: rtl;
            text-align: right;
        }
    </style>
</head>

<body>
    <div class="auth-page">
        <div class="login-wrap">
            <div class="brand-block">
                <img src="{{ asset('images/behrozan-logo.webp') }}" alt="بهروزان" class="brand-logo-img">
                <div class="brand-greeting">    پنل مدیریت اموزشگاه بهروزان </div>
            </div>

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

            @if(session('success'))
                <div class="alert mb-3" style="background:#edf8f4;color:var(--success);">
                    <i class="ri-checkbox-circle-line mt-1"></i>
                    <div>{{ session('success') }}</div>
                </div>
            @endif

            <form method="post" action="{{ route('login.store') }}" id="loginForm">
                @csrf
                <div class="mb-3">
                    <div class="input-group-modern">
                        <input type="tel" name="phone" value="{{ old('phone') }}" class="form-control direction-rtl"
                            required autofocus placeholder="شماره تلفن" inputmode="tel" autocomplete="tel">
                        <img src="{{ asset('images/icon-phone.webp') }}" alt="" class="field-icon">
                    </div>
                </div>

                <div class="mb-3">
                    <div class="input-group-modern">
                        <input type="password" name="password" id="passwordField" class="form-control has-toggle"
                            required placeholder="رمز عبور" autocomplete="current-password">
                        <button type="button" class="password-toggle" id="togglePassword"
                            aria-label="نمایش یا مخفی کردن رمز عبور" aria-pressed="false">
                            <img src="{{ asset('images/icon-eye.webp') }}" alt="">
                        </button>
                        <img src="{{ asset('images/icon-lock.webp') }}" alt="" class="field-icon">
                    </div>
                </div>

                {{-- <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="remember" value="1" id="remember">
                    <label class="form-check-label" for="remember">مرا به خاطر بسپار</label>
                </div>--}}

                <button class="btn-primary" type="submit" id="loginSubmit">
                    <span class="btn-spinner"></span>
                    <span class="btn-label">ورود</span>
                </button>
            </form>

            {{-- ثبت‌نام حساب جدید — فعلاً غیرفعال است، عمداً کامنت شده
            <div class="text-center mt-3 small">
                <a href="{{ route('register') }}">ثبت‌نام حساب جدید</a>
            </div>
            --}}

            {{-- متن قوانین و حریم خصوصی — فعلاً غیرفعال است، عمداً کامنت شده
            <div class="login-terms">
                ورود شما به معنی پذیرش <a href="#">قوانین</a> و <a href="#">حریم خصوصی</a> است
            </div>
            --}}
        </div>
    </div>

    <script>
        (function () {
            const toggleBtn = document.getElementById('togglePassword');
            const passwordField = document.getElementById('passwordField');

            toggleBtn?.addEventListener('click', function () {
                const isHidden = passwordField.type === 'password';
                passwordField.type = isHidden ? 'text' : 'password';
                toggleBtn.setAttribute('aria-pressed', String(isHidden));
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