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

.step.is-failed .step-dot{
    background:var(--danger);
    border-color:var(--danger);
    color:#fff;
    box-shadow:0 0 0 5px var(--danger-bg);
}

.step.is-failed .step-label{
    color:var(--danger);
    font-weight:700;
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
            in_array($reservation->status, [\App\Enums\ReservationStatus::Confirmed, \App\Enums\ReservationStatus::Completed], true) => 'success',
            in_array($reservation->status, [\App\Enums\ReservationStatus::Cancelled, \App\Enums\ReservationStatus::PaymentRejected, \App\Enums\ReservationStatus::NoShow], true) => 'danger',
            $reservation->status === \App\Enums\ReservationStatus::Expired || $isLinkExpired => 'neutral',
            $reservation->status === \App\Enums\ReservationStatus::PendingPaymentApproval => 'info',
            default => 'warning',
        };

        $stepIcons = [
            'created' => 'ri-file-list-3-line',
            'completion' => 'ri-user-line',
            'prepayment' => 'ri-bank-card-line',
            'receipt_approval' => 'ri-shield-check-line',
            'confirmed' => 'ri-checkbox-circle-line',
            'completed' => 'ri-check-double-line',
        ];

        $messageTone = match(true) {
            in_array($reservation->status, [\App\Enums\ReservationStatus::Cancelled, \App\Enums\ReservationStatus::PaymentRejected, \App\Enums\ReservationStatus::NoShow], true) => 'danger',
            $reservation->status === \App\Enums\ReservationStatus::Expired || $isLinkExpired => 'warning',
            in_array($reservation->status, [\App\Enums\ReservationStatus::Confirmed, \App\Enums\ReservationStatus::Completed], true) => 'success',
            default => 'info',
        };

        $studentReportCard = $reservation->reportCards->firstWhere('source', \App\Models\ReservationDocument::SOURCE_STUDENT)
            ?: $reservation->reportCards->first();
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
                @foreach($flowSteps as $step)
                    <div @class([
                        'step',
                        'is-done' => $step['state'] === 'completed',
                        'is-current' => $step['state'] === 'active',
                        'is-failed' => $step['state'] === 'failed',
                    ])>
                        <div class="step-dot"><i class="{{ $stepIcons[$step['key']] ?? 'ri-circle-line' }}"></i></div>
                        <div class="step-label">{{ $step['label'] }}</div>
                    </div>
                @endforeach
            </div>

            @if($flowMessage || $isLinkExpired)
                <div class="alert alert-{{ $messageTone }}">
                    <i class="ri-information-line"></i>
                    <div>{{ $flowMessage ?: 'مهلت تکمیل اطلاعات یا پرداخت به پایان رسیده است. لطفاً با آموزشگاه تماس بگیرید.' }}</div>
                </div>
            @endif

            @if($showCompletionWarning)
                <div class="alert alert-warning">
                    <i class="ri-error-warning-line"></i>
                    <div>
                        <div>در صورت عدم تکمیل اطلاعات رزرو شما باطل خواهد شد</div>
                        @if($reservation->public_token_expires_at)
                            <div class="mt-1">مهلت تکمیل اطلاعات تا: {{ \App\Support\PersianDate::dateTime($reservation->public_token_expires_at) }}</div>
                        @endif
                    </div>
                </div>
            @endif

            @if($showPaymentWarning)
                <div class="alert alert-warning">
                    <i class="ri-error-warning-line"></i>
                    <div>
                        <div>در صورت عدم پرداخت پیش‌پرداخت در زمان مقرر، رزرو شما باطل خواهد شد</div>
                        <div class="mt-1">مبلغ پیش‌پرداخت: {{ \App\Support\PersianDate::money($reservation->prepayment_amount) }}</div>
                        @if($reservation->payment_deadline_at)
                            <div>مهلت پرداخت تا: {{ \App\Support\PersianDate::dateTime($reservation->payment_deadline_at) }}</div>
                        @endif
                        @if($reservation->paymentCard)
                            <div class="mt-1">
                                {{ $reservation->paymentCard->bank_name }}
                                -
                                {{ $reservation->paymentCard->holder_name }}
                                -
                                <span class="ltr">{{ $reservation->paymentCard->formattedNumber() }}</span>
                            </div>
                        @endif
                    </div>
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
                    <div class="info-value">{{ $reservation->student?->examTypeLabel() ?: '-' }}</div>
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
                    @if($reservation->paymentCard)
                        <div class="info-item">
                            <div class="info-label"><i class="ri-bank-card-line"></i> شماره کارت پرداخت</div>
                            <div class="info-value ltr">{{ $reservation->paymentCard->formattedNumber() }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="ri-bank-line"></i> بانک</div>
                            <div class="info-value">{{ $reservation->paymentCard->bank_name }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="ri-user-line"></i> صاحب کارت</div>
                            <div class="info-value">{{ $reservation->paymentCard->holder_name }}</div>
                        </div>
                    @endif
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
                                <select name="major" class="form-select" required>
                                    <option value="">انتخاب کنید</option>
                                    @foreach($settings->get('majors', []) as $major)
                                        <option value="{{ $major }}" @selected(old('major') === $major)>{{ $major }}</option>
                                    @endforeach
                                </select>
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
                                <select name="exam_type[]" class="form-select" multiple size="4" required>
                                    @foreach($settings->get('exam_types', []) as $examType)
                                        <option value="{{ $examType }}" @selected(in_array($examType, old('exam_type', []), true))>{{ $examType }}</option>
                                    @endforeach
                                </select>
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

    {{-- Upload report card --}}
    @if($studentReportCard || $canUploadReportCard)
        <div class="card">
            <div class="card-body">
                <div class="section-title"><i class="ri-file-upload-line"></i> کارنامه دانش‌آموز</div>

                @if($studentReportCard)
                    <div class="alert alert-success">
                        <i class="ri-checkbox-circle-line"></i>
                        <div>
                            کارنامه شما با موفقیت ثبت شده است.
                            <div class="mt-1">
                                {{ $studentReportCard->original_name }}
                                -
                                {{ \App\Support\PersianDate::dateTime($studentReportCard->created_at) }}
                            </div>
                        </div>
                    </div>
                    <a class="btn btn-outline-primary mb-3" href="{{ route('public.reservations.report-card.show', [$reservation->public_token, $studentReportCard]) }}">
                        <i class="ri-download-line align-middle"></i> مشاهده کارنامه
                    </a>
                @endif

                @if($canUploadReportCard)
                    <form method="post" action="{{ route('public.reservations.report-card.store', $reservation->public_token) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="upload-drop mb-3">
                            <i class="ri-file-add-line"></i>
                            <div class="upload-drop-text">{{ $studentReportCard ? 'برای جایگزینی کارنامه، فایل جدید را انتخاب کنید' : 'فایل کارنامه را انتخاب کنید (jpg, jpeg, png, webp یا pdf)' }}</div>
                            <input type="file" name="report_card" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf" required>
                        </div>
                        <button class="btn btn-primary">
                            <i class="ri-upload-cloud-2-line align-middle"></i> {{ $studentReportCard ? 'جایگزینی کارنامه' : 'آپلود کارنامه' }}
                        </button>
                    </form>
                @endif
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

                <div class="alert alert-info">
                    <i class="ri-bank-card-line"></i>
                    <div>
                        لطفاً مبلغ پیش‌پرداخت را به شماره کارت زیر واریز کرده و تصویر فیش را ارسال کنید.
                        @if($reservation->paymentCard)
                            <div class="mt-2">
                                <strong>{{ $reservation->paymentCard->bank_name }}</strong>
                                -
                                {{ $reservation->paymentCard->holder_name }}
                                -
                                <span class="ltr">{{ $reservation->paymentCard->formattedNumber() }}</span>
                            </div>
                        @endif
                    </div>
                </div>

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
