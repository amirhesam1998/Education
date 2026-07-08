@csrf
<div class="mb-3">
    <label class="form-label">نام نقش</label>
    <input name="name" value="{{ old('name', $role->name) }}" class="form-control" required>
</div>
<div class="mb-3">
    <label class="form-label">دسترسیها</label>
    <div class="row g-2">
        @foreach($permissions as $permission)
            <div class="col-lg-3 col-md-4 col-sm-6">
                <label class="form-check">
                    <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" class="form-check-input"
                        @checked(in_array($permission->name, old('permissions', $role->permissions?->pluck('name')->all() ?? []), true))>
                    <span class="form-check-label">{{ \App\Support\PermissionLabels::permission($permission->name) }}</span>
                </label>
            </div>
        @endforeach
    </div>
</div>
<div class="mt-4 d-flex gap-2">
    <button class="btn btn-primary">ذخیره</button>
    <a class="btn btn-outline-secondary" href="{{ route('admin.roles.index') }}">بازگشت</a>
</div>
