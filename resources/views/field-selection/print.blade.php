@php
    $student = $plan->student;
    $reservation = $plan->reservation;
    $examLabel = $selectedExamTypeLabel ?? 'انتخاب رشته ثبت‌شده';
    $studentInfo = [
        'نام دانش‌آموز' => $student?->full_name ?: '-',
        'رشته' => $student?->major ?: '-',
        'منطقه' => $student?->region ?: '-',
        'نوع کنکور' => $student?->examTypeLabel() ?: '-',
        'تراز / رتبه' => $student?->score ?: '-',
        'زمان رزرو' => $reservation?->slot?->date
            ? \App\Support\PersianDate::date($reservation->slot->date).' - '.\App\Support\PersianDate::time($reservation->assignedStartTime())
            : '-',
        'تاریخ انتشار' => \App\Support\PersianDate::dateTime($plan->published_at ?: $plan->created_at),
        'تعداد رشته‌ها' => \App\Support\PersianDate::number($plan->items->count()),
    ];
@endphp
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>چاپ انتخاب رشته</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Tahoma, Arial, sans-serif; color: #111; margin: 12mm; font-size: 10px; }
        .head { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 2px solid #222; padding-bottom: 6px; margin-bottom: 10px; }
        .title { font-size: 18px; font-weight: 700; }
        .subtitle { font-size: 12px; font-weight: 700; }
        .info { display: grid; grid-template-columns: repeat(4, 1fr); border: 1px solid #333; border-left: 0; border-bottom: 0; margin-bottom: 12px; }
        .info > div { display: flex; gap: 6px; padding: 5px 7px; border-left: 1px solid #333; border-bottom: 1px solid #333; }
        .info .label { color: #555; white-space: nowrap; }
        .info .value { font-weight: 700; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #555; padding: 4px 5px; text-align: right; vertical-align: top; overflow-wrap: anywhere; }
        th { background: #eee; font-weight: 700; }
        tr { page-break-inside: avoid; break-inside: avoid; }
        thead { display: table-header-group; }
        .num { text-align: center; }
        @page { size: A4; margin: 12mm; }
        @media print { body { margin: 0; } th { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
    </style>
</head>
<body>
<div class="head">
    <div class="title">{{ app(\App\Services\SettingsService::class)->get('institute_name', config('app.name')) }}</div>
    <div class="subtitle">لیست انتخاب رشته | {{ $examLabel }}</div>
</div>

<div class="info">
    @foreach($studentInfo as $label => $value)
        <div><span class="label">{{ $label }}:</span><span class="value">{{ $value }}</span></div>
    @endforeach
</div>

<table>
    <colgroup>
        <col style="width: 5%">
        <col style="width: 8%">
        <col style="width: 15%">
        <col style="width: 30%">
        <col style="width: 22%">
        <col style="width: 9%">
        <col style="width: 11%">
    </colgroup>
    <thead>
        <tr>
            <th class="num">ردیف</th>
            <th class="num">کد رشته</th>
            <th>نام رشته</th>
            <th>توضیحات رشته</th>
            <th>دانشگاه</th>
            <th>شهر</th>
            <th>نوع دانشگاه</th>
        </tr>
    </thead>
    <tbody>
        @foreach($plan->items as $item)
            <tr>
                <td class="num">{{ \App\Support\PersianDate::number($item->priority_order) }}</td>
                <td class="num">{{ $item->field_code }}</td>
                <td>{{ $item->field_name }}</td>
                <td>{{ $item->field_description ?: $item->university_description ?: '-' }}</td>
                <td>{{ $item->university_name ?: '-' }}</td>
                <td>{{ $item->city ?: '-' }}</td>
                <td>{{ $item->university_type ?: '-' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
<script>window.addEventListener('load', function(){ window.print(); });</script>
</body>
</html>
