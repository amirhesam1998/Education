@extends('layouts.admin')

@section('title', 'کاربران')


@section('actions')
    <a class="btn btn-primary" href="{{ route('admin.users.create') }}">
        <i class="ri-user-add-line align-middle"></i> کاربر جدید
    </a>
@endsection

@push('styles')
<style>
    /* ===========================================================
       Users index — page-local styles
    =========================================================== */
    .filter-card .form-label{
        font-size: .78rem;
        font-weight: 600;
        color: var(--ink-500);
        margin-bottom: .35rem;
    }
    .filter-card .btn{ height: 40px; }

    .user-cell{ display: flex; align-items: center; gap: .65rem; }
    .user-avatar{
        width: 36px; height: 36px;
        border-radius: 50%;
        background: var(--brand-100);
        color: var(--brand-700);
        display: flex; align-items: center; justify-content: center;
        font-size: .85rem; font-weight: 700;
        flex-shrink: 0;
    }
    .user-name{ font-weight: 600; color: var(--ink-900); font-size: .87rem; }

    .status-dot{
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        font-size: .82rem;
        font-weight: 500;
    }
    .status-dot::before{
        content: "";
        width: 8px; height: 8px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .status-dot.is-active{ color: var(--success); }
    .status-dot.is-active::before{ background: var(--success); }
    .status-dot.is-inactive{ color: var(--ink-500); }
    .status-dot.is-inactive::before{ background: var(--ink-300); }

    .role-pills{ display: flex; flex-wrap: wrap; gap: .3rem; }
    .role-pill{
        display: inline-block;
        font-size: .74rem;
        font-weight: 500;
        color: var(--brand-700);
        background: var(--brand-50);
        border: 1px solid var(--brand-100);
        border-radius: 999px;
        padding: .2rem .6rem;
    }
    .no-roles{ font-size: .8rem; color: var(--ink-300); }

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
        .filter-card .col-md-5{ margin-bottom: .5rem; }
        .row-actions{ flex-wrap: wrap; }
    }
</style>
@endpush

@section('content')

    {{-- Search --}}
    <div class="card filter-card mb-3">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-5 col-sm-8">
                    <label class="form-label"><i class="ri-search-line align-middle"></i> جستجو</label>
                    <input name="q" value="{{ request('q') }}" class="form-control" placeholder="نام، ایمیل یا شماره تماس">
                </div>
                <div class="col-md-3 col-sm-4 d-flex align-items-end gap-2">
                    <button class="btn btn-primary flex-grow-1">
                        <i class="ri-search-line align-middle"></i> جستجو
                    </button>
                    <a class="btn btn-outline-secondary" href="{{ route('admin.users.index') }}" title="پاکسازی">
                        <i class="ri-refresh-line align-middle"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>نام</th>
                    <th>شماره تماس</th>
                    <th>ایمیل</th>
                    <th>وضعیت</th>
                    <th>نقشها</th>
                    <th>عملیات</th>
                </tr>
                </thead>
                <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>
                            <div class="user-cell">
                                <span class="user-avatar">{{ mb_substr($user->name, 0, 1) }}</span>
                                <span class="user-name">{{ $user->name }}</span>
                            </div>
                        </td>
                        <td class="ltr">{{ $user->phone }}</td>
                        <td class="ltr">{{ $user->email ?: '-' }}</td>
                        <td>
                            <span class="status-dot {{ $user->status === 'active' ? 'is-active' : 'is-inactive' }}">
                                {{ $user->status === 'active' ? 'فعال' : 'غیرفعال' }}
                            </span>
                        </td>
                        <td>
                            @if($user->roles->isNotEmpty())
                                <div class="role-pills">
                                    @foreach($user->roles as $role)
                                        <span class="role-pill">{{ \App\Support\PermissionLabels::role($role->name) }}</span>
                                    @endforeach
                                </div>
                            @else
                                <span class="no-roles">بدون نقش</span>
                            @endif
                        </td>
                        <td>
                            <div class="row-actions">
                                <a class="btn btn-outline-primary" href="{{ route('admin.users.edit', $user) }}" title="ویرایش">
                                    <i class="ri-edit-line"></i>
                                </a>
                                <form method="post" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('حذف شود؟')">
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
                        <td colspan="6">
                            <div class="empty-state">
                                <i class="ri-team-line"></i>
                                کاربری یافت نشد
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
            <div class="pagination-wrap">{{ $users->links() }}</div>
        @endif
    </div>
@endsection