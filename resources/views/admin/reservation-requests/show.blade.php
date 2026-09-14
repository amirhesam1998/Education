@extends('layouts.admin')

@section('title', 'مشاهده درخواست')
@section('subtitle', $reservationRequest->full_name)
@section('actions')<a class="btn btn-outline-secondary" href="{{ route('admin.reservation-requests.index') }}">بازگشت</a>@endsection

@section('content')
    <div class="card mb-4"><div class="card-header"><i class="ri-file-list-3-line align-middle text-muted me-1"></i> اطلاعات درخواست</div><div class="card-body"><dl class="row mb-0"><dt class="col-sm-3">نام و نام خانوادگی</dt><dd class="col-sm-9">{{ $reservationRequest->full_name }}</dd><dt class="col-sm-3">شماره تماس</dt><dd class="col-sm-9" dir="ltr">{{ $reservationRequest->phone_1 }}{{ $reservationRequest->phone_2 ? ' — '.$reservationRequest->phone_2 : '' }}</dd><dt class="col-sm-3">رشته / نوع کنکور</dt><dd class="col-sm-9">{{ $reservationRequest->major ?: '-' }} — {{ collect($reservationRequest->exam_type)->implode('، ') }}</dd><dt class="col-sm-3">سهمیه منطقه</dt><dd class="col-sm-9">{{ $reservationRequest->region ?: '-' }}</dd><dt class="col-sm-3">سهمیه خاص</dt><dd class="col-sm-9">{{ $reservationRequest->special_quota ?: '-' }}{{ $reservationRequest->special_quota_other ? ' — '.$reservationRequest->special_quota_other : '' }}</dd>@if($reservationRequest->rejection_reason)<dt class="col-sm-3 text-danger">دلیل رد</dt><dd class="col-sm-9 text-danger">{{ $reservationRequest->rejection_reason }}</dd>@endif</dl></div></div>

    @if($reservationRequest->isPending())
        <div class="d-flex flex-wrap gap-2">
            @can('approve_reservation_requests')<form method="post" action="{{ route('admin.reservation-requests.approve', $reservationRequest) }}">@csrf<button class="btn btn-success">تأیید درخواست</button></form>@endcan
            @can('reject_reservation_requests')<button class="btn btn-outline-danger" type="button" data-bs-toggle="collapse" data-bs-target="#reject-form">رد درخواست</button>@endcan
        </div>
        @can('reject_reservation_requests')<form method="post" action="{{ route('admin.reservation-requests.reject', $reservationRequest) }}" id="reject-form" class="collapse card card-body mt-3">@csrf<label class="form-label">دلیل رد</label><textarea name="rejection_reason" class="form-control" rows="3" required>{{ old('rejection_reason') }}</textarea><button class="btn btn-danger align-self-start mt-3">ثبت رد درخواست</button></form>@endcan
    @elseif($reservationRequest->isApproved())
        @can('convert_reservation_requests')<form method="post" action="{{ route('admin.reservation-requests.convert', $reservationRequest) }}">@csrf<button class="btn btn-primary"><i class="ri-calendar-check-line"></i> تبدیل به رزرو</button></form>@endcan
    @elseif($reservationRequest->convertedReservation)
        <a class="btn btn-outline-primary" href="{{ route('admin.reservations.show', $reservationRequest->convertedReservation) }}">مشاهده رزرو ایجاد شده</a>
    @endif
@endsection
