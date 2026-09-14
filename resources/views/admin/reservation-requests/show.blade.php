@extends('layouts.admin')

@section('title', 'مشاهده درخواست')
@section('subtitle', $reservationRequest->full_name)

@section('actions')
    <a class="btn btn-outline-secondary" href="{{ route('admin.reservation-requests.index') }}">
        <i class="ri-arrow-right-line align-middle"></i> بازگشت به لیست
    </a>
@endsection

@push('styles')
<style>

    /* ---- status banner ---- */
    .req-status {
        display: flex;
        align-items: center;
        gap: .85rem;
        padding: 1rem 1.15rem;
        margin-bottom: 1rem;
        border: 1px solid var(--border);
        border-inline-start: 4px solid var(--ink-300);
        border-radius: var(--radius-md);
        background: var(--surface);
    }

    .req-status__icon {
        flex: 0 0 auto;
        width: 46px;
        height: 46px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: var(--ink-100);
        color: var(--ink-500);
        font-size: 1.35rem;
    }

    .req-status__body { flex: 1 1 auto; min-width: 0; }

    .req-status__title {
        font-size: .95rem;
        font-weight: 700;
        color: var(--ink-900);
        line-height: 1.7;
    }

    .req-status__meta {
        margin-top: .15rem;
        font-size: .78rem;
        color: var(--ink-500);
        line-height: 1.9;
    }

    .req-status--pending   { border-inline-start-color: var(--warning); }
    .req-status--pending .req-status__icon   { background: #fbf1de; color: var(--warning); }
    .req-status--approved  { border-inline-start-color: var(--success); }
    .req-status--approved .req-status__icon  { background: #e2f5ec; color: var(--success); }
    .req-status--rejected  { border-inline-start-color: var(--danger); }
    .req-status--rejected .req-status__icon  { background: #fcecea; color: var(--danger); }
    .req-status--converted { border-inline-start-color: var(--info); }
    .req-status--converted .req-status__icon { background: #e5eefb; color: var(--info); }

    /* ---- info grid ---- */
    .req-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(215px, 1fr));
        gap: .75rem;
    }

    .req-item {
        display: flex;
        align-items: flex-start;
        gap: .65rem;
        padding: .8rem .85rem;
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        background: var(--bg);
        min-width: 0;
    }

    .req-item--full { grid-column: 1 / -1; }

    .req-item__icon {
        flex: 0 0 auto;
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        background: var(--brand-50);
        color: var(--brand-700);
        font-size: 1rem;
    }

    .req-item__body { min-width: 0; flex: 1 1 auto; }

    .req-item__label {
        display: block;
        font-size: .72rem;
        font-weight: 500;
        color: var(--ink-500);
        line-height: 1.6;
    }

    .req-item__value {
        display: block;
        margin-top: .1rem;
        font-size: .86rem;
        font-weight: 600;
        color: var(--ink-900);
        line-height: 1.8;
        overflow-wrap: anywhere;
    }

    .req-item__value.ltr { direction: ltr; text-align: right; }
    .req-item__value .muted { font-weight: 400; color: var(--ink-500); }

    .req-chip {
        display: inline-block;
        margin: .12rem .12rem 0 0;
        padding: .1rem .5rem;
        border-radius: 999px;
        background: var(--brand-50);
        color: var(--brand-700);
        font-size: .74rem;
        font-weight: 600;
    }

    .req-item--danger { border-color: #e7c3bf; background: #fcecea; }
    .req-item--danger .req-item__icon { background: #f7d9d5; color: var(--danger); }
    .req-item--danger .req-item__value { color: var(--danger); }

    /* ---- actions ---- */
    .req-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .6rem;
    }

    .req-actions form { margin: 0; }
    .req-actions .btn { min-height: 40px; }

    .req-reject-card { margin-top: .9rem; }
    .req-reject-card .form-label { font-size: .8rem; font-weight: 600; color: var(--ink-700); }

    @media (max-width: 575.98px) {
        .req-actions .btn,
        .req-actions form { width: 100%; }
        .req-actions .btn { justify-content: center; }
    }
</style>
@endpush

@section('content')
    @php
        $statuses = [
            'pending'   => ['در انتظار بررسی', 'ri-time-line', 'این درخواست هنوز بررسی نشده است.'],
            'approved'  => ['تأیید شده', 'ri-checkbox-circle-line', 'درخواست تأیید شده و آماده تبدیل به رزرو است.'],
            'rejected'  => ['رد شده', 'ri-close-circle-line', 'این درخواست رد شده است.'],
            'converted' => ['تبدیل شده به رزرو', 'ri-calendar-check-line', 'برای این درخواست رزرو ساخته شده است.'],
        ];
        $status = $statuses[$reservationRequest->status] ?? [$reservationRequest->status, 'ri-question-line', ''];
    @endphp

    <div class="req-page">

        {{-- state of the request --}}
        <div class="req-status req-status--{{ $reservationRequest->status }}">
            <span class="req-status__icon"><i class="{{ $status[1] }}"></i></span>
            <div class="req-status__body">
                <div class="req-status__title">{{ $status[0] }}</div>
                <div class="req-status__meta">
                    {{ $status[2] }}
                    <span class="d-block">
                        ثبت درخواست: {{ \App\Support\PersianDate::dateTime($reservationRequest->created_at) }}
                        @if($reservationRequest->reviewed_at)
                            · بررسی: {{ \App\Support\PersianDate::dateTime($reservationRequest->reviewed_at) }}
                            @if($reservationRequest->reviewer) توسط {{ $reservationRequest->reviewer->name }} @endif
                        @endif
                    </span>
                </div>
            </div>
        </div>

        {{-- submitted data --}}
        <div class="card mb-4">
            <div class="card-header">
                <i class="ri-file-list-3-line align-middle text-muted me-1"></i> اطلاعات درخواست
            </div>
            <div class="card-body">
                <div class="req-grid">

                    <div class="req-item">
                        <span class="req-item__icon"><i class="ri-user-line"></i></span>
                        <span class="req-item__body">
                            <span class="req-item__label">نام و نام خانوادگی</span>
                            <span class="req-item__value">{{ $reservationRequest->full_name }}</span>
                        </span>
                    </div>

                    <div class="req-item">
                        <span class="req-item__icon"><i class="ri-phone-line"></i></span>
                        <span class="req-item__body">
                            <span class="req-item__label">شماره تماس دانش‌آموز</span>
                            <span class="req-item__value ltr">{{ $reservationRequest->phone_1 }}</span>
                        </span>
                    </div>

                    <div class="req-item">
                        <span class="req-item__icon"><i class="ri-parent-line"></i></span>
                        <span class="req-item__body">
                            <span class="req-item__label">شماره تماس اولیا</span>
                            <span class="req-item__value ltr">{{ $reservationRequest->phone_2 ?: '—' }}</span>
                        </span>
                    </div>

                    <div class="req-item">
                        <span class="req-item__icon"><i class="ri-book-open-line"></i></span>
                        <span class="req-item__body">
                            <span class="req-item__label">رشته تحصیلی</span>
                            <span class="req-item__value">{{ $reservationRequest->major ?: '—' }}</span>
                        </span>
                    </div>

                    <div class="req-item">
                        <span class="req-item__icon"><i class="ri-map-pin-line"></i></span>
                        <span class="req-item__body">
                            <span class="req-item__label">سهمیه منطقه</span>
                            <span class="req-item__value">{{ $reservationRequest->region ?: '—' }}</span>
                        </span>
                    </div>

                    <div class="req-item">
                        <span class="req-item__icon"><i class="ri-award-line"></i></span>
                        <span class="req-item__body">
                            <span class="req-item__label">سهمیه خاص</span>
                            <span class="req-item__value">
                                {{ $reservationRequest->special_quota ?: '—' }}
                                @if($reservationRequest->special_quota_other)
                                    <span class="muted">({{ $reservationRequest->special_quota_other }})</span>
                                @endif
                            </span>
                        </span>
                    </div>

                    <div class="req-item req-item--full">
                        <span class="req-item__icon"><i class="ri-graduation-cap-line"></i></span>
                        <span class="req-item__body">
                            <span class="req-item__label">نوع کنکور</span>
                            <span class="req-item__value">
                                @forelse(collect($reservationRequest->exam_type)->filter() as $examType)
                                    <span class="req-chip">{{ $examType }}</span>
                                @empty
                                    —
                                @endforelse
                            </span>
                        </span>
                    </div>

                    @if($reservationRequest->rejection_reason)
                        <div class="req-item req-item--full req-item--danger">
                            <span class="req-item__icon"><i class="ri-close-circle-line"></i></span>
                            <span class="req-item__body">
                                <span class="req-item__label">دلیل رد درخواست</span>
                                <span class="req-item__value">{{ $reservationRequest->rejection_reason }}</span>
                            </span>
                        </div>
                    @endif

                </div>
            </div>
        </div>

        {{-- what can be done next --}}
        @if($reservationRequest->isPending())
            <div class="req-actions">
                @can('approve_reservation_requests')
                    <form method="post" action="{{ route('admin.reservation-requests.approve', $reservationRequest) }}">
                        @csrf
                        <button class="btn btn-success"><i class="ri-check-line align-middle"></i> تأیید درخواست</button>
                    </form>
                @endcan
                @can('reject_reservation_requests')
                    <button class="btn btn-outline-danger" type="button" data-bs-toggle="collapse" data-bs-target="#reject-form">
                        <i class="ri-close-line align-middle"></i> رد درخواست
                    </button>
                @endcan
            </div>

            @can('reject_reservation_requests')
                <form method="post" action="{{ route('admin.reservation-requests.reject', $reservationRequest) }}"
                      id="reject-form" class="collapse card card-body req-reject-card">
                    @csrf
                    <label class="form-label" for="rejection_reason">دلیل رد درخواست</label>
                    <textarea name="rejection_reason" id="rejection_reason" class="form-control" rows="3"
                              placeholder="دلیل رد را برای ثبت در سوابق بنویسید" required>{{ old('rejection_reason') }}</textarea>
                    @error('rejection_reason')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                    <button class="btn btn-danger align-self-start mt-3">
                        <i class="ri-close-circle-line align-middle"></i> ثبت رد درخواست
                    </button>
                </form>
            @endcan
        @elseif($reservationRequest->isApproved())
            <div class="req-actions">
                @can('convert_reservation_requests')
                    <form method="post" action="{{ route('admin.reservation-requests.convert', $reservationRequest) }}">
                        @csrf
                        <button class="btn btn-primary"><i class="ri-calendar-check-line align-middle"></i> تبدیل به رزرو</button>
                    </form>
                @endcan
            </div>
        @elseif($reservationRequest->convertedReservation)
            <div class="req-actions">
                <a class="btn btn-outline-primary" href="{{ route('admin.reservations.show', $reservationRequest->convertedReservation) }}">
                    <i class="ri-external-link-line align-middle"></i> مشاهده رزرو ایجاد شده
                </a>
            </div>
        @endif

    </div>
@endsection
