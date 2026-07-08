@extends('layouts.admin')

@section('title', 'نقشها')

@section('actions')
    <a class="btn btn-primary" href="{{ route('admin.roles.create') }}">نقش جدید</a>
@endsection

@section('content')
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>نام نقش</th><th>تعداد دسترسی</th><th>عملیات</th></tr></thead>
                <tbody>
                @foreach($roles as $role)
                    <tr>
                        <td>{{ \App\Support\PermissionLabels::role($role->name) }}</td>
                        <td>{{ \App\Support\PersianDate::number($role->permissions_count) }}</td>
                        <td class="d-flex gap-2">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.roles.edit', $role) }}">ویرایش</a>
                            <form method="post" action="{{ route('admin.roles.destroy', $role) }}" onsubmit="return confirm('حذف شود؟')">
                                @csrf
                                @method('delete')
                                <button class="btn btn-sm btn-outline-danger">حذف</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-body">{{ $roles->links() }}</div>
    </div>
@endsection
