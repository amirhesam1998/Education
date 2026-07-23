@extends('layouts.admin')

@section('title', 'خطاهای import')

@section('actions')
    <a class="btn btn-outline-secondary" href="{{ route('admin.study-programs.imports') }}">بازگشت</a>
@endsection

@section('content')
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>شیت</th><th>ردیف</th><th>کدرشته</th><th>دلیل</th></tr></thead>
                <tbody>
                @forelse($failures as $failure)
                    <tr>
                        <td>{{ $failure->sheet_name ?: '-' }}</td>
                        <td>{{ \App\Support\PersianDate::number($failure->source_row) }}</td>
                        <td class="ltr">{{ $failure->code ?: '-' }}</td>
                        <td>{{ $failure->reason }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4"><div class="empty-state">خطایی برای این import ثبت نشده است.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($failures->hasPages())
            <div class="p-3 d-flex justify-content-center">{{ $failures->links() }}</div>
        @endif
    </div>
@endsection
