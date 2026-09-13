@extends('layouts.admin')

@section('title', 'انتخاب کنکور')
@section('subtitle', 'نوع کنکور را انتخاب کنید تا لیست انتخاب رشته همان کنکور باز شود.')

@section('actions')
    <a class="btn btn-outline-secondary" href="{{ route('admin.reservations.show', $reservation) }}">
        <i class="ri-arrow-right-line align-middle"></i> بازگشت به رزرو
    </a>
@endsection

@push('styles')
<style>
    .exam-picker-card {  margin-inline: auto; }

    .exam-picker-intro {
        display: flex;
        align-items: flex-start;
        gap: .65rem;
        margin-bottom: 1.25rem;
        padding: .9rem 1rem;
        border-radius: var(--radius-md);
        background: var(--brand-50);
        color: var(--brand-700);
        font-size: .84rem;
        line-height: 1.9;
    }

    .exam-picker-intro i { font-size: 1.05rem; margin-top: .1rem; }

    .exam-option-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(232px, 1fr));
        gap: .85rem;
    }

    .exam-option-form { margin: 0; display: block; height: 100%; }

    .exam-option {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        gap: .8rem;
        padding: 1rem;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: var(--surface);
        color: var(--ink-900);
        text-align: right;
        cursor: pointer;
        transition: border-color .18s ease, background-color .18s ease, box-shadow .18s ease, transform .18s ease;
    }

    .exam-option:hover {
        border-color: var(--brand-500);
        background: var(--brand-50);
        box-shadow: 0 0 0 3px rgba(47, 143, 131, .1);
        transform: translateY(-1px);
    }

    .exam-option:focus-visible {
        outline: none;
        border-color: var(--brand-600);
        box-shadow: 0 0 0 3px rgba(47, 143, 131, .18);
    }

    .exam-option__icon {
        flex: 0 0 auto;
        width: 44px;
        height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: var(--brand-100);
        color: var(--brand-700);
        font-size: 1.25rem;
    }

    .exam-option__body { flex: 1 1 auto; min-width: 0; }

    .exam-option__title {
        display: block;
        font-size: .92rem;
        font-weight: 700;
        line-height: 1.7;
    }

    .exam-option__meta {
        display: block;
        margin-top: .15rem;
        font-size: .74rem;
        color: var(--ink-500);
    }

    .exam-option__go {
        flex: 0 0 auto;
        color: var(--ink-300);
        font-size: 1.1rem;
        transition: color .18s ease;
    }

    .exam-option:hover .exam-option__go { color: var(--brand-600); }

    .exam-state {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        margin-top: .3rem;
        padding: .15rem .5rem;
        border-radius: 999px;
        font-size: .7rem;
        font-weight: 600;
    }

    .exam-state--draft { background: #fbf1de; color: var(--warning); }
    .exam-state--published { background: #e2f5ec; color: var(--success); }
    .exam-state--archived { background: var(--ink-100); color: var(--ink-500); }
    .exam-state--new { background: var(--brand-50); color: var(--brand-700); }

    .exam-empty {
        text-align: center;
        padding: 2.25rem 1rem .5rem;
        color: var(--ink-500);
    }

    .exam-empty__icon {
        width: 68px;
        height: 68px;
        margin: 0 auto 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: #fbf1de;
        color: var(--warning);
        font-size: 1.9rem;
    }

    .exam-empty__title {
        font-size: .98rem;
        font-weight: 700;
        color: var(--ink-900);
        margin-bottom: .4rem;
    }

    .exam-empty__text { font-size: .85rem; line-height: 2; margin-bottom: 1.1rem; }

    @media (max-width: 575.98px) {
        .exam-option-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
    @php
        // Highest-version plan per exam type, so the picker can show what already exists.
        $plansByExamType = $reservation->fieldSelectionPlans()->get()->keyBy('exam_type_key');

        $examIcon = function (string $label): string {
            return match (true) {
                str_contains($label, 'تجربی') => 'ri-flask-line',
                str_contains($label, 'ریاضی') => 'ri-calculator-line',
                str_contains($label, 'انسانی') => 'ri-book-open-line',
                str_contains($label, 'هنر') => 'ri-palette-line',
                str_contains($label, 'زبان') => 'ri-translate-2',
                default => 'ri-graduation-cap-line',
            };
        };
    @endphp

    <div class="card exam-picker-card">
        <div class="card-header">
            <i class="ri-graduation-cap-line"></i> انتخاب کنکور
        </div>
        <div class="card-body">

            @if(count($examTypeOptions))
                <div class="exam-picker-intro">
                    <i class="ri-information-line"></i>
                    <div>
                        برای هر نوع کنکور یک لیست انتخاب رشته جداگانه نگهداری می‌شود.
                        با انتخاب هر مورد، لیست همان کنکور باز می‌شود و اگر قبلاً ساخته نشده باشد ساخته می‌شود.
                    </div>
                </div>

                <div class="exam-option-grid">
                    @foreach($examTypeOptions as $key => $label)
                        @php $plan = $plansByExamType->get($key); @endphp
                        <form method="post"
                              action="{{ route('admin.reservations.field-selection.store', $reservation) }}"
                              class="exam-option-form">
                            @csrf
                            <input type="hidden" name="exam_type_key" value="{{ $key }}">
                            <button type="submit" class="exam-option">
                                <span class="exam-option__icon"><i class="{{ $examIcon($label) }}"></i></span>
                                <span class="exam-option__body">
                                    {{-- labels already read like "کنکور ریاضی", so no prefix here --}}
                                    <span class="exam-option__title">{{ $label }}</span>
                                    @if($plan)
                                        <span class="exam-option__meta">
                                            نسخه {{ \App\Support\PersianDate::number($plan->version) }}
                                            · {{ \App\Support\PersianDate::number($plan->items()->count()) }} رشته‌محل
                                        </span>
                                        <span class="exam-state exam-state--{{ $plan->status }}">
                                            <i class="ri-circle-fill" style="font-size:.5rem"></i>
                                            @switch($plan->status)
                                                @case(\App\Models\FieldSelectionPlan::STATUS_PUBLISHED) منتشر شده @break
                                                @case(\App\Models\FieldSelectionPlan::STATUS_ARCHIVED) آرشیو شده @break
                                                @default پیش‌نویس
                                            @endswitch
                                        </span>
                                    @else
                                        <span class="exam-state exam-state--new">
                                            <i class="ri-add-line" style="font-size:.7rem"></i> هنوز ساخته نشده
                                        </span>
                                    @endif
                                </span>
                                <span class="exam-option__go"><i class="ri-arrow-left-s-line"></i></span>
                            </button>
                        </form>
                    @endforeach
                </div>
            @else
                <div class="exam-empty">
                    <div class="exam-empty__icon"><i class="ri-error-warning-line"></i></div>
                    <div class="exam-empty__title">نوع کنکور برای این رزرو ثبت نشده است</div>
                    <div class="exam-empty__text">
                        برای ساخت لیست انتخاب رشته، ابتدا باید نوع کنکور دانش‌آموز در اطلاعات رزرو ثبت شود.
                    </div>
                    @can('update_reservations')
                        <a class="btn btn-primary" href="{{ route('admin.reservations.edit', $reservation) }}">
                            <i class="ri-edit-line align-middle"></i> ویرایش اطلاعات رزرو
                        </a>
                    @endcan
                </div>
            @endif

        </div>
    </div>
@endsection
