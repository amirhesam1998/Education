@extends('layouts.admin')

@section('title', 'انتخاب کنکور')

@section('content')
    <div class="card mx-auto" style="max-width: 720px">
        <div class="card-header"><i class="ri-graduation-cap-line"></i> انتخاب کنکور</div>
        <div class="card-body">
            <p class="text-muted">برای مدیریت انتخاب رشته، نوع کنکور این رزرو را انتخاب کنید.</p>
            @forelse($examTypeOptions as $key => $label)
                <form method="post" action="{{ route('admin.reservations.field-selection.store', $reservation) }}" class="d-inline-block mb-2">
                    @csrf
                    <input type="hidden" name="exam_type_key" value="{{ $key }}">
                    <button class="btn btn-primary">انتخاب رشته کنکور {{ $label }}</button>
                </form>
            @empty
                <div class="alert alert-warning mb-0">نوع کنکور برای این رزرو ثبت نشده است.</div>
            @endforelse
        </div>
    </div>
@endsection
