@extends('layouts.admin')

@section('title', 'داشبورد')
@section('subtitle', 'نمای کلی وضعیت سیستم')

@push('styles')
<style>
    /* ===========================================================
       Dashboard-only styles
    =========================================================== */
    .stat-grid{
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .stat-card{
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        padding: 1.25rem;
        box-shadow: var(--shadow-sm);
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: .75rem;
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .stat-card:hover{
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .stat-icon{
        width: 46px; height: 46px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.3rem;
        flex-shrink: 0;
    }
    .stat-icon.tone-brand  { background: var(--brand-100); color: var(--brand-700); }
    .stat-icon.tone-info   { background: #e5eefb; color: var(--info); }
    .stat-icon.tone-warn   { background: #fbf1de; color: var(--warning); }
    .stat-icon.tone-danger { background: #fbe6e4; color: var(--danger); }

    .stat-value{
        font-size: 1.55rem;
        font-weight: 700;
        color: var(--ink-900);
        line-height: 1.3;
    }
    .stat-label{
        font-size: .8rem;
        color: var(--ink-500);
        margin-top: .15rem;
    }
    .stat-trend{
        font-size: .74rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: .2rem;
        margin-top: .5rem;
    }
    .stat-trend.up{ color: var(--success); }
    .stat-trend.down{ color: var(--danger); }
    .stat-trend.neutral{ color: var(--ink-500); }

    .panel-row{
        display: grid;
        grid-template-columns: 1.6fr 1fr;
        gap: 1rem;
        align-items: start;
    }

    .empty-state{
        text-align: center;
        padding: 2.5rem 1rem;
        color: var(--ink-500);
    }
    .empty-state i{ font-size: 2.2rem; color: var(--ink-300); margin-bottom: .5rem; display: block; }

    .agenda-item{
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .85rem 0;
        border-bottom: 1px solid var(--border);
    }
    .agenda-item:last-child{ border-bottom: none; }

    .agenda-time{
        width: 58px;
        flex-shrink: 0;
        text-align: center;
        font-weight: 700;
        font-size: .82rem;
        color: var(--brand-700);
        background: var(--brand-50);
        border-radius: var(--radius-sm);
        padding: .4rem 0;
    }

    .agenda-title{ font-size: .87rem; font-weight: 600; color: var(--ink-900); }
    .agenda-sub{ font-size: .78rem; color: var(--ink-500); }

    @media (max-width: 1199.98px){
        .stat-grid{ grid-template-columns: repeat(2, 1fr); }
        .panel-row{ grid-template-columns: 1fr; }
    }
    @media (max-width: 575.98px){
        .stat-grid{ grid-template-columns: 1fr; }
        .stat-value{ font-size: 1.3rem; }
    }
</style>
@endpush

@section('content')

    {{-- Stat cards --}}
    <div class="stat-grid">
        <div class="stat-card">
            <div>
                <div class="stat-value">{{ \App\Support\PersianDate::number($stats['today_reservations']['value'] ?? 0) }}</div>
                <div class="stat-label">{{ $stats['today_reservations']['label'] ?? 'رزرو امروز' }}</div>
                <div class="stat-trend {{ $stats['today_reservations']['trend']['tone'] ?? 'neutral' }}">
                    <i class="{{ $stats['today_reservations']['trend']['icon'] ?? 'ri-subtract-line' }}"></i>
                    {{ \App\Support\PersianDate::number($stats['today_reservations']['trend']['text'] ?? '') }}
                </div>
            </div>
            <div class="stat-icon tone-brand"><i class="ri-calendar-check-line"></i></div>
        </div>

        @if($canViewPaymentInfo)<div class="stat-card">
            <div>
                <div class="stat-value">{{ \App\Support\PersianDate::number($stats['pending_payments']['value'] ?? 0) }}</div>
                <div class="stat-label">{{ $stats['pending_payments']['label'] ?? 'فیش در انتظار تایید' }}</div>
                <div class="stat-trend {{ $stats['pending_payments']['trend']['tone'] ?? 'neutral' }}">
                    <i class="{{ $stats['pending_payments']['trend']['icon'] ?? 'ri-information-line' }}"></i>
                    {{ \App\Support\PersianDate::number($stats['pending_payments']['trend']['text'] ?? '') }}
                </div>
            </div>
            <div class="stat-icon tone-warn"><i class="ri-bank-card-line"></i></div>
        </div>@endif

        <div class="stat-card">
            <div>
                <div class="stat-value">{{ \App\Support\PersianDate::number($stats['available_intervals']['value'] ?? 0) }}</div>
                <div class="stat-label">{{ $stats['available_intervals']['label'] ?? 'تایم خالی این هفته' }}</div>
                <div class="stat-trend {{ $stats['available_intervals']['trend']['tone'] ?? 'neutral' }}">
                    <i class="{{ $stats['available_intervals']['trend']['icon'] ?? 'ri-subtract-line' }}"></i>
                    {{ \App\Support\PersianDate::number($stats['available_intervals']['trend']['text'] ?? '') }}
                </div>
            </div>
            <div class="stat-icon tone-info"><i class="ri-time-line"></i></div>
        </div>

        <div class="stat-card">
            <div>
                <div class="stat-value">{{ \App\Support\PersianDate::number($stats['active_reservations']['value'] ?? 0) }}</div>
                <div class="stat-label">{{ $stats['active_reservations']['label'] ?? 'رزروهای فعال' }}</div>
                <div class="stat-trend {{ $stats['active_reservations']['trend']['tone'] ?? 'neutral' }}">
                    <i class="{{ $stats['active_reservations']['trend']['icon'] ?? 'ri-information-line' }}"></i>
                    {{ \App\Support\PersianDate::number($stats['active_reservations']['trend']['text'] ?? '') }}
                </div>
            </div>
            <div class="stat-icon tone-danger"><i class="ri-user-heart-line"></i></div>
        </div>
        @can('view_reservation_requests')
            <a class="stat-card text-decoration-none" href="{{ route('admin.reservation-requests.index', ['status' => 'pending']) }}">
                <div>
                    <div class="stat-value">{{ \App\Support\PersianDate::number($pendingReservationRequests) }}</div>
                    <div class="stat-label">درخواست‌های رزرو در انتظار بررسی</div>
                    <div class="stat-trend neutral"><i class="ri-arrow-left-line"></i> مشاهده درخواست‌ها</div>
                </div>
                <div class="stat-icon tone-warn"><i class="ri-user-add-line"></i></div>
            </a>
        @endcan
    </div>

    @if($canViewPaymentInfo)<div class="card mb-4">
        <div class="card-header"><i class="ri-bank-card-line align-middle text-muted me-1"></i> وضعیت پرداخت رزروهای ثبت‌شده توسط شما</div>
        <div class="card-body">
            <div class="stat-grid mb-0">
                @foreach(['paid' => ['پرداخت شده', 'ri-checkbox-circle-line', 'tone-brand'], 'unpaid' => ['پرداخت نشده', 'ri-money-dollar-circle-line', 'tone-danger'], 'pending_payment' => ['در انتظار پرداخت', 'ri-time-line', 'tone-warn'], 'pending_approval' => ['در انتظار تأیید فیش', 'ri-file-search-line', 'tone-info'], 'rejected' => ['رد شده', 'ri-close-circle-line', 'tone-danger'], 'expired' => ['منقضی شده', 'ri-calendar-close-line', 'tone-warn']] as $key => [$label, $icon, $tone])
                    <div class="stat-card"><div><div class="stat-value">{{ \App\Support\PersianDate::number($creatorPaymentSummary[$key] ?? 0) }}</div><div class="stat-label">{{ $label }}</div></div><div class="stat-icon {{ $tone }}"><i class="{{ $icon }}"></i></div></div>
                @endforeach
            </div>
        </div>
    </div>@endif

    <div class="card mb-4">
        <div class="card-header"><i class="ri-bar-chart-grouped-line align-middle text-muted me-1"></i> آمار مشاوره‌های انجام‌شده</div>
        <div class="table-responsive"><table class="table mb-0"><thead><tr><th>مشاور</th><th>تعداد مشاوره انجام‌شده</th></tr></thead><tbody>@forelse($completedConsultationStats as $stat)<tr><td>{{ $stat['advisor_name'] }}</td><td>{{ \App\Support\PersianDate::number($stat['completed_count']) }}</td></tr>@empty<tr><td colspan="2" class="text-center text-muted py-4">هنوز مشاوره انجام‌شده‌ای ثبت نشده است.</td></tr>@endforelse</tbody></table></div>
    </div>

    @can('view_operator_field_selection_stats')
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center"><span><i class="ri-bar-chart-box-line align-middle text-muted me-1"></i> آمار انتخاب رشته اپراتورها</span><a href="{{ route('admin.reports.operator-field-selection.index') }}" class="small">مشاهده گزارش کامل</a></div>
            <div class="card-body"><div class="stat-grid mb-0"><div class="stat-card"><div><div class="stat-value">{{ \App\Support\PersianDate::number(collect($operatorFieldSelectionStats)->sum('created_plans_count')) }}</div><div class="stat-label">ایجاد شده</div></div><div class="stat-icon tone-brand"><i class="ri-add-circle-line"></i></div></div><div class="stat-card"><div><div class="stat-value">{{ \App\Support\PersianDate::number(collect($operatorFieldSelectionStats)->sum('edit_actions_count')) }}</div><div class="stat-label">ویرایش شده</div></div><div class="stat-icon tone-info"><i class="ri-edit-line"></i></div></div></div></div>
        </div>
    @endcan

    <div class="panel-row">
        {{-- Recent reservations table --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="ri-file-list-3-line align-middle text-muted me-1"></i> آخرین رزروها</span>
                <a href="{{ route('admin.reservations.index') }}" class="small">مشاهده همه</a>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>مراجع</th>
                            <th>مشاور</th>
                            <th>تاریخ و ساعت</th>
                            <th>وضعیت</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($latestReservations as $reservation)
                            @php
                                $statusColor = match ($reservation->status) {
                                    \App\Enums\ReservationStatus::Confirmed, \App\Enums\ReservationStatus::Completed => 'success',
                                    \App\Enums\ReservationStatus::PendingCompletion,
                                    \App\Enums\ReservationStatus::PendingPrepayment,
                                    \App\Enums\ReservationStatus::PendingPaymentApproval => 'warning',
                                    \App\Enums\ReservationStatus::PaymentRejected,
                                    \App\Enums\ReservationStatus::Cancelled,
                                    \App\Enums\ReservationStatus::Expired,
                                    \App\Enums\ReservationStatus::NoShow => 'danger',
                                    default => 'secondary',
                                };
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('admin.reservations.show', $reservation) }}">
                                        {{ $reservation->student?->full_name ?: 'رزرو #'.$reservation->id }}
                                    </a>
                                </td>
                                <td>{{ $reservation->advisor?->name ?: $reservation->slot?->advisor?->name ?: '-' }}</td>
                                <td>
                                    {{ $reservation->slot ? \App\Support\PersianDate::date($reservation->slot->date) : '-' }}
                                    @if($reservation->assignedStartTime() && $reservation->assignedEndTime())
                                        <span class="ltr d-inline-block">
                                            {{ \App\Support\PersianDate::time($reservation->assignedStartTime()) }}
                                            تا
                                            {{ \App\Support\PersianDate::time($reservation->assignedEndTime()) }}
                                        </span>
                                    @endif
                                </td>
                                <td><span class="badge bg-{{ $statusColor }}">{{ $reservation->status->label() }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <div class="empty-state">
                                        <i class="ri-file-list-3-line"></i>
                                        هنوز رزروی ثبت نشده است.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Today's agenda --}}
        <div class="card">
            <div class="card-header">
                <i class="ri-calendar-2-line align-middle text-muted me-1"></i> برنامه امروز
            </div>
            <div class="p-3">
                @forelse($todayReservations as $reservation)
                    @php
                        $statusColor = match ($reservation->status) {
                            \App\Enums\ReservationStatus::Confirmed, \App\Enums\ReservationStatus::Completed => 'success',
                            \App\Enums\ReservationStatus::PendingCompletion,
                            \App\Enums\ReservationStatus::PendingPrepayment,
                            \App\Enums\ReservationStatus::PendingPaymentApproval => 'warning',
                            default => 'secondary',
                        };
                    @endphp
                    <div class="agenda-item">
                        <div class="agenda-time ltr">
                            {{ \App\Support\PersianDate::time($reservation->assignedStartTime()) }}
                            تا
                            {{ \App\Support\PersianDate::time($reservation->assignedEndTime()) }}
                        </div>
                        <div>
                            <div class="agenda-title">
                                {{ $reservation->student?->full_name ?: 'رزرو #'.$reservation->id }}
                                <span class="badge bg-{{ $statusColor }}">{{ $reservation->status->label() }}</span>
                            </div>
                            <div class="agenda-sub">{{ $reservation->advisor?->name ?: $reservation->slot?->advisor?->name ?: '-' }}</div>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">
                        <i class="ri-calendar-line"></i>
                        برای امروز برنامه‌ای ثبت نشده است.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

@endsection
