@csrf
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">نام</label>
        <input name="name" value="{{ old('name', $user->name) }}" class="form-control" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">ایمیل</label>
        <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control ltr" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">شماره تماس</label>
        <input name="phone" value="{{ old('phone', $user->phone) }}" class="form-control ltr">
    </div>
    <div class="col-md-6">
        <label class="form-label">وضعیت</label>
        <select name="status" class="form-select">
            <option value="active" @selected(old('status', $user->status) === 'active')>فعال</option>
            <option value="inactive" @selected(old('status', $user->status) === 'inactive')>غیرفعال</option>
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">رمز عبور</label>
        <input type="password" name="password" class="form-control ltr" @required($mode === 'create')>
    </div>
    <div class="col-md-6">
        <label class="form-label">تکرار رمز عبور</label>
        <input type="password" name="password_confirmation" class="form-control ltr" @required($mode === 'create')>
    </div>
    <div class="col-12">
        <label class="form-label">نقشها</label>
        <div class="row g-2">
            @foreach($roles as $role)
                <div class="col-md-3 col-sm-6">
                    <label class="form-check">
                        <input type="checkbox" name="roles[]" value="{{ $role->name }}" class="form-check-input"
                            @checked(in_array($role->name, old('roles', $user->roles?->pluck('name')->all() ?? []), true))>
                        <span class="form-check-label">{{ \App\Support\PermissionLabels::role($role->name) }}</span>
                    </label>
                </div>
            @endforeach
        </div>
    </div>
</div>
<div class="mt-4 d-flex gap-2">
    <button class="btn btn-primary">ذخیره</button>
    <a class="btn btn-outline-secondary" href="{{ route('admin.users.index') }}">بازگشت</a>
</div>
