@extends('layouts.public')

@section('title', 'لیست انتخاب رشته')

@push('styles')
<style>.field-columns{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}.field-table{width:100%;font-size:.8rem}.field-table th,.field-table td{padding:.5rem;border-bottom:1px solid var(--border);vertical-align:top}.field-table th{color:var(--ink-500)}@media(max-width:767.98px){.field-columns{grid-template-columns:1fr}}</style>
@endpush

@section('content')
    <div class="card mt-3"><div class="card-body"><div class="section-title"><i class="ri-list-ordered"></i> لیست انتخاب رشته</div>
        @if(! $plan)
            <div class="alert alert-info mb-0"><i class="ri-information-line"></i><div>انتخاب رشته شما هنوز توسط آموزشگاه ثبت نشده است.</div></div>
        @else
            <div class="info-grid mb-3"><div class="info-item"><div class="info-label">دانش‌آموز</div><div class="info-value">{{ $reservation->student?->full_name ?: '-' }}</div></div><div class="info-item"><div class="info-label">رشته</div><div class="info-value">{{ $reservation->student?->major ?: '-' }}</div></div><div class="info-item"><div class="info-label">نسخه</div><div class="info-value">{{ \App\Support\PersianDate::number($plan->version) }}</div></div><div class="info-item"><div class="info-label">تاریخ انتشار</div><div class="info-value">{{ \App\Support\PersianDate::dateTime($plan->published_at) }}</div></div></div>
            <a class="btn btn-outline-primary mb-3" target="_blank" href="{{ route('public.reservations.field-selection.print', $reservation->public_token) }}"><i class="ri-printer-line"></i> چاپ انتخاب رشته</a>
            <div class="field-columns">@foreach([$plan->items->take(75), $plan->items->slice(75)] as $items)<div class="table-responsive"><table class="field-table"><thead><tr><th>ردیف</th><th>کد رشته</th><th>نام رشته</th><th>شهر</th></tr></thead><tbody>@foreach($items as $item)<tr><td>{{ \App\Support\PersianDate::number($item->priority_order) }}</td><td>{{ $item->field_code }}</td><td>{{ $item->field_name }}</td><td>{{ $item->city }}</td></tr>@endforeach</tbody></table></div>@endforeach</div>
        @endif
    </div></div>
@endsection
