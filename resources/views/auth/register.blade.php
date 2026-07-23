<!doctype html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ثبت‌نام</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: #f6f8f8;
            color: #1c2430;
            font-family: Tahoma, Arial, sans-serif;
        }

        .auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .auth-card {
            width: 100%;
            max-width: 440px;
            border: 1px solid #e7ebee;
            border-radius: 20px;
            background: #fff;
            box-shadow: 0 30px 70px -20px rgba(31, 96, 88, .24);
            overflow: hidden;
        }

        .auth-card::before {
            content: "";
            display: block;
            height: 3px;
            background: linear-gradient(90deg, #2f8f83, #3a7bd5, #2f8f83);
        }

        .auth-card .card-body {
            padding: 2rem 1.85rem;
        }

        .brand-mark {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            background: linear-gradient(150deg, #2f8f83, #1f6058);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.45rem;
            margin-bottom: 1rem;
        }

        .form-control {
            border-radius: 10px;
            border: 1.5px solid #e7ebee;
            background: #f6f8f8;
            padding: .75rem .95rem;
        }

        .form-control:focus {
            border-color: #2f8f83;
            box-shadow: 0 0 0 .2rem rgba(47, 143, 131, .14);
        }

        .btn-primary {
            background: linear-gradient(135deg, #2f8f83, #1f6058);
            border: 0;
            border-radius: 10px;
            padding: .8rem 1rem;
            font-weight: 700;
        }

        .ltr {
            direction: ltr;
            text-align: left;
        }
    </style>
</head>

<body>
    <main class="auth-page">
        <div class="auth-card">
            <div class="card-body">
                <div class="text-center">
                    <span class="brand-mark"><i class="ri-user-add-line"></i></span>
                    <h1 class="h5 fw-bold mb-2">ثبت‌نام در سامانه</h1>
                    <p class="text-muted small mb-4">پس از ثبت‌نام، حساب شما باید توسط مدیر فعال شود.</p>
                </div>

                @if($errors->any())
                    <div class="alert alert-danger">
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <form method="post" action="{{ route('register.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">نام</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="form-control" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">ایمیل</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="form-control ltr" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">شماره تماس</label>
                        <input type="text" name="phone" value="{{ old('phone') }}" class="form-control ltr">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">رمز عبور</label>
                        <input type="password" name="password" class="form-control ltr" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">تکرار رمز عبور</label>
                        <input type="password" name="password_confirmation" class="form-control ltr" required>
                    </div>

                    <button class="btn btn-primary w-100" type="submit">ثبت‌نام</button>
                </form>

                <div class="text-center mt-3 small">
                    <a href="{{ route('login') }}">حساب دارید؟ وارد شوید</a>
                </div>
            </div>
        </div>
    </main>
</body>

</html>
