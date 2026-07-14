@extends('layouts.public')

@section('title', 'جزئیات رزرو مشاوره')

@push('styles')
<style>
    /* ===========================================================
       Reservation details — page-local styles
    =========================================================== */
    .hero-row{
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 1.1rem;
    }
    .hero-title{ font-size: 1.2rem; font-weight: 700; margin: 0 0 .25rem; }
    .hero-sub{ font-size: .84rem; color: var(--ink-500); }

    .status-badge{
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        font-size: .8rem;
        font-weight: 600;
        padding: .45rem .85rem;
        border-radius: 999px;
        white-space: nowrap;
    }
    .status-badge::before{
        content: "";
        width: 8px; height: 8px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .status-badge.tone-success{ background: var(--success-bg); color: var(--success); }
    .status-badge.tone-success::before{ background: var(--success); }
    .status-badge.tone-warning{ background: var(--warning-bg); color: var(--warning); }
    .status-badge.tone-warning::before{ background: var(--warning); }
    .status-badge.tone-danger{ background: var(--danger-bg); color: var(--danger); }
    .status-badge.tone-danger::before{ background: var(--danger); }
    .status-badge.tone-info{ background: var(--info-bg); color: var(--info); }
    .status-badge.tone-info::before{ background: var(--info); }
    .status-badge.tone-neutral{ background: var(--neutral-bg); color: var(--neutral); }
    .status-badge.tone-neutral::before{ background: var(--neutral); }

 /* Steps tracker */
.steps{
    display:flex;
    align-items:flex-start;
    width:100%;
    margin-bottom:1.5rem;
    gap:0;
}

.step{
    flex:1;
    display:flex;
    flex-direction:column;
    align-items:center;
    text-align:center;
    gap:.45rem;
    position:relative;
}

/* Connector line between steps */
.step::after{
    content:"";
    position:absolute;
    top:15px;
    right:50%;
    width:100%;
    height:2px;
    background:var(--border);
    z-index:0;
    transition:
        background-color .3s ease,
        transform .3s ease;
}

/* Because the layout is RTL, the last visual step must not continue the line */
.step:last-child::after{
    display:none;
}

/* Completed connector */
.step.is-done::after{
    background:var(--pub-500);
}

.step-dot{
    position:relative;
    z-index:2;

    width:32px;
    height:32px;
    border-radius:50%;

    display:flex;
    align-items:center;
    justify-content:center;

    font-size:.85rem;
    background:var(--surface, #fff);
    color:var(--ink-500);
    border:2px solid var(--border);

    transition:
        transform .25s ease,
        background-color .25s ease,
        border-color .25s ease,
        box-shadow .25s ease;
}

.step.is-done .step-dot{
    background:var(--pub-500);
    border-color:var(--pub-500);
    color:#fff;
}

.step.is-current .step-dot{
    background:#fff;
    border-color:var(--pub-500);
    color:var(--pub-600);
    box-shadow:0 0 0 5px var(--pub-100);
    transform:scale(1.05);
}

.step-label{
    position:relative;
    z-index:2;
    font-size:.72rem;
    color:var(--ink-500);
    font-weight:500;
}

.step.is-done .step-label,
.step.is-current .step-label{
    color:var(--ink-900);
    font-weight:700;
}

@media (max-width:575.98px){
    .steps{
        margin-inline:-4px;
        width:calc(100% + 8px);
    }

    .step-dot{
        width:30px;
        height:30px;
        font-size:.8rem;
    }

    .step::after{
        top:14px;
    }

    .step-label{
        display:block;
        font-size:.62rem;
        line-height:1.5;
        padding:0 3px;
    }
}

@media (max-width:380px){
    .step-label{
        font-size:.58rem;
    }
}

    .info-grid{
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: .8rem;
    }
    .info-item{
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: .85rem 1rem;
        background: var(--bg);
    }
    .info-label{
        font-size: .74rem;
        color: var(--ink-500);
        display: flex;
        align-items: center;
        gap: .3rem;
        margin-bottom: .25rem;
    }
    .info-label i{ color: var(--pub-500); }
    .info-value{ font-size: .89rem; font-weight: 700; color: var(--ink-900); }

    .section-title{
        font-size: 1.02rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: .5rem;
        margin-bottom: 1.1rem;
    }
    .section-title i{ color: var(--pub-500); }

    .contact-note{
        display: flex;
        align-items: center;
        gap: .6rem;
    }
    .contact-note i{ font-size: 1.2rem; }

    .receipt-frame{
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        overflow: hidden;
        background: var(--bg);
        max-width: 340px;
    }
    .receipt-frame img{ display: block; width: 100%; }

    .upload-drop{
        border: 1.5px dashed var(--pub-200);
        border-radius: var(--radius-md);
        padding: 1.4rem;
        text-align: center;
        background: var(--pub-50);
        transition: border-color .15s, background .15s;
    }
    .upload-drop:hover{ border-color: var(--pub-500); background: var(--pub-100); }
    .upload-drop i{ font-size: 1.8rem; color: var(--pub-500); display: block; margin-bottom: .4rem; }
    .upload-drop-text{ font-size: .84rem; color: var(--ink-700); margin-bottom: .8rem; }

    @media (max-width: 575.98px){
        .info-grid{ grid-template-columns: 1fr; }
        .hero-row{ flex-direction: column; }
    }
</style>
@endpush

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

        $statusTone = match(true) {
            $reservation->status === \App\Enums\ReservationStatus::Confirmed => 'success',
            in_array($reservation->status, [\App\Enums\ReservationStatus::Cancelled, \App\Enums\ReservationStatus::PaymentRejected], true) => 'danger',
            $reservation->status === \App\Enums\ReservationStatus::Expired || $isLinkExpired => 'neutral',
            $reservation->status === \App\Enums\ReservationStatus::PendingPaymentApproval => 'info',
            default => 'warning',
        };

        $infoStepDone = count($missing) === 0;
        $paymentStepDone = ! $reservation->prepayment_required || $payment?->approved_at;
        $confirmedStepDone = $reservation->status === \App\Enums\ReservationStatus::Confirmed;

        $currentStep = match(true) {
            $confirmedStepDone => 4,
            $paymentStepDone => 3,
            $infoStepDone => 2,
            default => 1,
        };
    @endphp

    {{-- Header + status --}}
    <div class="card">
        <div class="card-body">
            <div class="hero-row">
                <div>
                    <h1 class="hero-title">{{ $settings->get('institute_name', 'آموزشگاه') }}</h1>
                    <div class="hero-sub">جزئیات رزرو مشاوره انتخاب رشته</div>
                </div>
                <span class="status-badge tone-{{ $statusTone }}">{{ $reservation->status->label() }}</span>
            </div>

            {{-- Progress tracker --}}
            <div class="steps">
                <div class="step {{ $currentStep > 1 ? 'is-done' : 'is-current' }}">
                    <div class="step-dot"><i class="ri-file-list-3-line"></i></div>
                    <div class="step-label">ثبت رزرو</div>
                </div>
                <div class="step {{ $infoStepDone ? ($currentStep > 2 ? 'is-done' : 'is-current') : ($currentStep === 1 ? '' : 'is-current') }}">
                    <div class="step-dot"><i class="ri-user-line"></i></div>
                    <div class="step-label">تکمیل اطلاعات</div>
                </div>
                <div class="step {{ $paymentStepDone ? ($currentStep > 3 ? 'is-done' : 'is-current') : ($currentStep === 3 ? 'is-current' : '') }}">
                    <div class="step-dot"><i class="ri-bank-card-line"></i></div>
                    <div class="step-label">پرداخت</div>
                </div>
                <div class="step {{ $confirmedStepDone ? 'is-done' : ($currentStep === 4 ? 'is-current' : '') }}">
                    <div class="step-dot"><i class="ri-checkbox-circle-line"></i></div>
                    <div class="step-label">تأیید نهایی</div>
                </div>
            </div>

            @if($reservation->status === \App\Enums\ReservationStatus::Expired || $isLinkExpired)
                <div class="alert alert-warning">
                    <i class="ri-time-line"></i>
                    <div>{{ $settings->get('expired_message', 'مهلت تکمیل اطلاعات یا پرداخت به پایان رسیده است. لطفاً با آموزشگاه تماس بگیرید.') }}</div>
                </div>
            @elseif($reservation->status === \App\Enums\ReservationStatus::Cancelled)
                <div class="alert alert-danger">
                    <i class="ri-close-circle-line"></i>
                    <div>{{ $settings->get('cancelled_message', 'این رزرو توسط آموزشگاه لغو شده است. لطفاً با آموزشگاه تماس بگیرید.') }}</div>
                </div>
            @elseif($reservation->status === \App\Enums\ReservationStatus::PendingPaymentApproval)
                <div class="alert alert-info">
                    <i class="ri-time-line"></i>
                    <div>فیش شما ثبت شد و در انتظار تأیید آموزشگاه است.</div>
                </div>
            @elseif($reservation->status === \App\Enums\ReservationStatus::Confirmed)
                <div class="alert alert-success">
                    <i class="ri-checkbox-circle-line"></i>
                    <div>رزرو شما با موفقیت نهایی شده است.</div>
                </div>
            @elseif($reservation->status === \App\Enums\ReservationStatus::PaymentRejected)
                <div class="alert alert-danger">
                    <i class="ri-error-warning-line"></i>
                    <div>فیش پرداخت شما رد شده است. در صورت باز بودن مهلت، فیش صحیح را بارگذاری کنید.</div>
                </div>
            @endif

            <div class="info-grid mt-1">
                <div class="info-item">
                    <div class="info-label"><i class="ri-user-line"></i> نام و نام خانوادگی</div>
                    <div class="info-value">{{ $reservation->student?->full_name ?: '-' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label"><i class="ri-graduation-cap-line"></i> رشته</div>
                    <div class="info-value">{{ $reservation->student?->major ?: '-' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label"><i class="ri-bar-chart-line"></i> تراز</div>
                    <div class="info-value">{{ $reservation->student?->score ?: '-' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label"><i class="ri-file-text-line"></i> نوع کنکور</div>
                    <div class="info-value">{{ $reservation->student?->exam_type ?: '-' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label"><i class="ri-phone-line"></i> شماره تماس</div>
                    <div class="info-value ltr">{{ $reservation->student?->phones->pluck('phone')->implode(' / ') }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label"><i class="ri-user-star-line"></i> مشاور</div>
                    <div class="info-value">{{ $reservation->advisor?->name ?: $reservation->slot?->advisor?->name ?: '-' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label"><i class="ri-calendar-line"></i> تاریخ</div>
                    <div class="info-value">{{ $reservation->slot ? \App\Support\PersianDate::date($reservation->slot->date) : '-' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label"><i class="ri-time-line"></i> زمان رزرو</div>
                    <div class="info-value ltr">{{ $reservation->slot ? \App\Support\PersianDate::time($reservation->assignedStartTime()).' - '.\App\Support\PersianDate::time($reservation->assignedEndTime()) : '-' }}</div>
                </div>
                @if($reservation->prepayment_required)
                    <div class="info-item">
                        <div class="info-label"><i class="ri-money-dollar-circle-line"></i> مبلغ پیش پرداخت</div>
                        <div class="info-value">{{ \App\Support\PersianDate::money($reservation->prepayment_amount) }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><i class="ri-hourglass-line"></i> مهلت پرداخت</div>
                        <div class="info-value">{{ \App\Support\PersianDate::dateTime($reservation->payment_deadline_at) }}</div>
                    </div>
                @endif
                @foreach($statusDates as $label => $date)
                    @if($date)
                        <div class="info-item">
                            <div class="info-label"><i class="ri-history-line"></i> {{ $label }}</div>
                            <div class="info-value">{{ \App\Support\PersianDate::dateTime($date) }}</div>
                        </div>
                    @endif
                @endforeach
            </div>

            <div class="alert alert-secondary contact-note mt-4 mb-0">
                <i class="ri-customer-service-2-line"></i>
                <div>
                    برای لغو یا تغییر زمان رزرو، لطفاً با آموزشگاه تماس بگیرید.
                    @if($settings->get('contact_phone'))
                        <span class="ltr">{{ $settings->get('contact_phone') }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Complete missing info --}}
    @if($canUpdate && count($missing) > 0)
        <div class="card">
            <div class="card-body">
                <div class="section-title"><i class="ri-edit-2-line"></i> تکمیل اطلاعات</div>
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
                    <button class="btn btn-primary mt-3">
                        <i class="ri-send-plane-line align-middle"></i> ثبت اطلاعات
                    </button>
                </form>
            </div>
        </div>
    @endif

    {{-- Upload payment receipt --}}
    @if(
        $canUpdate
        && $reservation->prepayment_required
        && count($missing) === 0
        && in_array($reservation->status, [\App\Enums\ReservationStatus::PendingPrepayment, \App\Enums\ReservationStatus::PaymentRejected], true)
    )
        <div class="card">
            <div class="card-body">
                <div class="section-title"><i class="ri-upload-cloud-2-line"></i> آپلود فیش پرداخت</div>

                @if($payment?->rejection_reason)
                    <div class="alert alert-danger">
                        <i class="ri-error-warning-line"></i>
                        <div><strong>دلیل رد فیش:</strong> {{ $payment->rejection_reason }}</div>
                    </div>
                @endif

                <form method="post" action="{{ route('public.reservations.upload-receipt', $reservation->public_token) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="upload-drop mb-3">
                        <i class="ri-image-add-line"></i>
                        <div class="upload-drop-text">تصویر فیش پرداخت را انتخاب کنید (jpg, png یا webp)</div>
                        <input type="file" name="receipt_image" class="form-control" accept=".jpg,.jpeg,.png,.webp" required>
                    </div>
                    <button class="btn btn-primary">
                        <i class="ri-send-plane-line align-middle"></i> ارسال فیش
                    </button>
                </form>
            </div>
        </div>
    @endif
@endsection
