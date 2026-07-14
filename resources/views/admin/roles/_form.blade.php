@push('styles')
<style>

    .form-card{ margin-bottom: 1rem; }
    .form-card .card-header{
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
        font-size: .95rem;
    }
    .form-card .card-header-title{ display: flex; align-items: center; gap: .5rem; }
    .form-card .card-header i{ color: var(--brand-600); font-size: 1.05rem; }
    .form-card .card-body{ padding: 1.25rem; }

    .form-hint{
        font-size: .76rem;
        color: var(--ink-500);
        margin-top: .3rem;
    }

    /* Permission picker — selectable chip cards, same pattern as the role picker on the user form */
    .permission-toolbar{
        display: flex;
        align-items: center;
        gap: .6rem;
        margin-bottom: 1rem;
        flex-wrap: wrap;
    }
    .permission-count{
        font-size: .8rem;
        color: var(--ink-500);
        margin-inline-start: auto;
    }
    .permission-count strong{ color: var(--brand-700); }

    .btn-chip{
        font-size: .78rem;
        padding: .35rem .75rem;
        border-radius: var(--radius-sm);
        border: 1px solid var(--border);
        background: var(--surface);
        color: var(--ink-700);
    }
    .btn-chip:hover{ background: var(--ink-100); }

    .permission-grid{
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: .6rem;
    }

    .permission-option{
        display: flex;
        align-items: center;
        gap: .55rem;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: .65rem .8rem;
        cursor: pointer;
        transition: border-color .15s, background .15s;
        height: 100%;
        margin: 0;
    }
    .permission-option:hover{ border-color: var(--brand-200); background: var(--brand-50); }

    .permission-option .form-check-input{
        width: 1.05rem;
        height: 1.05rem;
        margin: 0;
        flex-shrink: 0;
        border-color: var(--ink-300);
    }
    .permission-option .form-check-input:checked{
        background-color: var(--brand-500);
        border-color: var(--brand-500);
    }
    .permission-option .form-check-input:focus{
        box-shadow: 0 0 0 .2rem rgba(47,143,131,.15);
    }

    .permission-option .form-check-label{
        font-size: .82rem;
        font-weight: 500;
        color: var(--ink-700);
        cursor: pointer;
    }

    .permission-option:has(.form-check-input:checked){
        border-color: var(--brand-500);
        background: var(--brand-50);
    }
    .permission-option:has(.form-check-input:checked) .form-check-label{
        color: var(--brand-700);
        font-weight: 600;
    }

    .form-actions{
       position: sticky;
        bottom: 0;
        background: var(--bg);
        padding: .9rem 0 .2rem;
        display: flex;
        gap: .6rem;
        border-top: 1px solid transparent;
        justify-content: end;
    }
    .form-actions .btn{ min-width: 120px; }

    @media (max-width: 1199.98px){
        .permission-grid{ grid-template-columns: repeat(3, 1fr); }
    }
    @media (max-width: 991.98px){
        .permission-grid{ grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 575.98px){
        .form-card .card-body{ padding: 1rem; }
        .permission-grid{ grid-template-columns: 1fr; }
        .permission-count{ margin-inline-start: 0; width: 100%; }
        .form-actions{ flex-direction: column-reverse; }
        .form-actions .btn{ width: 100%; }
    }
</style>
@endpush

@csrf

<div class="card form-card">
    <div class="card-header"><i class="ri-price-tag-3-line"></i> نام نقش</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">نام نقش</label>
                <input name="name" value="{{ old('name', $role->name) }}" class="form-control" required>
                <div class="form-hint">این نام برای تعیین سطح دسترسی کاربران استفاده می‌شود.</div>
            </div>
        </div>
    </div>
</div>

<div class="card form-card">
    <div class="card-header"><i class="ri-shield-keyhole-line"></i> دسترسیها</div>
    <div class="card-body">
        <div class="permission-toolbar">
            <button type="button" class="btn-chip" id="selectAllPermissions">
                <i class="ri-checkbox-multiple-line align-middle"></i> انتخاب همه
            </button>
            <button type="button" class="btn-chip" id="clearAllPermissions">
                <i class="ri-close-line align-middle"></i> پاک کردن همه
            </button>
            <span class="permission-count"><strong id="permissionSelectedCount">0</strong> از {{ count($permissions) }} انتخاب شده</span>
        </div>

        <div class="permission-grid" id="permissionGrid">
            @foreach($permissions as $permission)
                <label class="permission-option">
                    <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" class="form-check-input"
                        @checked(in_array($permission->name, old('permissions', $role->permissions?->pluck('name')->all() ?? []), true))>
                    <span class="form-check-label">{{ \App\Support\PermissionLabels::permission($permission->name) }}</span>
                </label>
            @endforeach
        </div>
    </div>
</div>

<div class="form-actions">
    <button class="btn btn-primary">
        <i class="ri-save-line align-middle"></i> ذخیره
    </button>
    <a class="btn btn-outline-secondary" href="{{ route('admin.roles.index') }}">بازگشت</a>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const grid = document.getElementById('permissionGrid');
        const counter = document.getElementById('permissionSelectedCount');
        const selectAllBtn = document.getElementById('selectAllPermissions');
        const clearAllBtn = document.getElementById('clearAllPermissions');

        if (!grid || !counter) return;

        const checkboxes = () => Array.from(grid.querySelectorAll('input[type="checkbox"]'));

        function updateCount () {
            counter.textContent = checkboxes().filter((cb) => cb.checked).length;
        }

        grid.addEventListener('change', updateCount);

        selectAllBtn?.addEventListener('click', function () {
            checkboxes().forEach((cb) => { cb.checked = true; });
            updateCount();
        });

        clearAllBtn?.addEventListener('click', function () {
            checkboxes().forEach((cb) => { cb.checked = false; });
            updateCount();
        });

        updateCount();
    });
</script>
@endpush