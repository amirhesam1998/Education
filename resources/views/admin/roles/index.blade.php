@extends('layouts.admin')

@section('title', 'نقشها')


@section('actions')
    <a class="btn btn-primary" href="{{ route('admin.roles.create') }}">
        <i class="ri-add-line align-middle"></i> نقش جدید
    </a>
@endsection

@push('styles')
<style>
    /* ===========================================================
       Roles index — page-local styles
    =========================================================== */
    .role-cell{ display: flex; align-items: center; gap: .65rem; }
    .role-icon{
        width: 36px; height: 36px;
        border-radius: 10px;
        background: var(--brand-100);
        color: var(--brand-700);
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
    }
    .role-name{ font-weight: 600; color: var(--ink-900); font-size: .89rem; }

    .permission-count-pill{
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        font-size: .8rem;
        font-weight: 600;
        color: var(--brand-700);
        background: var(--brand-50);
        border: 1px solid var(--brand-100);
        border-radius: 999px;
        padding: .25rem .7rem;
    }
    .permission-count-pill i{ font-size: .9rem; }

    .row-actions{ display: flex; gap: .4rem; flex-wrap: nowrap; }
    .row-actions .btn{
        width: 34px; height: 34px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--radius-sm);
        font-size: 1rem;
    }
    .row-actions form{ margin: 0; }

    .btn-outline-danger{ color: var(--danger); border-color: #f0c8c4; }
    .btn-outline-danger:hover{ background: var(--danger); border-color: var(--danger); }

    .pagination-wrap{
        display: flex;
        justify-content: center;
        padding: .9rem;
    }
    .pagination{ margin: 0; }
    .page-link{
        border: 1px solid var(--border);
        color: var(--ink-700);
        border-radius: var(--radius-sm) !important;
        margin: 0 .15rem;
    }
    .page-item.active .page-link{
        background: var(--brand-500);
        border-color: var(--brand-500);
    }

    .empty-state{
        text-align: center;
        padding: 2.5rem 1rem;
        color: var(--ink-500);
    }
    .empty-state i{ font-size: 2.2rem; color: var(--ink-300); margin-bottom: .5rem; display: block; }

    @media (max-width: 767.98px){
        .row-actions{ flex-wrap: wrap; }
    }
</style>
@endpush

@section('content')

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>نام نقش</th>
                    <th>تعداد دسترسی</th>
                    <th>عملیات</th>
                </tr>
                </thead>
                <tbody>
                @forelse($roles as $role)
                    <tr>
                        <td>
                            <div class="role-cell">
                                <span class="role-icon"><i class="ri-shield-user-line"></i></span>
                                <span class="role-name">{{ \App\Support\PermissionLabels::role($role->name) }}</span>
                            </div>
                        </td>
                        <td>
                            <span class="permission-count-pill">
                                <i class="ri-shield-keyhole-line"></i>
                                {{ \App\Support\PersianDate::number($role->permissions_count) }}
                            </span>
                        </td>
                        <td>
                            <div class="row-actions">
                                <a class="btn btn-outline-primary" href="{{ route('admin.roles.edit', $role) }}" title="ویرایش">
                                    <i class="ri-edit-line"></i>
                                </a>
                                <form method="post" action="{{ route('admin.roles.destroy', $role) }}" onsubmit="return confirm('حذف شود؟')">
                                    @csrf
                                    @method('delete')
                                    <button class="btn btn-outline-danger" title="حذف">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">
                            <div class="empty-state">
                                <i class="ri-shield-user-line"></i>
                                نقشی یافت نشد
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($roles->hasPages())
            <div class="pagination-wrap">{{ $roles->links() }}</div>
        @endif
    </div>
@endsection