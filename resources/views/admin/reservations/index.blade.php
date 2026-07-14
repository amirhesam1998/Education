@extends('layouts.admin')

@section('title', 'رزروها')

@section('actions')
@can('create_reservations')
<a class="btn btn-primary" href="{{ route('admin.reservations.create') }}">
    <i class="ri-add-line align-middle"></i> رزرو جدید
</a>
@endcan
@endsection

@push('styles')
<style>
    /* ===========================================================
       Reservations index — page-local styles
    =========================================================== */
    .filter-card .form-label {
        font-size: .78rem;
        font-weight: 600;
        color: var(--ink-500);
        margin-bottom: .35rem;
    }

    .filter-card .btn {
        height: 38px;
    }

    .student-cell .student-name {
        font-weight: 600;
        color: var(--ink-900);
        font-size: .87rem;
    }

    .student-cell .student-phone {
        font-size: .78rem;
        color: var(--ink-500);
        direction: ltr;
        text-align: right;
    }

    .slot-cell .slot-date {
        font-weight: 600;
        font-size: .85rem;
    }

    .slot-cell .slot-time {
        font-size: .78rem;
        color: var(--ink-500);
        direction: ltr;
        text-align: right;
    }

    .badge.text-bg-light {
        background: var(--ink-100) !important;
        color: var(--ink-700) !important;
        border: 1px solid var(--border);
    }

    .row-actions {
        display: flex;
        gap: .4rem;
        flex-wrap: wrap;
    }

    .row-actions .btn {
        border-radius: var(--radius-sm);
        font-size: .78rem;
    }

    .pagination-wrap {
        display: flex;
        justify-content: center;
        padding: .9rem;
    }

    .pagination {
        margin: 0;
    }

    .page-link {
        border: 1px solid var(--border);
        color: var(--ink-700);
        border-radius: var(--radius-sm) !important;
        margin: 0 .15rem;
    }

    .page-item.active .page-link {
        background: var(--brand-500);
        border-color: var(--brand-500);
    }

    .empty-state {
        text-align: center;
        padding: 2.5rem 1rem;
        color: var(--ink-500);
    }

    .empty-state i {
        font-size: 2.2rem;
        color: var(--ink-300);
        margin-bottom: .5rem;
        display: block;
    }

    @media (max-width: 767.98px) {
        .filter-card .col-md-2 {
            margin-bottom: .5rem;
        }
    }
</style>
@endpush

@section('content')

{{-- Filters --}}
<div class="card filter-card mb-3">
    <div class="card-body">
        <form class="row g-3" method="get">
            <div class="col-md-4 col-sm-6">
                <label class="form-label"><i class="ri-flag-line align-middle"></i> وضعیت</label>
                <select name="status" class="form-select">
                    <option value="">همه</option>
                    @foreach($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(request('status')===$value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 col-sm-6">
                <label class="form-label"><i class="ri-calendar-line align-middle"></i> تاریخ</label>
                <input type="text" name="date" value="{{ \App\Support\PersianDate::inputDate(request('date')) }}"
                    class="form-control jalali-date-picker" autocomplete="off">
            </div>
            <div class="col-md-4 col-sm-6">
                <label class="form-label"><i class="ri-user-line align-middle"></i> مشاور</label>
                <select name="advisor_id" class="form-select">
                    <option value="">همه</option>
                    @foreach($advisors as $advisor)
                    <option value="{{ $advisor->id }}" @selected(request('advisor_id')==$advisor->id)>{{ $advisor->name
                        }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 col-sm-6">
                <label class="form-label"><i class="ri-graduation-cap-line align-middle"></i> نام دانش آموز</label>
                <input name="student_name" value="{{ request('student_name') }}" class="form-control">
            </div>
            <div class="col-md-4 col-sm-6">
                <label class="form-label"><i class="ri-phone-line align-middle"></i> شماره تماس</label>
                <input name="phone" value="{{ request('phone') }}" class="form-control ltr">
            </div>
            <div class="col-12 d-flex align-items-end gap-2">
                <button class="btn btn-primary flex-grow-1">
                    <i class="ri-filter-3-line align-middle"></i> فیلتر
                </button>
                <a class="btn btn-outline-secondary" href="{{ route('admin.reservations.index') }}" title="پاکسازی">
                    <i class="ri-refresh-line align-middle"></i>
                </a>
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>دانش آموز</th>
                    <th>تایم</th>
                    <th>مشاور</th>
                    <th>وضعیت رزرو</th>
                    <th>پرداخت</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reservations as $reservation)
                <tr>
                    <td class="student-cell">
                        <div class="student-name">{{ $reservation->student?->full_name ?: 'نامشخص' }}</div>
                        <div class="student-phone">{{ $reservation->student?->phones->pluck('phone')->implode(' / ') }}</div>
                        <div class="student-phone">{{ $reservation->student?->examTypeLabel() }}</div>
                    </td>
                    <td class="slot-cell">
                        @if($reservation->slot)
                        <div class="slot-date">{{ \App\Support\PersianDate::date($reservation->slot->date) }}</div>
                        <div class="slot-time">
                            {{ \App\Support\PersianDate::time($reservation->assignedStartTime()) }}
                            —
                            {{ \App\Support\PersianDate::time($reservation->assignedEndTime()) }}
                        </div>
                        @else
                        <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>{{ $reservation->advisor?->name ?: $reservation->slot?->advisor?->name }}</td>
                    <td><span class="badge text-bg-light">{{ $reservation->status->label() }}</span></td>
                    <td>{{ $reservation->payment?->status?->label() ?: '-' }}</td>
                    <td>
                        <div class="row-actions">
                            <a class="btn btn-sm btn-outline-info"
                                href="{{ route('admin.reservations.show', $reservation) }}">
                                <i class="ri-eye-line align-middle"></i> جزئیات
                            </a>
                            @can('update_reservations')
                            <a class="btn btn-sm btn-outline-primary"
                                href="{{ route('admin.reservations.edit', $reservation) }}">
                                <i class="ri-edit-line align-middle"></i> ویرایش
                            </a>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <i class="ri-file-list-3-line"></i>
                            رزروی یافت نشد
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($reservations->hasPages())
    <div class="pagination-wrap">{{ $reservations->links() }}</div>
    @endif
</div>
@endsection
