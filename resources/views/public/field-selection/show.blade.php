@extends('layouts.public')

@section('title', 'لیست انتخاب رشته')

@push('styles')
    <style>
        /* ==========================================================
           FIELD SELECTION
           Modern / Mobile First / Printable
        ========================================================== */

        :root {
            --fs-primary: #8CC63F;
            --fs-secondary: #6BBF3A;
            --fs-deep: #2E4E24;

            --fs-bg: #FAF8F2;
            --fs-surface: #FFFFFF;
            --fs-soft: #E8F5D8;

            --fs-text: #2B2B2B;
            --fs-muted: #8A8A8A;
            --fs-border: #E6E6E6;

            --fs-info: #397E77;
            --fs-info-bg: #EAF8F5;

            --fs-radius-sm: 12px;
            --fs-radius-md: 16px;
            --fs-radius-lg: 22px;
            --fs-radius-xl: 28px;

            --fs-shadow:
                0 8px 20px rgba(46, 78, 36, .045),
                0 20px 50px rgba(46, 78, 36, .055);

            --fs-shadow-sm:
                0 4px 18px rgba(46, 78, 36, .045);
        }

        body {
            background: var(--fs-bg);
            color: var(--fs-text);
        }

        .field-selection-page {
            width: 100%;
            max-width: 1280px;

            margin-inline: auto;
            padding: 16px 10px 50px;

            direction: rtl;
        }

        .field-selection-page *,
        .field-selection-page *::before,
        .field-selection-page *::after {
            box-sizing: border-box;
        }


        /* ==========================================================
           Hero
        ========================================================== */

        .field-hero {
            position: relative;
            overflow: hidden;

            margin-bottom: 14px;
            padding: 18px;

            border: 1px solid rgba(46, 78, 36, .08);
            border-radius: var(--fs-radius-xl);

            background:
                radial-gradient(circle at 0 0,
                    rgba(140, 198, 63, .18),
                    transparent 38%),
                var(--fs-surface);

            box-shadow: var(--fs-shadow);
        }

        .field-hero::after {
            content: "";

            position: absolute;

            width: 180px;
            height: 180px;

            left: -70px;
            top: -95px;

            border-radius: 50%;

            background: rgba(232, 245, 216, .55);

            pointer-events: none;
        }

        .field-hero__top {
            position: relative;
            z-index: 2;

            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .field-hero__identity {
            display: flex;
            align-items: center;
            gap: 12px;

            min-width: 0;
        }

        .field-hero__icon {
            width: 50px;
            height: 50px;
            flex: 0 0 50px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 16px;

            background: linear-gradient(135deg,
                    var(--fs-primary),
                    var(--fs-secondary));

            color: #fff;

            font-size: 23px;

            box-shadow:
                0 8px 22px rgba(107, 191, 58, .20);
        }

        .field-hero__content {
            min-width: 0;
        }

        .field-hero__title {
            margin: 0 0 4px;

            color: var(--fs-deep);

            font-size: 18px;
            line-height: 1.7;
            font-weight: 700;
        }

        .field-hero__subtitle {
            color: var(--fs-muted);

            font-size: 12px;
            line-height: 1.8;
        }

        .field-hero__actions {
            position: relative;
            z-index: 2;

            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
        }

        .field-action {
            min-height: 46px;

            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;

            padding: 10px 14px;

            border-radius: 14px;
            border: 0;

            font-family: inherit;
            font-size: 12px;
            font-weight: 600;

            text-decoration: none !important;

            cursor: pointer;

            transition:
                transform .18s ease,
                border-color .18s ease,
                background-color .18s ease;
        }

        .field-action:active {
            transform: scale(.98);
        }

        .field-action--primary {
            color: #fff !important;

            background: linear-gradient(135deg,
                    var(--fs-primary),
                    var(--fs-secondary));

            box-shadow:
                0 8px 22px rgba(107, 191, 58, .18);
        }

        .field-action--soft {
            color: var(--fs-deep) !important;

            background: #fff;

            border: 1px solid var(--fs-border);
        }


        /* ==========================================================
           Plan switcher — other published plans for this student
        ========================================================== */

        .plan-switch {
            position: relative;
            z-index: 2;

            margin-top: 16px;
            padding: 13px;

            border: 1px solid rgba(140, 198, 63, .16);
            border-radius: var(--fs-radius-md);

            background: rgba(255, 255, 255, .72);
        }

        .plan-switch__head {
            display: flex;
            align-items: center;
            gap: 7px;

            margin-bottom: 10px;

            color: var(--fs-deep);

            font-size: 12.5px;
            font-weight: 700;
        }

        .plan-switch__head i {
            color: var(--fs-primary);
            font-size: 16px;
        }

        .plan-switch__count {
            min-width: 20px;
            padding: 1px 7px;

            border-radius: 999px;

            background: var(--fs-soft);
            color: var(--fs-deep);

            font-size: 10.5px;
            font-weight: 700;
            text-align: center;
        }

        .plan-switch__list {
            display: grid;
            gap: 8px;
        }

        .plan-switch__item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;

            padding: 10px 11px;

            border: 1px solid var(--fs-border);
            border-radius: 14px;

            background: #fff;

            transition:
                border-color .18s ease,
                box-shadow .18s ease;
        }

        .plan-switch__item:hover {
            border-color: var(--fs-primary);
            box-shadow: var(--fs-shadow-sm);
        }

        .plan-switch__item.is-current {
            border-color: var(--fs-primary);
            background: var(--fs-soft);
        }

        .plan-switch__info {
            flex: 1 1 auto;
            min-width: 0;
        }

        .plan-switch__name {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px;

            color: var(--fs-text);

            font-size: 12.5px;
            font-weight: 650;
            line-height: 1.8;

            overflow-wrap: anywhere;
        }

        .plan-switch__badge {
            padding: 1px 8px;

            border-radius: 999px;

            background: var(--fs-primary);
            color: #fff;

            font-size: 10px;
            font-weight: 700;
        }

        .plan-switch__date {
            display: inline-flex;
            align-items: center;
            gap: 4px;

            margin-top: 3px;

            color: var(--fs-muted);

            font-size: 10.5px;
        }

        .plan-switch__actions {
            flex: 0 0 auto;

            display: flex;
            gap: 6px;
        }

        /* the shared .field-action is sized for the page header — slim it here */
        .plan-switch__btn {
            min-height: 36px;

            padding: 8px 13px;

            border-radius: 11px;

            font-size: 11.5px;

            white-space: nowrap;
        }


        /* ==========================================================
           Info
        ========================================================== */

        .field-info-grid {
            position: relative;
            z-index: 2;

            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;

            margin-top: 16px;
        }

        .field-info-item {
            display: flex;
            align-items: center;
            gap: 10px;

            min-width: 0;

            padding: 11px;

            border: 1px solid rgba(140, 198, 63, .13);
            border-radius: 15px;

            background: rgba(255, 255, 255, .72);
        }

        .field-info-item__icon {
            width: 36px;
            height: 36px;
            flex: 0 0 36px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 11px;

            background: var(--fs-soft);
            color: var(--fs-deep);

            font-size: 17px;
        }

        .field-info-item__content {
            min-width: 0;
            flex: 1;
        }

        .field-info-item__label {
            display: block;

            margin-bottom: 2px;

            color: var(--fs-muted);

            font-size: 10px;
            line-height: 1.5;
            font-weight: 500;
        }

        .field-info-item__value {
            display: block;

            color: var(--fs-text);

            font-size: 12px;
            line-height: 1.7;
            font-weight: 600;

            overflow-wrap: anywhere;
        }


        /* ==========================================================
           Empty state
        ========================================================== */

        .field-empty {
            display: flex;
            flex-direction: column;
            align-items: center;

            padding: 35px 20px;

            border: 1px solid rgba(57, 126, 119, .12);
            border-radius: var(--fs-radius-lg);

            background: var(--fs-info-bg);

            text-align: center;
        }

        .field-empty__icon {
            width: 58px;
            height: 58px;

            display: flex;
            align-items: center;
            justify-content: center;

            margin-bottom: 12px;

            border-radius: 18px;

            background: rgba(57, 126, 119, .10);
            color: var(--fs-info);

            font-size: 28px;
        }

        .field-empty__title {
            margin-bottom: 5px;

            color: var(--fs-info);

            font-size: 14px;
            font-weight: 650;
        }

        .field-empty__text {
            max-width: 420px;

            color: #5F7774;

            font-size: 11.5px;
            line-height: 1.9;
        }


        /* ==========================================================
           Selection section
        ========================================================== */

        .field-list-card {
            overflow: hidden;

            border: 1px solid rgba(46, 78, 36, .07);
            border-radius: var(--fs-radius-xl);

            background: var(--fs-surface);

            box-shadow: var(--fs-shadow);
        }

        .field-list-card__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;

            padding: 15px 16px;

            border-bottom: 1px solid #EFEFEF;
        }

        .field-list-card__heading {
            display: flex;
            align-items: center;
            gap: 10px;

            min-width: 0;
        }

        .field-list-card__icon {
            width: 40px;
            height: 40px;
            flex: 0 0 40px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 13px;

            background: var(--fs-soft);
            color: var(--fs-deep);

            font-size: 19px;
        }

        .field-list-card__title {
            color: var(--fs-text);

            font-size: 14px;
            font-weight: 650;
        }

        .field-list-card__description {
            margin-top: 2px;

            color: var(--fs-muted);

            font-size: 10px;
            line-height: 1.6;
        }

        .field-count {
            flex-shrink: 0;

            display: inline-flex;
            align-items: center;
            gap: 5px;

            padding: 6px 9px;

            border-radius: 999px;

            background: var(--fs-soft);
            color: var(--fs-deep);

            font-size: 10.5px;
            font-weight: 600;
        }

        .field-list-card__body {
            padding: 12px;
        }


        /* ==========================================================
           Desktop tables
        ========================================================== */

        .field-columns {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));

            gap: 12px;
        }

        .field-column {
            min-width: 0;

            overflow: hidden;

            border: 1px solid var(--fs-border);
            border-radius: 15px;

            background: #fff;
        }

        .field-column__title {
            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 9px 11px;

            border-bottom: 1px solid #EFEFEF;

            background: #FAFBF8;

            color: var(--fs-deep);

            font-size: 11px;
            font-weight: 600;
        }

        .field-table-wrap {
            width: 100%;

            overflow-x: auto;
        }

        .field-table {
            width: 100%;

            border-collapse: collapse;
            table-layout: fixed;

            font-size: 10px;
        }

        .field-table th,
        .field-table td {
            padding: 8px 6px;

            border-bottom: 1px solid #F0F0F0;

            vertical-align: top;

            line-height: 1.7;
        }

        .field-table th {
            position: sticky;
            top: 0;
            z-index: 2;

            background: #F7F9F4;

            color: #667262;

            font-size: 9.5px;
            font-weight: 600;

            white-space: nowrap;
        }

        .field-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .field-table tbody tr {
            transition: background-color .15s ease;
        }

        .field-table tbody tr:hover {
            background: #FBFDF8;
        }

        .field-table__priority {
            text-align: center;

            color: var(--fs-deep);

            font-weight: 700;
        }

        .field-table__code {
            direction: ltr;

            color: #4F5B4B;

            font-weight: 600;
            text-align: center;
        }

        .field-table__name {
            color: var(--fs-text);

            font-weight: 650;
        }

        .field-table__description {
            color: #737373;

            font-size: 9.5px;
        }

        .field-table__university {
            color: #4E4E4E;

            font-weight: 500;
        }

        .field-table th:nth-child(1),
        .field-table td:nth-child(1) {
            width: 7%;
        }

        .field-table th:nth-child(2),
        .field-table td:nth-child(2) {
            width: 13%;
        }

        .field-table th:nth-child(3),
        .field-table td:nth-child(3) {
            width: 18%;
        }

        .field-table th:nth-child(4),
        .field-table td:nth-child(4) {
            width: 22%;
        }

        .field-table th:nth-child(5),
        .field-table td:nth-child(5) {
            width: 17%;
        }

        .field-table th:nth-child(6),
        .field-table td:nth-child(6) {
            width: 10%;
        }

        .field-table th:nth-child(7),
        .field-table td:nth-child(7) {
            width: 13%;
        }


        /* ==========================================================
           Mobile cards
        ========================================================== */

        .field-mobile-list {
            display: none;
        }

        .field-mobile-item {
            overflow: hidden;

            border: 1px solid var(--fs-border);
            border-radius: 16px;

            background: #fff;

            box-shadow: var(--fs-shadow-sm);
        }

        .field-mobile-item+.field-mobile-item {
            margin-top: 9px;
        }

        .field-mobile-item__header {
            display: flex;
            align-items: flex-start;
            gap: 10px;

            padding: 12px;

            background:
                linear-gradient(135deg,
                    rgba(232, 245, 216, .58),
                    rgba(255, 255, 255, .75));

            border-bottom: 1px solid #EEF1EA;
        }

        .field-mobile-item__priority {
            width: 34px;
            height: 34px;
            flex: 0 0 34px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 11px;

            background: var(--fs-deep);
            color: #fff;

            font-size: 11px;
            font-weight: 700;
        }

        .field-mobile-item__heading {
            flex: 1;
            min-width: 0;
        }

        .field-mobile-item__name {
            margin-bottom: 3px;

            color: var(--fs-text);

            font-size: 14.5px;
            line-height: 1.7;
            font-weight: 650;
        }

        .field-mobile-item__code {
            color: var(--fs-muted);

            font-size: 14px;
            font-weight: 500;
        }

        .field-mobile-item__body {
            padding: 4px 12px;
        }

        .field-mobile-row {
            display: flex;
            align-items: flex-start;
            gap: 10px;

            padding: 9px 0;

            border-bottom: 1px solid #F1F1F1;
        }

        .field-mobile-row:last-child {
            border-bottom: 0;
        }

        .field-mobile-row__label {
            width: 75px;
            flex: 0 0 75px;

            color: var(--fs-muted);

            font-size: 10px;
            line-height: 1.7;
            font-weight: 500;
        }

        .field-mobile-row__value {
            flex: 1;
            min-width: 0;

            color: #484848;

            font-size: 15px;
            line-height: 1.9;
            font-weight: 500;

            overflow-wrap: anywhere;
        }


        /* ==========================================================
           Tablet
        ========================================================== */

        @media (min-width: 576px) {

            .field-selection-page {
                padding-inline: 16px;
            }

            .field-hero {
                padding: 22px;
            }

            .field-hero__top {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
            }

            .field-hero__actions {
                display: flex;
                align-items: center;
            }

            .field-info-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }


        @media (min-width: 992px) {

            .field-info-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }


        /* ==========================================================
           Mobile
        ========================================================== */

        @media (max-width: 767.98px) {

            /* stack each row so the buttons never squeeze the exam name */
            .plan-switch__item {
                flex-direction: column;
                align-items: stretch;
                gap: 9px;
            }

            .plan-switch__actions {
                width: 100%;
            }

            .plan-switch__btn {
                flex: 1 1 0;
                min-width: 0;
            }

            .field-columns {
                display: none;
            }

            .field-mobile-list {
                display: block;
            }

            .field-list-card__header {
                align-items: flex-start;
            }

            .field-list-card__body {
                padding: 10px;
            }

            .field-count {
                font-size: 9.5px;
            }
        }


        /* ==========================================================
           PRINT
        ========================================================== */

        @media print {

            @page {
                size: A3 landscape;
                margin: 5mm;
            }

            html,
            body {
                width: 100%;
                height: auto;

                margin: 0 !important;
                padding: 0 !important;

                background: #fff !important;
            }

            body {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            body * {
                visibility: hidden;
            }

            .field-selection-page,
            .field-selection-page * {
                visibility: visible;
            }

            .field-selection-page {
                position: absolute;
                inset: 0;

                width: 100%;
                max-width: none;

                margin: 0 !important;
                padding: 0 !important;

                background: #fff !important;
            }

            .no-print,
            .field-hero__actions,
            .field-mobile-list {
                display: none !important;
            }

            .field-hero {
                overflow: visible;

                margin: 0 0 2.5mm !important;
                padding: 2.5mm 3mm !important;

                border: .2mm solid #D9E2D3 !important;
                border-radius: 2mm !important;

                background: #fff !important;

                box-shadow: none !important;
            }

            .field-hero::after {
                display: none !important;
            }

            .field-hero__top {
                display: block !important;
            }

            .field-hero__identity {
                gap: 2mm;
            }

            .field-hero__icon {
                width: 8mm;
                height: 8mm;
                flex: 0 0 8mm;

                border-radius: 2mm;

                font-size: 4mm;

                box-shadow: none;
            }

            .field-hero__title {
                margin: 0;

                font-size: 3.2mm;
                line-height: 1.3;
            }

            .field-hero__subtitle {
                display: none;
            }

            .field-info-grid {
                display: grid !important;
                grid-template-columns: repeat(4, 1fr) !important;

                gap: 1.5mm !important;

                margin-top: 2mm !important;
            }

            .field-info-item {
                gap: 1.2mm;

                min-height: 0;

                padding: 1.3mm 1.5mm !important;

                border-radius: 1.5mm !important;

                background: #fff !important;
            }

            .field-info-item__icon {
                display: none;
            }

            .field-info-item__label {
                margin-bottom: .4mm;

                font-size: 1.7mm;
                line-height: 1.2;
            }

            .field-info-item__value {
                font-size: 2.1mm;
                line-height: 1.2;
            }

            .field-list-card {
                overflow: visible !important;

                border: 0 !important;
                border-radius: 0 !important;

                box-shadow: none !important;
            }

            .field-list-card__header {
                display: none !important;
            }

            .field-list-card__body {
                padding: 0 !important;
            }

            .field-columns {
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;

                gap: 2mm !important;
            }

            .field-column {
                overflow: visible !important;

                border: .2mm solid #BFC8BC !important;
                border-radius: 0 !important;

                break-inside: avoid;
                page-break-inside: avoid;
            }

            .field-column__title {
                padding: 1mm !important;

                font-size: 1.8mm !important;
                line-height: 1.1 !important;

                background: #EDF5E5 !important;
            }

            .field-table-wrap {
                overflow: visible !important;
            }

            .field-table {
                width: 100% !important;

                table-layout: fixed !important;

                border-collapse: collapse !important;

                font-size: 1.65mm !important;
                line-height: 1.05 !important;
            }

            .field-table thead {
                display: table-header-group;
            }

            .field-table tr {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .field-table th,
            .field-table td {
                height: auto !important;

                padding: .65mm .6mm !important;

                border-bottom: .15mm solid #D9D9D9 !important;

                font-size: 1.65mm !important;
                line-height: 1.05 !important;

                overflow: hidden !important;

                vertical-align: middle !important;
            }

            .field-table th {
                position: static !important;

                background: #EEF4E9 !important;

                font-size: 1.55mm !important;
                font-weight: 700 !important;

                white-space: nowrap !important;
            }

            .field-table td {
                white-space: nowrap !important;

                text-overflow: ellipsis !important;
            }

            .field-table__description {
                max-width: 0;

                font-size: 1.55mm !important;

                white-space: nowrap !important;

                overflow: hidden !important;
                text-overflow: ellipsis !important;
            }

            .field-table th:nth-child(1),
            .field-table td:nth-child(1) {
                width: 6% !important;
            }

            .field-table th:nth-child(2),
            .field-table td:nth-child(2) {
                width: 12% !important;
            }

            .field-table th:nth-child(3),
            .field-table td:nth-child(3) {
                width: 19% !important;
            }

            .field-table th:nth-child(4),
            .field-table td:nth-child(4) {
                width: 24% !important;
            }

            .field-table th:nth-child(5),
            .field-table td:nth-child(5) {
                width: 17% !important;
            }

            .field-table th:nth-child(6),
            .field-table td:nth-child(6) {
                width: 9% !important;
            }

            .field-table th:nth-child(7),
            .field-table td:nth-child(7) {
                width: 13% !important;
            }

            a {
                text-decoration: none !important;
            }
        }
    </style>
@endpush


@section('content')

    <div class="field-selection-page">

        @if(!$plan)

            <section class="field-hero">

                <div class="field-hero__top">

                    <div class="field-hero__identity">

                        <div class="field-hero__icon">
                            <i class="ri-list-ordered"></i>
                        </div>

                        <div class="field-hero__content">

                            <h1 class="field-hero__title">
                                لیست انتخاب رشته
                            </h1>

                            <div class="field-hero__subtitle">
                                نتیجه انتخاب رشته شما پس از انتشار در این بخش نمایش داده می‌شود.
                            </div>

                        </div>

                    </div>

                </div>

            </section>


            <div class="field-empty">

                <div class="field-empty__icon">
                    <i class="ri-time-line"></i>
                </div>

                <div class="field-empty__title">
                    انتخاب رشته هنوز آماده نشده است
                </div>

                <div class="field-empty__text">
                    <strong>انتخاب رشته شما هنوز توسط آموزشگاه منتشر نشده است.</strong><br>
                    انتخاب رشته شما هنوز توسط آموزشگاه ثبت و منتشر نشده است.
                    پس از آماده شدن، اطلاعات کامل انتخاب‌های شما در همین صفحه نمایش داده خواهد شد.
                </div>

            </div>

        @else

            {{-- =====================================================
            HERO
            ====================================================== --}}

            <section class="field-hero">

                <div class="field-hero__top">

                    <div class="field-hero__identity">

                        <div class="field-hero__icon">
                            <i class="ri-list-check-3"></i>
                        </div>

                        <div class="field-hero__content">

                            <h1 class="field-hero__title">
                                لیست انتخاب رشته
                            </h1>

                            <div class="field-hero__subtitle">
                                اولویت‌ها و رشته‌محل‌های ثبت‌شده توسط آموزشگاه
                            </div>

                        </div>

                    </div>


                    <div class="field-hero__actions no-print">

                        <a class="field-action field-action--primary" target="_blank" href="{{ route('public.reservations.field-selection.plan.print', [$reservation->public_token, $plan]) }}">
                            <i class="ri-printer-line"></i>

                            چاپ انتخاب رشته
                        </a>

                        <button type="button" class="field-action field-action--soft" onclick="history.back()">
                            <i class="ri-arrow-right-line"></i>

                            بازگشت
                        </button>

                    </div>

                </div>


                <div class="field-info-grid">

                    <div class="field-info-item">

                        <div class="field-info-item__icon">
                            <i class="ri-user-line"></i>
                        </div>

                        <div class="field-info-item__content">

                            <span class="field-info-item__label">
                                دانش‌آموز
                            </span>

                            <span class="field-info-item__value">
                                {{ $reservation->student?->full_name ?: '-' }}
                            </span>

                        </div>

                    </div>


                    <div class="field-info-item">

                        <div class="field-info-item__icon">
                            <i class="ri-graduation-cap-line"></i>
                        </div>

                        <div class="field-info-item__content">

                            <span class="field-info-item__label">
                                رشته
                            </span>

                            <span class="field-info-item__value">
                                {{ $reservation->student?->major ?: '-' }}
                            </span>

                        </div>

                    </div>


                    <div class="field-info-item">

                        <div class="field-info-item__icon">
                            <i class="ri-map-pin-line"></i>
                        </div>

                        <div class="field-info-item__content">

                            <span class="field-info-item__label">
                                منطقه
                            </span>

                            <span class="field-info-item__value">
                                {{ $reservation->student?->region ?: '-' }}
                            </span>

                        </div>

                    </div>


                    <div class="field-info-item">

                        <div class="field-info-item__icon">
                            <i class="ri-file-copy-2-line"></i>
                        </div>

                        <div class="field-info-item__content">

                            <span class="field-info-item__label">
                                کنکور
                            </span>

                            <span class="field-info-item__value">
                                {{ ($examTypeLabels ?? [])[$plan->exam_type_key] ?? 'انتخاب رشته ثبت‌شده' }}
                            </span>

                        </div>

                    </div>


                    <div class="field-info-item">

                        <div class="field-info-item__icon">
                            <i class="ri-calendar-check-line"></i>
                        </div>

                        <div class="field-info-item__content">

                            <span class="field-info-item__label">
                                تاریخ انتشار
                            </span>

                            <span class="field-info-item__value">
                                {{ \App\Support\PersianDate::dateTime($plan->published_at) }}
                            </span>

                        </div>

                    </div>

                </div>

                @if(($visiblePlans ?? collect())->count() > 1)
                    <div class="plan-switch no-print">

                        <div class="plan-switch__head">
                            <i class="ri-stack-line"></i>
                            <span>انتخاب رشته‌های قابل مشاهده</span>
                            <span class="plan-switch__count">{{ \App\Support\PersianDate::number($visiblePlans->count()) }}</span>
                        </div>

                        <div class="plan-switch__list">
                            @foreach($visiblePlans as $visiblePlan)
                                @php($isCurrent = (int) $visiblePlan->id === (int) $plan->id)

                                <div @class(['plan-switch__item', 'is-current' => $isCurrent])>

                                    <div class="plan-switch__info">
                                        <div class="plan-switch__name">
                                            {{ ($examTypeLabels ?? [])[$visiblePlan->exam_type_key] ?? 'انتخاب رشته ثبت‌شده' }}
                                            @if($isCurrent)
                                                <span class="plan-switch__badge">در حال مشاهده</span>
                                            @endif
                                        </div>
                                        <div class="plan-switch__date">
                                            <i class="ri-calendar-check-line"></i>
                                            {{ \App\Support\PersianDate::dateTime($visiblePlan->published_at) }}
                                        </div>
                                    </div>

                                    <div class="plan-switch__actions">
                                        @unless($isCurrent)
                                            <a href="{{ route('public.reservations.field-selection.plan.show', [$reservation->public_token, $visiblePlan]) }}"
                                               class="field-action field-action--primary plan-switch__btn">
                                                <i class="ri-eye-line"></i> مشاهده
                                            </a>
                                        @endunless
                                        <a target="_blank"
                                           href="{{ route('public.reservations.field-selection.plan.print', [$reservation->public_token, $visiblePlan]) }}"
                                           class="field-action field-action--soft plan-switch__btn">
                                            <i class="ri-printer-line"></i> چاپ
                                        </a>
                                    </div>

                                </div>
                            @endforeach
                        </div>

                    </div>
                @endif

            </section>



            {{-- =====================================================
            LIST
            ====================================================== --}}

            <section class="field-list-card">

                <div class="field-list-card__header">

                    <div class="field-list-card__heading">

                        <div class="field-list-card__icon">
                            <i class="ri-list-ordered"></i>
                        </div>

                        <div>

                            <div class="field-list-card__title">
                                اولویت‌های انتخاب رشته
                            </div>

                            <div class="field-list-card__description">
                                لیست کامل رشته‌محل‌های انتخاب‌شده به ترتیب اولویت
                            </div>

                        </div>

                    </div>


                    <div class="field-count">

                        <i class="ri-stack-line"></i>

                        {{ \App\Support\PersianDate::number($plan->items->count()) }}

                        انتخاب

                    </div>

                </div>


                <div class="field-list-card__body">

                    {{-- =================================================
                    DESKTOP TABLES
                    ================================================== --}}

                    <div class="field-columns">

                        @foreach([
                                $plan->items->take(75),
                                $plan->items->slice(75)
                            ] as $columnIndex => $items)

                            @if($items->count())

                                <div class="field-column">

                                    <div class="field-column__title">

                                        <span>
                                            بخش
                                            {{ \App\Support\PersianDate::number($columnIndex + 1) }}
                                        </span>

                                        <span>
                                            {{ \App\Support\PersianDate::number($items->count()) }}
                                            مورد
                                        </span>

                                    </div>


                                    <div class="field-table-wrap">

                                        <table class="field-table">

                                            <thead>

                                                <tr>

                                                    <th>
                                                        ردیف
                                                    </th>

                                                    <th>
                                                        کد رشته
                                                    </th>

                                                    <th>
                                                        نام رشته
                                                    </th>

                                                    <th>
                                                        توضیحات
                                                    </th>

                                                    <th>
                                                        دانشگاه
                                                    </th>

                                                    <th>
                                                        شهر
                                                    </th>

                                                    <th>
                                                        نوع دانشگاه
                                                    </th>

                                                </tr>

                                            </thead>


                                            <tbody>

                                                @foreach($items as $item)

                                                                <tr>

                                                                    <td class="field-table__priority">

                                                                        {{
                                                    \App\Support\PersianDate::number(
                                                        $item->priority_order
                                                    )
                                                                                        }}

                                                                    </td>


                                                                    <td class="field-table__code">

                                                                        {{ $item->field_code ?: '-' }}

                                                                    </td>


                                                                    <td class="field-table__name">

                                                                        {{ $item->field_name ?: '-' }}

                                                                    </td>


                                                                    <td class="field-table__description" title="{{
                                                    $item->field_description
                                                    ?: $item->university_description
                                                    ?: '-'
                                                                                        }}">

                                                                        {{
                                                    $item->field_description
                                                    ?: $item->university_description
                                                    ?: '-'
                                                                                        }}

                                                                    </td>


                                                                    <td class="field-table__university">

                                                                        {{ $item->university_name ?: '-' }}

                                                                    </td>


                                                                    <td>

                                                                        {{ $item->city ?: '-' }}

                                                                    </td>


                                                                    <td>

                                                                        {{ $item->university_type ?: '-' }}

                                                                    </td>

                                                                </tr>

                                                @endforeach

                                            </tbody>

                                        </table>

                                    </div>

                                </div>

                            @endif

                        @endforeach

                    </div>



                    {{-- =================================================
                    MOBILE
                    ================================================== --}}

                    <div class="field-mobile-list">

                        @foreach($plan->items as $item)

                                <article class="field-mobile-item">

                                    <div class="field-mobile-item__header">

                                        <div class="field-mobile-item__priority">

                                            {{
                            \App\Support\PersianDate::number(
                                $item->priority_order
                            )
                                                    }}

                                        </div>


                                        <div class="field-mobile-item__heading">

                                            <div class="field-mobile-item__name">

                                                {{ $item->field_name ?: '-' }}

                                            </div>

                                            <div class="field-mobile-item__code">

                                                کد رشته:

                                                <span dir="ltr">
                                                    {{ $item->field_code ?: '-' }}
                                                </span>

                                            </div>

                                        </div>

                                    </div>


                                    <div class="field-mobile-item__body">

                                        <div class="field-mobile-row">

                                            <div class="field-mobile-row__label">
                                                دانشگاه
                                            </div>

                                            <div class="field-mobile-row__value">
                                                {{ $item->university_name ?: '-' }}
                                            </div>

                                        </div>


                                        <div class="field-mobile-row">

                                            <div class="field-mobile-row__label">
                                                شهر
                                            </div>

                                            <div class="field-mobile-row__value">
                                                {{ $item->city ?: '-' }}
                                            </div>

                                        </div>


                                        <div class="field-mobile-row">

                                            <div class="field-mobile-row__label">
                                                نوع دانشگاه
                                            </div>

                                            <div class="field-mobile-row__value">
                                                {{ $item->university_type ?: '-' }}
                                            </div>

                                        </div>


                                        <div class="field-mobile-row">

                                            <div class="field-mobile-row__label">
                                                توضیحات
                                            </div>

                                            <div class="field-mobile-row__value">

                                                {{
                            $item->field_description
                            ?: $item->university_description
                            ?: '-'
                                                        }}

                                            </div>

                                        </div>

                                    </div>

                                </article>

                        @endforeach

                    </div>

                </div>

            </section>

        @endif

    </div>

@endsection
