@extends('layouts.admin')

@section('title', 'کاربران')

@section('actions')
    <a class="btn btn-primary" href="{{ route('admin.users.create') }}">کاربر جدید</a>
@endsection

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <form method="get" class="row g-2">
                <div class="col-md-5">
                    <label class="form-label">جستجو</label>
                    <input name="q" value="{{ request('q') }}" class="form-control" placeholder="نام، ایمیل یا شماره تماس">
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button class="btn btn-outline-primary">جستجو</button>
                    <a class="btn btn-outline-secondary" href="{{ route('admin.users.index') }}">پاکسازی</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>نام</th><th>ایمیل</th><th>شماره تماس</th><th>وضعیت</th><th>نقشها</th><th>عملیات</th></tr></thead>
                <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td class="ltr">{{ $user->email }}</td>
                        <td class="ltr">{{ $user->phone }}</td>
                        <td>{{ $user->status === 'active' ? 'فعال' : 'غیرفعال' }}</td>
                        <td>{{ $user->roles->pluck('name')->map(fn ($name) => \App\Support\PermissionLabels::role($name))->implode('، ') }}</td>
                        <td class="d-flex gap-2">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.users.edit', $user) }}">ویرایش</a>
                            <form method="post" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('حذف شود؟')">
                                @csrf
                                @method('delete')
                                <button class="btn btn-sm btn-outline-danger">حذف</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">کاربری یافت نشد.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">{{ $users->links() }}</div>
    </div>
@endsection
