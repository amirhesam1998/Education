@extends('layouts.admin')

@section('title', 'تایمها')

@section('actions')
    @can('create_slots')
        <a class="btn btn-primary" href="{{ route('admin.slots.create') }}">
            <i class="ri-add-line align-middle"></i> ایجاد تایم
        </a>
    @endcan
@endsection

@push('styles')
<style>
    /* ===========================================================
       Slots index — page-local styles
    =========================================================== */
    .filter-card .card-body{ padding: 1.1rem 1.25rem; }
    .filter-card .form-label{
        font-size: .78rem;
        font-weight: 600;
        color: var(--ink-500);
        margin-bottom: .35rem;
    }
    .filter-card .btn{ height: 40px; }

    .advisor-cell{ display: flex; align-items: center; gap: .55rem; }
    .advisor-avatar{
        width: 30px; height: 30px;
        border-radius: 50%;
        background: var(--brand-100);
        color: var(--brand-700);
        display: flex; align-items: center; justify-content: center;
        font-size: .78rem; font-weight: 700;
        flex-shrink: 0;
    }

    .row-actions{ display: flex; gap: .4rem; flex-wrap: nowrap; }
    .row-actions .btn{
        width: 34px; height: 34px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--radius-sm);
        font-size: 1rem;
    }
    .row-actions form{ margin: 0; }

    /* Badge tones for the raw text-bg-* classes used by the backend */
    .badge.text-bg-secondary{ background: var(--ink-100) !important; color: var(--ink-700) !important; }
    .badge.text-bg-success  { background: #e2f5ec !important; color: var(--success) !important; }
    .badge.text-bg-warning  { background: #fbf1de !important; color: var(--warning) !important; }
    .badge.text-bg-danger   { background: #fbe6e4 !important; color: var(--danger) !important; }
    .badge.text-bg-info     { background: #e5eefb !important; color: var(--info) !important; }

    .capacity-pill{
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        font-size: .82rem;
        color: var(--ink-700);
    }
    .capacity-pill i{ color: var(--ink-500); }

    .empty-state{
        text-align: center;
        padding: 2.5rem 1rem;
        color: var(--ink-500);
    }
    .empty-state i{ font-size: 2.2rem; color: var(--ink-300); margin-bottom: .5rem; display: block; }

    .pagination-wrap{
        display: flex;
        justify-content: center;
        padding: .9rem;
    }
    .pagination{ margin: 0; }
    .page-link{
        border: 1px solid var(--border);
        color: var(--ink-700);
        border-radius: var(--radius-sm) !important;
        margin: 0 .15rem;
    }
    .page-item.active .page-link{
        background: var(--brand-500);
        border-color: var(--brand-500);
    }

    @media (max-width: 767.98px){
        .filter-card .col-md-3{ margin-bottom: .5rem; }
        .row-actions{ flex-wrap: wrap; }
    }
</style>
@endpush

@section('content')

    {{-- Filters --}}
    <div class="card filter-card mb-3">
        <div class="card-body">
            <form class="row g-3" method="get">
                <div class="col-md-3 col-sm-6">
                    <label class="form-label"><i class="ri-calendar-line align-middle"></i> تاریخ</label>
                    <input type="text" name="date" value="{{ \App\Support\PersianDate::inputDate(request('date')) }}" class="form-control jalali-date-picker" autocomplete="off" placeholder="انتخاب تاریخ">
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label"><i class="ri-user-line align-middle"></i> مشاور</label>
                    <select name="advisor_id" class="form-select">
                        <option value="">همه</option>
                        @foreach($advisors as $advisor)
                            <option value="{{ $advisor->id }}" @selected(request('advisor_id') == $advisor->id)>{{ $advisor->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label"><i class="ri-flag-line align-middle"></i> وضعیت</label>
                    <select name="status" class="form-select">
                        <option value="">همه</option>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-sm-6 d-flex align-items-end gap-2">
                    <button class="btn btn-primary flex-grow-1">
                        <i class="ri-filter-3-line align-middle"></i> فیلتر
                    </button>
                    <a class="btn btn-outline-secondary" href="{{ route('admin.slots.index') }}" title="پاکسازی">
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
                    <th>تاریخ</th>
                    <th>ساعت</th>
                    <th>مشاور</th>
                    <th>ظرفیت</th>
                    <th>رزرو فعال</th>
                    <th>وضعیت تایم</th>
                    <th>عملیات</th>
                </tr>
                </thead>
                <tbody>
                @forelse($slots as $slot)
                    @php
                        $activeCount = $availability->countActiveReservations($slot);
                        $isAvailable = $availability->isAvailable($slot);
                        $advisorInitial = mb_substr($slot->advisor?->name ?? '-', 0, 1);
                    @endphp
                    <tr>
                        <td class="text-nowrap">{{ \App\Support\PersianDate::date($slot->date) }}</td>
                        <td class="text-nowrap ltr">{{ \App\Support\PersianDate::time($slot->start_time) }} — {{ \App\Support\PersianDate::time($slot->end_time) }}</td>
                        <td>
                            <div class="advisor-cell">
                                <span class="advisor-avatar">{{ $advisorInitial }}</span>
                                <span>{{ $slot->advisor?->name ?: '-' }}</span>
                            </div>
                        </td>
                        <td><span class="capacity-pill"><i class="ri-group-line"></i> {{ \App\Support\PersianDate::number($slot->capacity) }}</span></td>
                        <td><span class="capacity-pill"><i class="ri-checkbox-circle-line"></i> {{ \App\Support\PersianDate::number($activeCount) }}</span></td>
                        <td>
                            @if($slot->status->value !== 'active')
                                <span class="badge text-bg-secondary">غیرفعال</span>
                            @elseif($isAvailable)
                                <span class="badge text-bg-success">آزاد</span>
                            @else
                                <span class="badge text-bg-warning">رزرو شده / قفل</span>
                            @endif
                        </td>
                        <td>
                            <div class="row-actions">
                                <a class="btn btn-outline-info" href="{{ route('admin.slots.show', $slot) }}" title="مشاهده">
                                    <i class="ri-eye-line"></i>
                                </a>
                                @can('update_slots')
                                    <a class="btn btn-outline-primary" href="{{ route('admin.slots.edit', $slot) }}" title="ویرایش">
                                        <i class="ri-edit-line"></i>
                                    </a>
                                @endcan
                                @can('delete_slots')
                                    <form method="post" action="{{ route('admin.slots.destroy', $slot) }}" onsubmit="return confirm('حذف شود؟')">
                                        @csrf
                                        @method('delete')
                                        <button class="btn btn-outline-danger" title="حذف">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <i class="ri-calendar-close-line"></i>
                                تایمی یافت نشد
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($slots->hasPages())
            <div class="pagination-wrap">{{ $slots->links() }}</div>
        @endif
    </div>
@endsection
