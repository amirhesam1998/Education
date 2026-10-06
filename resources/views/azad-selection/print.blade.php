@php
    $student = $plan->student;
    $reservation = $plan->reservation;
    $studentInfo = [
        'نام دانش‌آموز' => $student?->full_name ?: '-',
        'رشته' => $student?->major ?: '-',
        'منطقه' => $student?->region ?: '-',
        'تراز / رتبه' => $student?->score ?: '-',
        'زمان رزرو' => $reservation?->slot?->date
            ? \App\Support\PersianDate::date($reservation->slot->date).' - '.\App\Support\PersianDate::time($reservation->assignedStartTime())
            : '-',
        'تاریخ انتشار' => \App\Support\PersianDate::dateTime($plan->published_at ?: $plan->created_at),
        'تعداد رشته‌محل‌ها' => \App\Support\PersianDate::number($plan->items->count()),
    ];
    $hasNotes = $plan->items->contains(fn ($item) => filled($item->note));
@endphp
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>چاپ انتخاب رشته دانشگاه آزاد</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Tahoma, Arial, sans-serif; color: #111; margin: 12mm; font-size: 10px; background: #fff; }
        .tools { display: flex; gap: 8px; margin-bottom: 12px; }
        .tools a, .tools button { font: inherit; font-size: 12px; padding: 6px 14px; border: 1px solid #555; border-radius: 6px; background: #fff; color: #111; text-decoration: none; cursor: pointer; }
        .tools button { background: #1f4fa3; border-color: #1f4fa3; color: #fff; }
        .head { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 2px solid #222; padding-bottom: 6px; margin-bottom: 10px; gap: 12px; }
        .title { font-size: 18px; font-weight: 700; }
        .subtitle { font-size: 12px; font-weight: 700; }
        .info { display: grid; grid-template-columns: repeat(4, 1fr); border: 1px solid #333; border-left: 0; border-bottom: 0; margin-bottom: 12px; }
        .info > div { display: flex; gap: 6px; padding: 5px 7px; border-left: 1px solid #333; border-bottom: 1px solid #333; }
        .info .label { color: #555; white-space: nowrap; }
        .info .value { font-weight: 700; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #555; padding: 4px 5px; text-align: right; vertical-align: top; overflow-wrap: anywhere; }
        th { background: #eee; font-weight: 700; }
        tr { page-break-inside: avoid; break-inside: avoid; }
        thead { display: table-header-group; }
        .num { text-align: center; }
        @page { size: A4; margin: 12mm; }
        @media print { body { margin: 0; } .tools { display: none; } th { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
        @media screen and (max-width: 700px) { body { margin: 12px; } .info { grid-template-columns: 1fr 1fr; } table { min-width: 720px; } }
    </style>
</head>
<body>
@unless($autoPrint)
    <div class="tools">
        @isset($backUrl)<a href="{{ $backUrl }}">بازگشت</a>@endisset
        <button type="button" onclick="window.print()">چاپ</button>
    </div>
@endunless

<div class="head">
    <div class="title">{{ app(\App\Services\SettingsService::class)->get('institute_name', config('app.name')) }}</div>
    <div class="subtitle">لیست انتخاب رشته | دانشگاه آزاد اسلامی</div>
</div>

<div class="info">
    @foreach($studentInfo as $label => $value)
        <div><span class="label">{{ $label }}:</span><span class="value">{{ $value }}</span></div>
    @endforeach
</div>

<div class="table-wrap">
<table>
    <colgroup>
        <col style="width: 5%">
        <col style="width: 7%">
        <col style="width: 16%">
        <col style="width: 7%">
        <col style="width: {{ $hasNotes ? 20 : 26 }}%">
        <col style="width: 14%">
        <col style="width: 12%">
        <col style="width: 7%">
        <col style="width: 6%">
        @if($hasNotes)<col style="width: 6%">@endif
    </colgroup>
    <thead>
        <tr>
            <th class="num">ردیف</th>
            <th class="num">کد محل</th>
            <th>محل دانشگاهی</th>
            <th class="num">کد رشته</th>
            <th>رشته تحصیلی</th>
            <th>دفترچه</th>
            <th>استان / شهر</th>
            <th>جنس پذیرش</th>
            <th class="num">ظرفیت</th>
            @if($hasNotes)<th>توضیح</th>@endif
        </tr>
    </thead>
    <tbody>
        @foreach($plan->items as $item)
            <tr>
                <td class="num">{{ \App\Support\PersianDate::number($item->priority_order) }}</td>
                <td class="num">{{ $item->unit_code }}</td>
                <td>{{ $item->unit_name }}</td>
                <td class="num">{{ $item->field_code }}</td>
                <td>{{ $item->field_name }}@if($item->part_time) (پاره وقت)@endif</td>
                <td>{{ $item->bookletLabel() }}</td>
                <td>{{ $item->province }} / {{ $item->city }}</td>
                <td>{{ $item->gender }}</td>
                <td class="num">@if($item->admission === 'exam'){{ $item->capacity_first ?? '-' }} / {{ $item->capacity_second ?? '-' }}@else - @endif</td>
                @if($hasNotes)<td>{{ $item->note ?: '-' }}</td>@endif
            </tr>
        @endforeach
    </tbody>
</table>
</div>
@if($autoPrint)
    <script>window.addEventListener('load', function(){ window.print(); });</script>
@endif
</body>
</html>
