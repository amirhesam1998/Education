@extends('layouts.admin')

@section('title', 'ردیف‌های نیازمند بررسی')

@section('actions')
    <a class="btn btn-outline-secondary" href="{{ route('admin.study-programs.index') }}">
        <i class="ri-arrow-right-line align-middle"></i> بازگشت
    </a>
@endsection

@section('content')
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>کدرشته</th>
                    <th>گروه</th>
                    <th>فایل</th>
                    <th>ردیف</th>
                    <th>دلیل بررسی</th>
                    <th>وضعیت</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($reviews as $review)
                    <tr>
                        <td class="ltr">{{ $review->code ?: '-' }}</td>
                        <td>{{ $review->examGroup?->name }}</td>
                        <td>{{ $review->source_file }}</td>
                        <td>{{ \App\Support\PersianDate::number($review->source_row) }}</td>
                        <td>{{ $review->review_reason ?: '-' }}</td>
                        <td><span class="badge bg-warning text-dark">{{ $review->status }}</span></td>
                        <td>
                            @if($review->status === 'pending')
                                <form method="post" action="{{ route('admin.study-programs.reviews.reject', $review) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-danger">رد</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td colspan="7">
                            <pre class="small bg-light p-2 rounded mb-0 ltr">{{ json_encode($review->raw_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="empty-state">ردیفی برای بررسی وجود ندارد.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($reviews->hasPages())
            <div class="p-3 d-flex justify-content-center">{{ $reviews->links() }}</div>
        @endif
    </div>
@endsection

