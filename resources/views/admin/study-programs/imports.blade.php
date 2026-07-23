@extends('layouts.admin')

@section('title', 'تاریخچه import رشته‌محل‌ها')

@section('actions')
    <a class="btn btn-outline-secondary" href="{{ route('admin.study-programs.index') }}">رشته‌محل‌ها</a>
@endsection

@section('content')
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>فایل</th><th>سال</th><th>گروه</th><th>وضعیت</th><th>کل</th><th>درج</th><th>به‌روزرسانی</th><th>خطا</th><th>زمان</th><th></th></tr></thead>
                <tbody>
                @forelse($imports as $import)
                    <tr>
                        <td>{{ $import->source_filename }}</td>
                        <td>{{ \App\Support\PersianDate::number($import->examYear?->year) }}</td>
                        <td>{{ $import->examGroup?->name ?: '-' }}</td>
                        <td><span class="badge bg-info">{{ $import->status }}</span></td>
                        <td>{{ \App\Support\PersianDate::number($import->total_rows) }}</td>
                        <td>{{ \App\Support\PersianDate::number($import->inserted_rows) }}</td>
                        <td>{{ \App\Support\PersianDate::number($import->updated_rows) }}</td>
                        <td>{{ \App\Support\PersianDate::number($import->failed_rows) }}</td>
                        <td>{{ \App\Support\PersianDate::dateTime($import->finished_at ?: $import->started_at) }}</td>
                        <td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.study-programs.failures', $import) }}">خطاها</a></td>
                    </tr>
                @empty
                    <tr><td colspan="10"><div class="empty-state">هنوز import ثبت نشده است.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($imports->hasPages())
            <div class="p-3 d-flex justify-content-center">{{ $imports->links() }}</div>
        @endif
    </div>
@endsection
