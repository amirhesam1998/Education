@extends('layouts.public')

@section('title', 'جزئیات رزرو مشاوره')

@section('content')
    @php
        $missing = $reservation->missingStudentFields();
        $canUpdate = !$isLinkExpired && $reservation->status->allowsPublicUpdates();
        $payment = $reservation->payment;
        $statusDates = [
            'مهلت لینک' => $reservation->public_token_expires_at,
            'تأیید رزرو' => $reservation->confirmed_at,
            'لغو رزرو' => $reservation->cancelled_at,
            'انقضای رزرو' => $reservation->expired_at,
            'تکمیل رزرو' => $reservation->completed_at,
            'آپلود فیش' => $payment?->uploaded_at,
            'تأیید فیش' => $payment?->approved_at,
            'رد فیش' => $payment?->rejected_at,
        ];
    @endphp

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <h1 class="h4 mb-1">{{ $settings->get('institute_name', 'آموزشگاه') }}</h1>
                    <div class="text-muted">جزئیات رزرو مشاوره انتخاب رشته</div>
                </div>
                <span class="badge text-bg-light">{{ $reservation->status->label() }}</span>
            </div>

            @if($reservation->status === \App\Enums\ReservationStatus::Expired || $isLinkExpired)
                <div class="alert alert-warning">{{ $settings->get('expired_message', 'مهلت تکمیل اطلاعات یا پرداخت به پایان رسیده است. لطفاً با آموزشگاه تماس بگیرید.') }}</div>
            @elseif($reservation->status === \App\Enums\ReservationStatus::Cancelled)
                <div class="alert alert-danger">{{ $settings->get('cancelled_message', 'این رزرو توسط آموزشگاه لغو شده است. لطفاً با آموزشگاه تماس بگیرید.') }}</div>
            @elseif($reservation->status === \App\Enums\ReservationStatus::PendingPaymentApproval)
                <div class="alert alert-info">فیش شما ثبت شد و در انتظار تأیید آموزشگاه است.</div>
            @elseif($reservation->status === \App\Enums\ReservationStatus::Confirmed)
                <div class="alert alert-success">رزرو شما با موفقیت نهایی شده است.</div>
            @elseif($reservation->status === \App\Enums\ReservationStatus::PaymentRejected)
                <div class="alert alert-danger">فیش پرداخت شما رد شده است. در صورت باز بودن مهلت، فیش صحیح را بارگذاری کنید.</div>
            @endif

            <div class="row g-3">
                <div class="col-md-6"><strong>نام و نام خانوادگی:</strong> {{ $reservation->student?->full_name ?: '-' }}</div>
                <div class="col-md-6"><strong>رشته:</strong> {{ $reservation->student?->major ?: '-' }}</div>
                <div class="col-md-6"><strong>تراز:</strong> {{ $reservation->student?->score ?: '-' }}</div>
                <div class="col-md-6"><strong>نوع کنکور:</strong> {{ $reservation->student?->exam_type ?: '-' }}</div>
                <div class="col-md-6"><strong>شماره تماس:</strong> <span class="ltr">{{ $reservation->student?->phones->pluck('phone')->implode(' / ') }}</span></div>
                <div class="col-md-6"><strong>مشاور:</strong> {{ $reservation->advisor?->name ?: $reservation->slot?->advisor?->name ?: '-' }}</div>
                <div class="col-md-6"><strong>تاریخ:</strong> {{ $reservation->slot ? \App\Support\PersianDate::date($reservation->slot->date) : '-' }}</div>
                <div class="col-md-6"><strong>بازه کلی تایم:</strong> {{ $reservation->slot ? \App\Support\PersianDate::time($reservation->slot->start_time).' تا '.\App\Support\PersianDate::time($reservation->slot->end_time) : '-' }}</div>
                <div class="col-md-6"><strong>زمان رزرو:</strong> {{ $reservation->slot ? \App\Support\PersianDate::time($reservation->assignedStartTime()).' تا '.\App\Support\PersianDate::time($reservation->assignedEndTime()) : '-' }}</div>
                @if($reservation->prepayment_required)
                    <div class="col-md-6"><strong>مبلغ پیش پرداخت:</strong> {{ \App\Support\PersianDate::money($reservation->prepayment_amount) }}</div>
                    <div class="col-md-6"><strong>مهلت پرداخت:</strong> {{ \App\Support\PersianDate::dateTime($reservation->payment_deadline_at) }}</div>
                @endif
                @foreach($statusDates as $label => $date)
                    @if($date)
                        <div class="col-md-6"><strong>{{ $label }}:</strong> {{ \App\Support\PersianDate::dateTime($date) }}</div>
                    @endif
                @endforeach
            </div>

            <div class="alert alert-secondary mt-4 mb-0">
                برای لغو یا تغییر زمان رزرو، لطفاً با آموزشگاه تماس بگیرید.
                @if($settings->get('contact_phone'))
                    <span class="ltr">{{ $settings->get('contact_phone') }}</span>
                @endif
            </div>
        </div>
    </div>

    @if($canUpdate && count($missing) > 0)
        <div class="card mb-3">
            <div class="card-body">
                <h2 class="h5 mb-3">تکمیل اطلاعات</h2>
                <form method="post" action="{{ route('public.reservations.complete', $reservation->public_token) }}">
                    @csrf
                    <div class="row g-3">
                        @if(in_array('full_name', $missing, true))
                            <div class="col-md-6">
                                <label class="form-label">نام و نام خانوادگی</label>
                                <input name="full_name" value="{{ old('full_name') }}" class="form-control" required>
                            </div>
                        @endif
                        @if(in_array('major', $missing, true))
                            <div class="col-md-6">
                                <label class="form-label">رشته</label>
                                <input name="major" value="{{ old('major') }}" class="form-control" required>
                            </div>
                        @endif
                        @if(in_array('score', $missing, true))
                            <div class="col-md-6">
                                <label class="form-label">تراز</label>
                                <input name="score" value="{{ old('score') }}" class="form-control" required>
                            </div>
                        @endif
                        @if(in_array('exam_type', $missing, true))
                            <div class="col-md-6">
                                <label class="form-label">نوع کنکور</label>
                                <input name="exam_type" value="{{ old('exam_type') }}" class="form-control" required>
                            </div>
                        @endif
                        @if(in_array('phone_one', $missing, true))
                            <div class="col-md-6">
                                <label class="form-label">شماره تماس اول</label>
                                <input name="phone_one" value="{{ old('phone_one') }}" class="form-control ltr" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">شماره تماس دوم</label>
                                <input name="phone_two" value="{{ old('phone_two') }}" class="form-control ltr">
                            </div>
                        @endif
                        <div class="col-12">
                            <label class="form-label">توضیح دانش آموز</label>
                            <textarea name="student_note" rows="2" class="form-control">{{ old('student_note') }}</textarea>
                        </div>
                    </div>
                    <button class="btn btn-primary mt-3">ثبت اطلاعات</button>
                </form>
            </div>
        </div>
    @endif

    @if(
        $canUpdate
        && $reservation->prepayment_required
        && count($missing) === 0
        && in_array($reservation->status, [\App\Enums\ReservationStatus::PendingPrepayment, \App\Enums\ReservationStatus::PaymentRejected], true)
    )
        <div class="card">
            <div class="card-body">
                <h2 class="h5 mb-3">آپلود فیش پرداخت</h2>
                @if($payment?->rejection_reason)
                    <div class="alert alert-danger">دلیل رد فیش: {{ $payment->rejection_reason }}</div>
                @endif
                <form method="post" action="{{ route('public.reservations.upload-receipt', $reservation->public_token) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">فیش پرداخت</label>
                        <input type="file" name="receipt_image" class="form-control" accept=".jpg,.jpeg,.png,.webp" required>
                    </div>
                    <button class="btn btn-primary">ارسال فیش</button>
                </form>
            </div>
        </div>
    @endif
@endsection
