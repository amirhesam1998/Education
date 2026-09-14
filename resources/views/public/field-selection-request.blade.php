<!doctype html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>درخواست تایم انتخاب رشته | بهروزان</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            /* Behrozan palette */
            --brand-500: #8CC63F;
            --brand-600: #6BBF3A;
            --brand-700: #2E4E24;

            --ink-900: #2B2B2B;
            --ink-600: #424242;
            --ink-400: #8A8A8A;
            --placeholder: #B2B2B2;

            --bg: #FAF8F2;
            --surface: #FFFFFF;
            --border: #E6E6E6;
            --glow: #E8F5D8;

            --radius-md: 16px;
            --radius-lg: 22px;
            --shadow-card:
                0 8px 20px rgba(46, 78, 36, .045),
                0 20px 50px rgba(46, 78, 36, .06);
            --shadow-btn: 0 8px 24px rgba(76, 175, 80, .28);
        }

        * {
            box-sizing: border-box;
        }

        [hidden] {
            display: none !important;
        }

        html,
        body {
            height: 100%;
        }

        /* Reserve the scrollbar gutter permanently, so locking the page
           behind the modal cannot change the layout width. The browser
           decides which side the gutter sits on — no guessing in JS. */
        html {
            scrollbar-gutter: stable;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--ink-900);
            font-family: 'Vazirmatn', Tahoma, Arial, sans-serif;
            font-weight: 500;
            font-size: .95rem;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
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
        .request-page {
            min-height: 100vh;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 2.5rem 1rem 3rem;
            position: relative;
            overflow: hidden;
            background: var(--bg) url("{{ asset('images/behrozan-bg-texture.webp') }}") center top / cover no-repeat;
        }

        .request-wrap {
            width: 100%;
            max-width: 620px;
            position: relative;
            z-index: 1;
        }

        /* ===========================================================
           Brand
        =========================================================== */
        .brand-block {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .75rem;
            margin-bottom: 2rem;
            text-align: center;
            animation: fade-drop .55s ease both;
        }

        .brand-logo-img {
            width: min(300px, 74vw);
            height: auto;
            display: block;
        }

        .brand-greeting {
            font-size: 1.05rem;
            font-weight: 600;
            color: var(--ink-900);
        }

        .brand-sub {
            font-size: .84rem;
            font-weight: 400;
            color: var(--ink-400);
            line-height: 1.9;
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

        @media (prefers-reduced-motion: reduce) {

            .brand-block,
            .request-card,
            .success-modal__card {
                animation: none !important;
            }
        }

        /* ===========================================================
           Card
        =========================================================== */
        .request-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-card);
            padding: 1.6rem 1.4rem;
            animation: fade-drop .55s ease .08s both;
        }

        .card-title {
            display: flex;
            align-items: center;
            gap: .5rem;
            margin: 0 0 1.35rem;
            padding-bottom: .9rem;
            border-bottom: 1px solid var(--border);
            font-size: .98rem;
            font-weight: 700;
            color: var(--ink-900);
        }

        .card-title i {
            color: var(--brand-600);
            font-size: 1.15rem;
        }

        /* ===========================================================
           Form grid
        =========================================================== */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .field {
            min-width: 0;
        }

        .field--full {
            grid-column: 1 / -1;
        }

        .field__label {
            display: block;
            margin-bottom: 7px;
            color: var(--ink-600);
            font-size: 12px;
            line-height: 1.7;
            font-weight: 550;
        }

        .field__hint {
            font-size: 11px;
            font-weight: 400;
            color: var(--ink-400);
        }

        .field-input {
            width: 100%;
            height: 48px;
            padding: 0 13px;
            border: 1px solid var(--border);
            border-radius: 14px;
            outline: none;
            background: #fff;
            color: var(--ink-900);
            font-family: inherit;
            font-size: 13px;
            font-weight: 400;
            transition: border-color .18s ease, box-shadow .18s ease;
        }

        .field-input::placeholder {
            color: var(--placeholder);
        }

        .field-input:focus {
            border-color: var(--brand-600);
            box-shadow: 0 0 0 4px rgba(140, 198, 63, .12);
        }

        .field-input.ltr {
            direction: ltr;
            text-align: left;
        }

        /* invalid state + inline error */
        .field-input.is-invalid {
            border-color: #D98B8B;
            background: #FFFAFA;
        }

        .field-input.is-invalid:focus {
            border-color: #C84A4A;
            box-shadow: 0 0 0 4px rgba(200, 74, 74, .12);
        }

        .field-error {
            margin: 6px 2px 0;
            font-size: 11px;
            font-weight: 500;
            line-height: 1.8;
            color: #C84A4A;
        }

        .choice-grid.is-invalid .choice__box {
            border-color: #E0BABA;
        }

        /* ===========================================================
           Captcha
        =========================================================== */
        .captcha-box {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 10px 8px 8px;
            border: 1px solid var(--border);
            border-radius: 14px;
            background: #FCFDFA;
            transition: border-color .18s ease, box-shadow .18s ease, background-color .18s ease;
            direction: ltr;
        }

        .captcha-box:focus-within {
            border-color: var(--brand-600);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(140, 198, 63, .12);
        }

        .captcha-box.is-invalid {
            border-color: #D98B8B;
            background: #FFFAFA;
        }

        .captcha-box__icon {
            flex: 0 0 auto;
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: var(--glow);
            color: var(--brand-700);
            font-size: 1.05rem;
        }

        /* the sum itself — deliberately not selectable, so it cannot be copied
           straight into the answer box */
        .captcha-box__question {
            flex: 0 0 auto;
            padding: 8px 14px;
            border-radius: 11px;
            background: var(--brand-700);
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: .5px;
            white-space: nowrap;
            user-select: none;
            -webkit-user-select: none;
        }

        .captcha-box__input {
            flex: 1 1 auto;
            min-width: 0;
            height: 40px;
            padding: 0 12px;
            border: 0;
            border-radius: 11px;
            outline: none;
            background: transparent;
            color: var(--ink-900);
            font-family: inherit;
            font-size: 14px;
            direction: rtl;
            font-weight: 600;
        }

        .captcha-box__input::placeholder {
            color: var(--placeholder);
            font-weight: 400;
        }

        /* strip the number spinners — they make no sense for an answer box */
        .captcha-box__input::-webkit-outer-spin-button,
        .captcha-box__input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .captcha-box__input[type="number"] {
            -moz-appearance: textfield;
            appearance: textfield;
        }

        @media (max-width: 380px) {
            .captcha-box__question { padding: 8px 10px; font-size: 14px; }
            .captcha-box__icon { display: none; }
        }

        /* conditional reveal (سایر) */
        .field-reveal {
            margin-top: 10px;
            animation: reveal-down .22s ease both;
        }

        @keyframes reveal-down {
            from {
                opacity: 0;
                transform: translateY(-6px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .field-reveal {
                animation: none;
            }
        }

        /* ===========================================================
           Choice chips — used by both "منطقه" (radio) and "نوع کنکور" (checkbox)
        =========================================================== */
        .choice-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(104px, 1fr));
            gap: 8px;
        }

        /* longer labels (سهمیه خاص) need roomier chips */
        .choice-grid--wide {
            /* grid-template-columns: repeat(auto-fill, minmax(158px, 1fr)); */
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .choice {
            position: relative;
            min-width: 0;
            margin: 0;
            cursor: pointer;
        }

        .choice input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .choice__box {
            min-height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8px 10px;
            border: 1px solid var(--border);
            border-radius: 13px;
            background: #fff;
            color: #555;
            font-size: 12.5px;
            font-weight: 500;
            text-align: center;
            transition: background-color .18s ease, color .18s ease, border-color .18s ease, box-shadow .18s ease;
        }

        .choice:hover .choice__box {
            border-color: var(--brand-500);
            background: #fcfdfa;
        }

        .choice input:checked+.choice__box {
            color: var(--brand-700);
            background: var(--glow);
            border-color: var(--brand-500);
            box-shadow: inset 0 0 0 1px rgba(140, 198, 63, .15);
        }

        .choice input:focus-visible+.choice__box {
            border-color: var(--brand-600);
            box-shadow: 0 0 0 4px rgba(140, 198, 63, .12);
        }

        /* ===========================================================
           File dropzone
        =========================================================== */
        .file-drop {
            position: relative;
            border: 1.5px dashed #D5D9D0;
            border-radius: var(--radius-md);
            background: #FCFDFA;
            padding: 1.35rem 1rem;
            text-align: center;
            cursor: pointer;
            transition: border-color .18s ease, background-color .18s ease, box-shadow .18s ease;
        }

        .file-drop:hover,
        .file-drop:focus-visible {
            border-color: var(--brand-500);
            background: #fff;
            outline: none;
        }

        .file-drop.is-dragging {
            border-color: var(--brand-600);
            background: var(--glow);
            box-shadow: 0 0 0 4px rgba(140, 198, 63, .12);
        }

        .file-drop.has-file {
            border-style: solid;
            border-color: var(--brand-500);
            background: #fff;
            padding: .85rem;
            text-align: right;
            cursor: default;
        }

        /* the real input stays reachable for a11y but never visible */
        .file-drop__input {
            position: absolute;
            width: 1px;
            height: 1px;
            opacity: 0;
            pointer-events: none;
        }

        .file-drop__empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .4rem;
            pointer-events: none;
        }

        .file-drop__icon {
            width: 46px;
            height: 46px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: var(--glow);
            color: var(--brand-700);
            font-size: 1.35rem;
        }

        .file-drop__title {
            font-size: 12.5px;
            font-weight: 600;
            color: var(--ink-600);
        }

        .file-drop__title span {
            color: var(--brand-700);
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        .file-drop__meta {
            font-size: 11px;
            font-weight: 400;
            color: var(--ink-400);
        }

        /* preview */
        .file-preview {
            display: flex;
            align-items: center;
            gap: .75rem;
        }

        .file-preview__thumb {
            flex: 0 0 auto;
            width: 56px;
            height: 56px;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: var(--glow) center / cover no-repeat;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--brand-700);
            font-size: 1.4rem;
            overflow: hidden;
        }

        .file-preview__info {
            flex: 1 1 auto;
            min-width: 0;
        }

        .file-preview__name {
            display: block;
            font-size: 12.5px;
            font-weight: 600;
            color: var(--ink-900);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .file-preview__size {
            font-size: 11px;
            font-weight: 400;
            color: var(--ink-400);
        }

        .file-preview__actions {
            flex: 0 0 auto;
            display: flex;
            gap: .35rem;
        }

        .file-preview__btn {
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: #fff;
            color: var(--ink-400);
            font-size: 1rem;
            cursor: pointer;
            transition: color .15s ease, border-color .15s ease, background .15s ease;
        }

        .file-preview__btn:hover {
            color: var(--brand-700);
            border-color: var(--brand-500);
            background: var(--glow);
        }

        .file-preview__btn--danger:hover {
            color: #C84A4A;
            border-color: #E7B6B6;
            background: #FFF0F0;
        }

        /* ===========================================================
           Submit
        =========================================================== */
        .btn-primary {
            width: 100%;
            height: 56px;
            margin-top: 1.5rem;
            background: linear-gradient(135deg, var(--brand-500), var(--brand-600));
            border: none;
            border-radius: var(--radius-md);
            color: #fff;
            font-family: inherit;
            font-weight: 700;
            font-size: .98rem;
            box-shadow: var(--shadow-btn);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            cursor: pointer;
            transition: transform .15s ease, filter .15s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            filter: brightness(1.03);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        /* ===========================================================
           Footer note
        =========================================================== */
        .request-footer {
            margin-top: 1.5rem;
            padding: 1.15rem 1.25rem;
            border-radius: var(--radius-md);
            background: rgba(255, 255, 255, .72);
            border: 1px solid var(--border);
            text-align: center;
            line-height: 2.1;
        }

        .request-footer p {
            margin: 0;
            font-size: .84rem;
            color: var(--ink-600);
        }

        .request-footer .thanks {
            margin-top: .35rem;
            font-weight: 700;
            color: var(--brand-700);
        }

        /* ===========================================================
           Signature — shared by footer and modal
        =========================================================== */
        .signature {
            margin-top: 1rem;
            padding-top: .95rem;
            border-top: 1px dashed var(--border);
            line-height: 2;
        }

        .signature p {
            margin: 0;
        }

        .signature__thanks {
            font-size: .82rem;
            font-weight: 500;
            color: var(--ink-400);
        }

        .signature__org {
            font-size: .88rem;
            font-weight: 700;
            color: var(--brand-700);
        }

        .signature__person {
            font-size: .82rem;
            font-weight: 500;
            color: var(--ink-600);
        }

        .signature__phone {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            margin-top: .5rem;
            padding: .42rem .95rem;
            border-radius: 999px;
            background: var(--glow);
            color: var(--brand-700);
            font-size: .86rem;
            font-weight: 700;
            text-decoration: none;
            transition: filter .15s ease;
        }

        .signature__phone:hover {
            filter: brightness(.96);
        }

        .ltr-num {
            direction: ltr;
            unicode-bidi: embed;
        }

        .signature--modal {
            margin-top: 1.35rem;
        }

        /* ===========================================================
           Success modal
        =========================================================== */
        /* The tint and the blur are transitioned directly.
           Fading the whole layer with `opacity` made the blur land at full
           strength on the first frame while the dark tint was still
           transparent — that was the bright flash on open. */
        .success-modal {
            position: fixed;
            inset: 0;
            z-index: 1080;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: rgba(30, 40, 24, 0);
            backdrop-filter: blur(0px);
            -webkit-backdrop-filter: blur(0px);
            /* stays click-through while closing so it never traps the page */
            pointer-events: none;
            transition:
                background-color .22s ease,
                backdrop-filter .22s ease,
                -webkit-backdrop-filter .22s ease;
        }

        .success-modal.is-open {
            background: rgba(30, 40, 24, .5);
            backdrop-filter: blur(7px);
            -webkit-backdrop-filter: blur(7px);
            pointer-events: auto;
        }

        .success-modal__card {
            width: 100%;
            max-width: 420px;
            background: var(--surface);
            border-radius: var(--radius-lg);
            box-shadow: 0 24px 70px rgba(46, 78, 36, .25);
            padding: 2rem 1.5rem 1.5rem;
            text-align: center;
            opacity: 0;
            transform: translateY(14px) scale(.96);
            transition:
                opacity .24s ease,
                transform .28s cubic-bezier(.2, .9, .3, 1.2);
        }

        .success-modal.is-open .success-modal__card {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        @media (prefers-reduced-motion: reduce) {

            .success-modal,
            .success-modal__card {
                transition: none;
            }
        }

        .success-modal__icon {
            width: 74px;
            height: 74px;
            margin: 0 auto 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: var(--glow);
            color: var(--brand-600);
            font-size: 2.3rem;
            box-shadow: 0 0 0 8px rgba(140, 198, 63, .12);
        }

        .success-modal__title {
            margin: 0 0 .6rem;
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--ink-900);
        }

        .success-modal__text {
            margin: 0;
            font-size: .86rem;
            font-weight: 400;
            line-height: 2.2;
            color: var(--ink-600);
        }

        .success-modal__card .btn-primary {
            margin-top: 1.6rem;
            height: 50px;
        }

        /* ===========================================================
           Responsive
        =========================================================== */
        @media (max-width: 575.98px) {
            .request-page {
                padding: 1.5rem .85rem 2.5rem;
            }

            .request-card {
                padding: 1.25rem 1rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .choice-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="request-page">
        <div class="request-wrap">

            <div class="brand-block">
                <img src="{{ asset('images/behrozan-logo.webp') }}" alt="بهروزان" class="brand-logo-img">
                <div class="brand-greeting">درخواست تایم انتخاب رشته</div>
                <div class="brand-sub">لطفاً اطلاعات زیر را تکمیل کنید تا همکاران ما با شما تماس بگیرند.</div>
            </div>

            <form class="request-card" id="requestForm" method="post" action="{{ route('public.reservation-requests.store') }}">
                @csrf

                <div class="card-title">
                    <i class="ri-graduation-cap-line"></i>
                    اطلاعات دانش‌آموز
                </div>

                <div class="form-grid">

                    <div class="field">
                        <label class="field__label" for="full_name">نام و نام خانوادگی</label>
                        <input type="text" id="full_name" name="full_name" class="field-input" data-validate="name"
                            maxlength="255" value="{{ old('full_name') }}" placeholder="نام و نام خانوادگی دانش‌آموز" autocomplete="name" spellcheck="false" required>
                        @error('full_name')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="field">
                        <label class="field__label" for="major">رشته تحصیلی</label>
                        <select id="major" name="major" class="field-input">
                            <option value="">انتخاب کنید</option>
                            @foreach(['تجربی', 'انسانی', 'ریاضی', 'هنر', 'زبان'] as $major)
                                <option value="{{ $major }}" @selected(old('major') === $major)>{{ $major }}</option>
                            @endforeach
                        </select>
                        @error('major')<p class="field-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="field">
                        <label class="field__label" for="student_phone">شماره تماس دانش‌آموز</label>
                        <input type="tel" id="student_phone" name="student_phone" class="field-input ltr"
                            data-validate="phone" inputmode="numeric" maxlength="11" placeholder="09xxxxxxxxx"
                            value="{{ old('student_phone', old('phone_1')) }}" autocomplete="tel" required>
                        @error('phone_1')<p class="field-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="field">
                        <label class="field__label" for="parent_phone">شماره تماس اولیا</label>
                        <input type="tel" id="parent_phone" name="parent_phone" class="field-input ltr"
                            data-validate="phone" inputmode="numeric" maxlength="11" placeholder="09xxxxxxxxx" value="{{ old('parent_phone', old('phone_2')) }}">
                        @error('phone_2')<p class="field-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="field field--full">
                        <label class="field__label">
                            نوع کنکور
                            <span class="field__hint">(می‌توانید چند مورد را انتخاب کنید)</span>
                        </label>
                        <div class="choice-grid">
                            @foreach($examTypes as $examType)<label class="choice"><input type="checkbox" name="exam_type[]" value="{{ $examType }}" @checked(in_array($examType, (array) old('exam_type', []), true))><span class="choice__box">{{ $examType }}</span></label>@endforeach
                        </div>
                        @error('exam_type')<p class="field-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="field field--full">
                        <label class="field__label">سهمیه منطقه <span class="field__hint">(یک مورد را انتخاب کنید)</span></label>
                        <div class="choice-grid">
                            @foreach(['منطقه یک', 'منطقه دو', 'منطقه سه', 'اطلاعی ندارم'] as $region)<label class="choice"><input type="radio" name="region" value="{{ $region }}" @checked(old('region') === $region)><span class="choice__box">{{ $region }}</span></label>@endforeach
                        </div>
                        @error('region')<p class="field-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="field field--full">
                        <label class="field__label">آیا دارای سهمیه خاص هستید؟ <span class="field__hint">(یک مورد را انتخاب کنید)</span></label>
                        <div class="choice-grid choice-grid--wide">
                            @foreach([' سهمیه خاص ندارم', ' ۵٪', ' ۲۵٪', 'سایر'] as $quota)<label class="choice"><input type="radio" name="special_quota" value="{{ $quota }}" data-quota-other @checked(old('special_quota') === $quota)><span class="choice__box">{{ $quota }}</span></label>@endforeach
                        </div>
                        <div class="field-reveal" data-quota-other-wrap hidden>
                            <label class="field__label" for="special_quota_other">عنوان سهمیه را وارد کنید</label>
                            <input type="text" id="special_quota_other" name="special_quota_other" class="field-input" maxlength="60" value="{{ old('special_quota_other') }}" placeholder="عنوان سهمیه">
                            @error('special_quota_other')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        @error('special_quota')<p class="field-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="field field--full">
                        <label class="field__label" for="captcha">
                            عبارت امنیتی
                            <span class="field__hint">(برای اطمینان از اینکه ربات نیستید)</span>
                        </label>

                        <div class="captcha-box @error('captcha') is-invalid @enderror">
                            <span class="captcha-box__icon"><i class="ri-shield-check-line"></i></span>

                            <span class="captcha-box__question ltr-num" aria-hidden="true">{{ $captchaQuestion }}</span>

                            <input type="number" id="captcha" name="captcha" class="captcha-box__input ltr"
                                   inputmode="numeric" autocomplete="off" placeholder="پاسخ"
                                   aria-label="پاسخ عبارت امنیتی {{ $captchaQuestion }}" required>
                        </div>

                        @error('captcha')<p class="field-error">{{ $message }}</p>@enderror
                    </div>

                </div>

                <button type="submit" class="btn-primary">
                    <i class="ri-send-plane-line"></i>
                    ثبت درخواست
                </button>

            </form>

            <div class="request-footer">
                <p>پس از ثبت فرم، برای رزرو تایم انتخاب رشته با شما تماس گرفته خواهد شد.</p>

                <div class="signature">
                    <p class="signature__thanks">با تشکر از شما</p>
                    <p class="signature__org">مرکز تخصصی مشاوره و انتخاب رشته بهروزان</p>
                    <p class="signature__person">مشاور: علیرضا مرادی</p>
                    <a class="signature__phone" href="tel:09111354626">
                        <i class="ri-phone-line"></i>
                        <span class="ltr-num">۰۹۱۱۱۳۵۴۶۲۶</span>
                    </a>
                </div>
            </div>

        </div>
    </div>

    {{-- Success modal --}}
    <div class="success-modal {{ session('success') ? 'is-open' : '' }}" id="successModal" @unless(session('success')) hidden @endunless>
        <div class="success-modal__card" role="dialog" aria-modal="true" aria-labelledby="successTitle"
            aria-describedby="successText">
            <div class="success-modal__icon"><i class="ri-check-line"></i></div>
            <h2 class="success-modal__title" id="successTitle">دانش‌آموز گرامی</h2>
            <p class="success-modal__text" id="successText">
                {{ session('success', 'درخواست شما با موفقیت ثبت شد. آموزشگاه پس از بررسی با شما تماس خواهد گرفت.') }}
            </p>

            <div class="signature signature--modal">
                <p class="signature__thanks">با تشکر از شما</p>
                <p class="signature__org">مرکز تخصصی مشاوره و انتخاب رشته بهروزان</p>
                <p class="signature__person">مشاور: علیرضا مرادی</p>
                <a class="signature__phone" href="tel:09111354626">
                    <i class="ri-phone-line"></i>
                    <span class="ltr-num">۰۹۱۱۱۳۵۴۶۲۶</span>
                </a>
            </div>

            <button type="button" class="btn-primary" data-modal-close>باشه</button>
        </div>
    </div>

    <script>
        (function () {
            'use strict';

            /* -------------------------------------------------
               File dropzone + preview
               (the کارنامه field is optional in the markup —
                everything below is skipped when it is absent)
            ------------------------------------------------- */
            initDropzone();

            function initDropzone() {
            const drop = document.getElementById('fileDrop');
            const input = document.getElementById('report_card');
            if (!drop || !input) return;
            const empty = drop.querySelector('[data-drop-empty]');
            const preview = drop.querySelector('[data-drop-preview]');
            const thumb = drop.querySelector('[data-preview-thumb]');
            const nameEl = drop.querySelector('[data-preview-name]');
            const sizeEl = drop.querySelector('[data-preview-size]');
            const openBtn = drop.querySelector('[data-preview-open]');
            const removeBtn = drop.querySelector('[data-preview-remove]');

            let objectUrl = null;

            const faDigits = (value) => String(value).replace(/\d/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[d]);

            function humanSize(bytes) {
                if (bytes < 1024) return faDigits(bytes) + ' بایت';
                if (bytes < 1024 * 1024) return faDigits((bytes / 1024).toFixed(0)) + ' کیلوبایت';
                return faDigits((bytes / (1024 * 1024)).toFixed(1)) + ' مگابایت';
            }

            function releaseUrl() {
                if (objectUrl) {
                    URL.revokeObjectURL(objectUrl);
                    objectUrl = null;
                }
            }

            function clearFile() {
                releaseUrl();
                input.value = '';
                thumb.style.backgroundImage = '';
                thumb.innerHTML = '';
                drop.classList.remove('has-file');
                preview.hidden = true;
                empty.hidden = false;
            }

            function showFile(file) {
                releaseUrl();
                objectUrl = URL.createObjectURL(file);

                nameEl.textContent = file.name;
                sizeEl.textContent = humanSize(file.size);

                if (file.type.startsWith('image/')) {
                    thumb.innerHTML = '';
                    thumb.style.backgroundImage = 'url("' + objectUrl + '")';
                } else {
                    thumb.style.backgroundImage = '';
                    thumb.innerHTML = '<i class="ri-file-pdf-2-line"></i>';
                }

                empty.hidden = true;
                preview.hidden = false;
                drop.classList.add('has-file');
            }

            function acceptFile(file) {
                if (!file) return;
                if (!file.type.startsWith('image/') && file.type !== 'application/pdf') return;

                const transfer = new DataTransfer();
                transfer.items.add(file);
                input.files = transfer.files;
                showFile(file);
            }

            drop.addEventListener('click', function (event) {
                if (event.target.closest('.file-preview__actions')) return;
                if (drop.classList.contains('has-file')) return;
                input.click();
            });

            drop.addEventListener('keydown', function (event) {
                if (drop.classList.contains('has-file')) return;
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    input.click();
                }
            });

            input.addEventListener('change', function () {
                const file = input.files && input.files[0];
                file ? showFile(file) : clearFile();
            });

            ['dragenter', 'dragover'].forEach(function (type) {
                drop.addEventListener(type, function (event) {
                    event.preventDefault();
                    drop.classList.add('is-dragging');
                });
            });

            ['dragleave', 'drop'].forEach(function (type) {
                drop.addEventListener(type, function (event) {
                    event.preventDefault();
                    drop.classList.remove('is-dragging');
                });
            });

            drop.addEventListener('drop', function (event) {
                acceptFile(event.dataTransfer && event.dataTransfer.files[0]);
            });

            openBtn.addEventListener('click', function () {
                if (objectUrl) window.open(objectUrl, '_blank', 'noopener');
            });

            removeBtn.addEventListener('click', clearFile);
            }

       
            const FA_DIGITS = '۰۱۲۳۴۵۶۷۸۹';

            function toLatinDigits(value) {
                return String(value)
                    .replace(/[۰-۹]/g, (d) => String('۰۱۲۳۴۵۶۷۸۹'.indexOf(d)))
                    .replace(/[٠-٩]/g, (d) => String('٠١٢٣٤٥٦٧٨٩'.indexOf(d)));
            }

            // Persian/Arabic letters, space and ZWNJ only.
            // Every digit, latin letter, punctuation and angle bracket is dropped.
            const NAME_STRIP = /[^\p{Script=Arabic}‌ ]|\p{Nd}/gu;
            const NAME_VALID = /^[\p{Script=Arabic}‌]+(?: [\p{Script=Arabic}‌]+)*$/u;

            // Quota title: letters + digits + a few harmless separators.
            const QUOTA_STRIP = /[^\p{Script=Arabic}\p{Nd}‌ ()٪%.‏-]/gu;

            const PHONE_VALID = /^09\d{9}$/;

            function sanitize(el, stripRe) {
                const caret = el.selectionStart;
                const raw = el.value;
                const cleaned = raw.replace(stripRe, '').replace(/ {2,}/g, ' ');
                if (cleaned === raw) return;

                const kept = raw.slice(0, caret).replace(stripRe, '').replace(/ {2,}/g, ' ').length;
                el.value = cleaned;
                try {
                    el.setSelectionRange(kept, kept);
                } catch (error) {
                    /* some input types refuse setSelectionRange — harmless */
                }
            }

            function errorSlot(el) {
                return document.querySelector('[data-error-for="' + el.id + '"]');
            }

            function setError(el, message) {
                el.classList.add('is-invalid');
                el.setAttribute('aria-invalid', 'true');
                const slot = errorSlot(el);
                if (slot) {
                    slot.textContent = message;
                    slot.hidden = false;
                }
            }

            function clearError(el) {
                el.classList.remove('is-invalid');
                el.removeAttribute('aria-invalid');
                const slot = errorSlot(el);
                if (slot) {
                    slot.textContent = '';
                    slot.hidden = true;
                }
            }

            document.querySelectorAll('[data-validate="name"]').forEach(function (el) {
                el.addEventListener('input', function () {
                    sanitize(el, NAME_STRIP);
                    clearError(el);
                });
                el.addEventListener('blur', function () {
                    el.value = el.value.trim();
                });
            });

            document.querySelectorAll('[data-validate="phone"]').forEach(function (el) {
                el.addEventListener('input', function () {
                    const caret = el.selectionStart;
                    const cleaned = toLatinDigits(el.value).replace(/\D/g, '').slice(0, 11);
                    if (cleaned !== el.value) {
                        el.value = cleaned;
                        try {
                            el.setSelectionRange(Math.min(caret, cleaned.length), Math.min(caret, cleaned.length));
                        } catch (error) {
                            /* ignore */
                        }
                    }
                    // the "must differ" rule couples both numbers, so editing
                    // either one clears the error shown on the other as well
                    document.querySelectorAll('[data-validate="phone"]').forEach(clearError);
                });
            });

            const quotaOtherInput = document.getElementById('special_quota_other');
            if (quotaOtherInput) {
                quotaOtherInput.addEventListener('input', function () {
                    sanitize(quotaOtherInput, QUOTA_STRIP);
                    clearError(quotaOtherInput);
                });
            }

            /* -------------------------------------------------
               "سایر" reveal
            ------------------------------------------------- */
            const quotaWrap = document.querySelector('[data-quota-other-wrap]');

            function selectedQuota() {
                const checked = document.querySelector('input[name="special_quota"]:checked');
                return checked ? checked.value : null;
            }

            function syncQuotaOther(focus) {
                if (!quotaWrap || !quotaOtherInput) return;
                const isOther = selectedQuota() === 'سایر';
                quotaWrap.hidden = !isOther;

                if (!isOther) {
                    quotaOtherInput.value = '';
                    clearError(quotaOtherInput);
                } else if (focus) {
                    quotaOtherInput.focus();
                }
            }

            document.querySelectorAll('input[name="special_quota"]').forEach(function (radio) {
                radio.addEventListener('change', function () {
                    syncQuotaOther(true);
                });
            });

            syncQuotaOther(false);

            /* -------------------------------------------------
               Success modal
            ------------------------------------------------- */
            const form = document.getElementById('requestForm');
            const modal = document.getElementById('successModal');
            const closeBtn = modal.querySelector('[data-modal-close]');
            let lastFocused = null;

            function validateForm() {
                const problems = [];

                const fullName = document.getElementById('full_name');
                const major = document.getElementById('major');
                const studentPhone = document.getElementById('student_phone');
                const parentPhone = document.getElementById('parent_phone');

                [fullName, major, studentPhone, parentPhone, quotaOtherInput]
                    .filter(Boolean)
                    .forEach(clearError);

                if (fullName) {
                    const value = fullName.value.trim();
                    if (!value) {
                        problems.push([fullName, 'نام و نام خانوادگی را وارد کنید.']);
                    } else if (value.length < 3 || !NAME_VALID.test(value)) {
                        problems.push([fullName, 'نام باید فقط شامل حروف فارسی باشد.']);
                    }
                }

                if (major && major.value.trim() && !NAME_VALID.test(major.value.trim())) {
                    problems.push([major, 'رشته تحصیلی باید فقط شامل حروف فارسی باشد.']);
                }

                if (studentPhone) {
                    const value = studentPhone.value.trim();
                    if (!value) {
                        problems.push([studentPhone, 'شماره تماس دانش‌آموز را وارد کنید.']);
                    } else if (!PHONE_VALID.test(value)) {
                        problems.push([studentPhone, 'شماره باید ۱۱ رقم و با ۰۹ شروع شود.']);
                    }
                }

                if (parentPhone && parentPhone.value.trim()) {
                    const value = parentPhone.value.trim();

                    if (!PHONE_VALID.test(value)) {
                        problems.push([parentPhone, 'شماره باید ۱۱ رقم و با ۰۹ شروع شود.']);
                    } else if (studentPhone && value === studentPhone.value.trim()) {
                        problems.push([parentPhone, 'شماره اولیا نباید با شماره دانش‌آموز یکسان باشد.']);
                    }
                }

                if (quotaOtherInput && selectedQuota() === 'سایر' && quotaOtherInput.value.trim().length < 2) {
                    problems.push([quotaOtherInput, 'عنوان سهمیه را وارد کنید.']);
                }

                problems.forEach(function (pair) {
                    setError(pair[0], pair[1]);
                });

                if (problems.length) {
                    problems[0][0].focus();
                    problems[0][0].scrollIntoView({ block: 'center', behavior: 'smooth' });
                }

                return problems.length === 0;
            }

            let closeTimer = null;

            // Hiding the page scrollbar widens the viewport, which re-lays-out
            // the page and rescales the `cover` background texture — that was
            // the visible jump behind the modal. `scrollbar-gutter: stable`
            // handles it; this pads the gap for browsers that lack it.
            const hasStableGutter = window.CSS
                && CSS.supports
                && CSS.supports('scrollbar-gutter', 'stable');

            function lockScroll() {
                if (!hasStableGutter) {
                    const gap = window.innerWidth - document.documentElement.clientWidth;
                    if (gap > 0) document.body.style.paddingRight = gap + 'px';
                }
                document.body.style.overflow = 'hidden';
            }

            function unlockScroll() {
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            }

            function openModal() {
                window.clearTimeout(closeTimer);
                lastFocused = document.activeElement;
                lockScroll();
                modal.hidden = false;

                // flush the hidden -> visible change so the transition starts
                // from the closed state instead of being skipped
                void modal.offsetWidth;

                modal.classList.add('is-open');
                closeBtn.focus();
            }

            function closeModal() {
                modal.classList.remove('is-open');
                unlockScroll();
                if (lastFocused) lastFocused.focus();

                closeTimer = window.setTimeout(function () {
                    modal.hidden = true;
                }, 240);
            }

            form.addEventListener('submit', function (event) {
                if (!validateForm()) event.preventDefault();
            });

            closeBtn.addEventListener('click', closeModal);

            modal.addEventListener('click', function (event) {
                if (event.target === modal) closeModal();
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && modal.classList.contains('is-open')) closeModal();
            });
        })();
    </script>
</body>

</html>
