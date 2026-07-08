<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ورود به پنل مدیریت</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        body { background: #f6f7fb; font-family: Tahoma, Arial, sans-serif; }
        .login-card { max-width: 420px; margin: 9vh auto; border-radius: .5rem; }
    </style>
</head>
<body>
<div class="container">
    <div class="card login-card shadow-sm">
        <div class="card-body p-4">
            <h1 class="h4 mb-4">ورود به پنل مدیریت</h1>

            @if($errors->any())
                <div class="alert alert-danger">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="post" action="{{ route('login.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">ایمیل</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-control ltr" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label">رمز عبور</label>
                    <input type="password" name="password" class="form-control ltr" required>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="remember" value="1" id="remember">
                    <label class="form-check-label" for="remember">مرا به خاطر بسپار</label>
                </div>
                <button class="btn btn-primary w-100">ورود</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
