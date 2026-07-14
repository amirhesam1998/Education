@push('styles')
    <style>
        .form-card {
            margin-bottom: 1rem;
        }

        .form-card .card-header {
            display: flex;
            align-items: center;
            gap: .5rem;
            font-size: .95rem;
        }

        .form-card .card-header i {
            color: var(--brand-600);
            font-size: 1.05rem;
        }

        .form-card .card-body {
            padding: 1.25rem;
        }

        .form-hint {
            font-size: .76rem;
            color: var(--ink-500);
            margin-top: .3rem;
        }

        /* Role picker — selectable chip cards instead of bare checkboxes */
        .role-option {
            display: flex;
            align-items: center;
            gap: .6rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: .7rem .85rem;
            cursor: pointer;
            transition: border-color .15s, background .15s;
            height: 100%;
            margin: 0;
        }

        .role-option:hover {
            border-color: var(--brand-200);
            background: var(--brand-50);
        }

        .role-option .form-check-input {
            width: 1.05rem;
            height: 1.05rem;
            margin: 0;
            flex-shrink: 0;
            border-color: var(--ink-300);
        }

        .role-option .form-check-input:checked {
            background-color: var(--brand-500);
            border-color: var(--brand-500);
        }

        .role-option .form-check-input:focus {
            box-shadow: 0 0 0 .2rem rgba(47, 143, 131, .15);
        }

        .role-option .form-check-label {
            font-size: .85rem;
            font-weight: 500;
            color: var(--ink-700);
            cursor: pointer;
        }

        .role-option:has(.form-check-input:checked) {
            border-color: var(--brand-500);
            background: var(--brand-50);
        }

        .role-option:has(.form-check-input:checked) .form-check-label {
            color: var(--brand-700);
            font-weight: 600;
        }

        .role-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: .65rem;
        }

        .password-hint {
            display: flex;
            align-items: center;
            gap: .35rem;
            font-size: .76rem;
            color: var(--ink-500);
            margin-top: .3rem;
        }

        .password-hint i {
            color: var(--info);
        }

        .form-actions {
            position: sticky;
            bottom: 0;
            background: var(--bg);
            padding: .9rem 0 .2rem;
            display: flex;
            gap: .6rem;
            border-top: 1px solid transparent;
            justify-content: end;
        }

        .form-actions .btn {
            min-width: 120px;
        }

        @media (max-width: 991.98px) {
            .role-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 575.98px) {
            .form-card .card-body {
                padding: 1rem;
            }

            .role-grid {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column-reverse;
            }

            .form-actions .btn {
                width: 100%;
            }
        }
    </style>
@endpush

@csrf

<div class="card form-card">
    <div class="card-header"><i class="ri-user-line"></i> اطلاعات پایه</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">نام</label>
                <input name="name" value="{{ old('name', $user->name) }}" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">ایمیل</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control ltr"
                    required>
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
        </div>
    </div>
</div>

<div class="card form-card">
    <div class="card-header"><i class="ri-lock-line"></i> رمز عبور</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">رمز عبور</label>
                <input type="password" name="password" class="form-control ltr" @required($mode === 'create')>
            </div>
            <div class="col-md-6">
                <label class="form-label">تکرار رمز عبور</label>
                <input type="password" name="password_confirmation" class="form-control ltr"
                    @required($mode === 'create')>
            </div>
        </div>
        @if($mode === 'create')
            <div class="password-hint"><i class="ri-information-line"></i> رمز عبور برای ورود کاربر جدید الزامی است.</div>
        @else
            <div class="password-hint"><i class="ri-information-line"></i> برای حفظ رمز عبور فعلی، این فیلدها را خالی
                بگذارید.</div>
        @endif
    </div>
</div>

<div class="card form-card">
    <div class="card-header"><i class="ri-shield-user-line"></i> نقشها</div>
    <div class="card-body">
        <div class="role-grid">
            @foreach($roles as $role)
                <label class="role-option">
                    <input type="checkbox" name="roles[]" value="{{ $role->name }}" class="form-check-input"
                        @checked(in_array($role->name, old('roles', $user->roles?->pluck('name')->all() ?? []), true))>
                    <span class="form-check-label">{{ \App\Support\PermissionLabels::role($role->name) }}</span>
                </label>
            @endforeach
        </div>
        <div class="form-hint">کاربر می‌تواند بیش از یک نقش داشته باشد. دسترسیهای نهایی از مجموع نقشهای انتخاب شده تعیین
            می‌شود.</div>
    </div>
</div>

<div class="form-actions">
    <button class="btn btn-primary">
        <i class="ri-save-line align-middle"></i> ذخیره
    </button>
    <a class="btn btn-outline-secondary" href="{{ route('admin.users.index') }}">بازگشت</a>
</div>