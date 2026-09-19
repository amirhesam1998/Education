@extends('layouts.public')

@section('title', 'جزئیات رزرو مشاوره')

@push('styles')
    <style>
        /* ==========================================================
           BEHROZAN — Student Reservation Center
           Mobile First / RTL / Page Local
        ========================================================== */

        :root {
            --br-primary: #8CC63F;
            --br-secondary: #6BBF3A;
            --br-deep: #2E4E24;

            --br-bg: #FAF8F2;
            --br-surface: #FFFFFF;
            --br-glow: #E8F5D8;

            --br-text: #2B2B2B;
            --br-muted: #8A8A8A;
            --br-border: #E6E6E6;

            --br-success: #4D982C;
            --br-success-bg: #EDF8E6;

            --br-warning: #9B741D;
            --br-warning-bg: #FFF7DE;

            --br-danger: #C84A4A;
            --br-danger-bg: #FFF0F0;

            --br-info: #397E77;
            --br-info-bg: #EAF8F5;

            --br-radius-sm: 12px;
            --br-radius-md: 16px;
            --br-radius-lg: 22px;
            --br-radius-xl: 28px;

            --br-shadow-sm:
                0 2px 4px rgba(46, 78, 36, .02),
                0 8px 24px rgba(46, 78, 36, .045);

            --br-shadow:
                0 8px 20px rgba(46, 78, 36, .045),
                0 20px 50px rgba(46, 78, 36, .06);
        }

        body {
            background: var(--br-bg);
            color: var(--br-text);
        }

        .student-reservation-page {
            direction: rtl;
            width: 100%;
            max-width: 1080px;
            margin-inline: auto;
            padding: 14px 10px 50px;
        }

        .student-reservation-page *,
        .student-reservation-page *::before,
        .student-reservation-page *::after {
            box-sizing: border-box;
        }

        .student-reservation-page .ltr {
            direction: ltr;
            unicode-bidi: embed;
        }


        /* ==========================================================
           Hero
        ========================================================== */

        .reservation-hero {
            position: relative;
            overflow: hidden;

            background:
                radial-gradient(circle at 0 0,
                    rgba(140, 198, 63, .16),
                    transparent 38%),
                var(--br-surface);

            border: 1px solid rgba(46, 78, 36, .08);
            border-radius: var(--br-radius-xl);
            box-shadow: var(--br-shadow);

            padding: 18px;
            margin-bottom: 14px;
        }

        .reservation-hero::after {
            content: "";

            position: absolute;
            width: 180px;
            height: 180px;

            border-radius: 50%;
            background: rgba(232, 245, 216, .55);

            left: -70px;
            top: -90px;

            pointer-events: none;
        }

        .reservation-hero__head {
            position: relative;
            z-index: 2;

            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .reservation-brand {
            display: flex;
            align-items: center;
            gap: 12px;

            min-width: 0;
        }

        .reservation-brand__icon {
            width: 48px;
            height: 48px;
            flex-shrink: 0;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 16px;

            background: linear-gradient(135deg,
                    var(--br-primary),
                    var(--br-secondary));

            color: #fff;
            font-size: 23px;

            box-shadow:
                0 8px 22px rgba(107, 191, 58, .22);
        }

        .reservation-brand__content {
            min-width: 0;
        }

        .reservation-brand__title {
            margin: 0 0 4px;

            color: var(--br-deep);

            font-size: 17px;
            line-height: 1.6;
            font-weight: 700;
        }

        .reservation-brand__subtitle {
            color: var(--br-muted);

            font-size: 12.5px;
            line-height: 1.8;
        }


        /* ==========================================================
           Status badge
        ========================================================== */

        .reservation-status {
            width: fit-content;
            min-height: 38px;

            display: inline-flex;
            align-items: center;
            gap: 7px;

            border-radius: 999px;
            padding: 8px 12px;

            font-size: 12px;
            font-weight: 600;

            white-space: nowrap;
        }

        .reservation-status::before {
            content: "";

            width: 8px;
            height: 8px;
            flex-shrink: 0;

            border-radius: 50%;
        }

        .reservation-status.tone-success {
            color: var(--br-success);
            background: var(--br-success-bg);
        }

        .reservation-status.tone-success::before {
            background: var(--br-success);
        }

        .reservation-status.tone-warning {
            color: var(--br-warning);
            background: var(--br-warning-bg);
        }

        .reservation-status.tone-warning::before {
            background: var(--br-warning);
        }

        .reservation-status.tone-danger {
            color: var(--br-danger);
            background: var(--br-danger-bg);
        }

        .reservation-status.tone-danger::before {
            background: var(--br-danger);
        }

        .reservation-status.tone-info {
            color: var(--br-info);
            background: var(--br-info-bg);
        }

        .reservation-status.tone-info::before {
            background: var(--br-info);
        }

        .reservation-status.tone-neutral {
            color: #727272;
            background: #F2F2F2;
        }

        .reservation-status.tone-neutral::before {
            background: #929292;
        }


        /* ==========================================================
           Current stage
        ========================================================== */

        .current-stage {
            position: relative;
            z-index: 2;

            display: flex;
            align-items: center;
            gap: 11px;

            margin-top: 17px;
            padding: 12px;

            border: 1px solid rgba(140, 198, 63, .16);
            border-radius: 17px;

            background: rgba(232, 245, 216, .58);
        }

        .current-stage__icon {
            width: 40px;
            height: 40px;
            flex-shrink: 0;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 13px;

            background: var(--br-deep);
            color: #fff;

            font-size: 19px;
        }

        .current-stage__text {
            min-width: 0;
        }

        .current-stage__label {
            display: block;

            margin-bottom: 2px;

            color: var(--br-muted);

            font-size: 11.5px;
            font-weight: 500;
        }

        .current-stage__value {
            display: block;

            color: var(--br-deep);

            font-size: 13.5px;
            line-height: 1.7;
            font-weight: 700;
        }


        /* ==========================================================
           Quick actions
        ========================================================== */

        .hero-actions {
            position: relative;
            z-index: 2;

            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;

            margin-top: 14px;
        }

        .hero-action {
            min-height: 46px;

            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;

            border: 0;
            border-radius: 14px;

            padding: 10px 14px;

            text-decoration: none !important;

            cursor: pointer;

            font-size: 12.5px;
            font-weight: 600;

            transition:
                transform .18s ease,
                background-color .18s ease,
                border-color .18s ease;
        }

        .hero-action:active {
            transform: scale(.98);
        }

        .hero-action--primary {
            background: linear-gradient(135deg,
                    var(--br-primary),
                    var(--br-secondary));

            color: #fff !important;

            box-shadow:
                0 8px 20px rgba(107, 191, 58, .18);
        }

        .hero-action--soft {
            background: var(--br-bg);
            color: var(--br-deep) !important;

            border: 1px solid var(--br-border);
        }


        /* ==========================================================
           Progress steps
        ========================================================== */

        .reservation-progress-card {
            padding: 16px 12px;
            margin-bottom: 14px;

            background: var(--br-surface);

            border: 1px solid rgba(46, 78, 36, .07);
            border-radius: var(--br-radius-lg);

            box-shadow: var(--br-shadow-sm);
        }

        .progress-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;

            margin-bottom: 13px;
        }

        .progress-heading__title {
            display: flex;
            align-items: center;
            gap: 7px;

            color: var(--br-text);

            font-size: 14px;
            font-weight: 650;
        }

        .progress-heading__title i {
            color: var(--br-primary);
            font-size: 18px;
        }

        .progress-heading__hint {
            color: var(--br-muted);
            font-size: 10.5px;
        }

        /* ==========================================================
           Published field-selection plans
        ========================================================== */

        .plan-group-list {
            display: flex;
            flex-direction: column;

            gap: 14px;
        }

        .plan-group {
            display: flex;
            flex-direction: column;

            gap: 8px;
        }

        .plan-group__title {
            display: flex;
            align-items: center;
            gap: 6px;

            color: var(--br-deep);

            font-size: 12.5px;
            font-weight: 650;
        }

        .plan-group__title i {
            color: var(--br-primary);
            font-size: 15px;
        }

        .plan-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;

            padding: 12px;

            border: 1px solid var(--br-border);
            border-radius: var(--br-radius-md);

            background: #fff;

            transition:
                border-color .18s ease,
                box-shadow .18s ease;
        }

        .plan-card:hover {
            border-color: var(--br-primary);
            box-shadow: var(--br-shadow-sm);
        }

        .plan-card__info {
            flex: 1 1 auto;
            min-width: 0;
        }

        .plan-card__name {
            color: var(--br-text);

            font-size: 13px;
            font-weight: 600;
            line-height: 1.8;
        }

        .plan-card__meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px 10px;

            margin-top: 5px;
        }

        .plan-card__date,
        .plan-card__state {
            display: inline-flex;
            align-items: center;
            gap: 4px;

            font-size: 11px;
        }

        .plan-card__date {
            color: var(--br-muted);
        }

        .plan-card__state {
            padding: 2px 8px;
            border-radius: 999px;

            background: var(--br-success-bg);
            color: var(--br-success);

            font-weight: 600;
        }

        .plan-card__actions {
            flex: 0 0 auto;

            display: flex;
            gap: 7px;
        }

        .plan-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;

            min-height: 38px;
            padding: 0 14px;

            border: 1px solid transparent;
            border-radius: 11px;

            font-size: 12px;
            font-weight: 600;

            text-decoration: none;
            white-space: nowrap;

            transition:
                filter .18s ease,
                background-color .18s ease,
                border-color .18s ease,
                color .18s ease;
        }

        .plan-btn--primary {
            background: linear-gradient(135deg, var(--br-primary), var(--br-secondary));
            color: #fff;

            box-shadow: 0 5px 14px rgba(76, 175, 80, .22);
        }

        .plan-btn--primary:hover {
            filter: brightness(1.04);
        }

        .plan-btn--ghost {
            border-color: var(--br-border);
            background: #fff;
            color: var(--br-text);
        }

        .plan-btn--ghost:hover {
            border-color: var(--br-primary);
            color: var(--br-deep);
            background: var(--br-glow);
        }

        .reservation-steps {
            display: grid;

            grid-auto-flow: column;
            grid-auto-columns: 108px;

            gap: 8px;

            overflow-x: auto;
            overscroll-behavior-inline: contain;

            padding: 2px 1px 8px;

            scrollbar-width: thin;
            scrollbar-color: var(--br-border) transparent;
        }

        .reservation-step {
            min-height: 90px;

            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;

            padding: 10px 8px;

            text-align: center;

            border: 1px solid var(--br-border);
            border-radius: 16px;

            background: #fff;

            transition:
                border-color .2s ease,
                background-color .2s ease,
                box-shadow .2s ease,
                transform .2s ease;
        }

        .reservation-step__icon {
            width: 34px;
            height: 34px;

            display: flex;
            align-items: center;
            justify-content: center;

            margin-bottom: 7px;

            border-radius: 11px;

            background: #F4F4F4;
            color: var(--br-muted);

            font-size: 16px;
        }

        .reservation-step__label {
            color: var(--br-muted);

            font-size: 10.5px;
            line-height: 1.6;
            font-weight: 500;
        }

        .reservation-step.is-done {
            background: #F7FBEF;
            border-color: rgba(140, 198, 63, .2);
        }

        .reservation-step.is-done .reservation-step__icon {
            color: #fff;
            background: var(--br-secondary);
        }

        .reservation-step.is-done .reservation-step__label {
            color: var(--br-deep);
        }

        .reservation-step.is-current {
            background: var(--br-glow);

            border-color: var(--br-primary);

            box-shadow:
                0 7px 16px rgba(140, 198, 63, .12);

            transform: translateY(-1px);
        }

        .reservation-step.is-current .reservation-step__icon {
            color: #fff;
            background: var(--br-deep);
        }

        .reservation-step.is-current .reservation-step__label {
            color: var(--br-deep);
            font-weight: 700;
        }

        .reservation-step.is-failed {
            background: var(--br-danger-bg);

            border-color: rgba(200, 74, 74, .3);
        }

        .reservation-step.is-failed .reservation-step__icon {
            background: var(--br-danger);
            color: #fff;
        }

        .reservation-step.is-failed .reservation-step__label {
            color: var(--br-danger);
            font-weight: 650;
        }


        /* ==========================================================
           Notices
        ========================================================== */

        .reservation-notices {
            display: flex;
            flex-direction: column;
            gap: 9px;

            margin-bottom: 14px;
        }

        .reservation-notice {
            display: flex;
            align-items: center;
            gap: 10px;

            padding: 13px;

            border: 1px solid transparent;
            border-radius: 16px;

            font-size: 12px;
            line-height: 1.9;
        }

        .reservation-notice>i {
            flex-shrink: 0;

            margin-top: 1px;

            font-size: 19px;
        }

        .reservation-notice strong {
            font-weight: 650;
        }

        .reservation-notice--success {
            color: #376F25;
            background: var(--br-success-bg);

            border-color: rgba(77, 152, 44, .12);
        }

        .reservation-notice--warning {
            color: #765812;
            background: var(--br-warning-bg);

            border-color: rgba(155, 116, 29, .12);
        }

        .reservation-notice--danger {
            color: #A13939;
            background: var(--br-danger-bg);

            border-color: rgba(200, 74, 74, .12);
        }

        .reservation-notice--info {
            color: #316B66;
            background: var(--br-info-bg);

            border-color: rgba(57, 126, 119, .12);
        }

        .reservation-notice--soft {
            color: #626262;
            background: #fff;

            border-color: var(--br-border);
        }


        /* ==========================================================
           Overview grid
        ========================================================== */

        .reservation-overview {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;

            margin-bottom: 14px;
        }

        .reservation-panel {
            overflow: hidden;

            background: var(--br-surface);

            border: 1px solid rgba(46, 78, 36, .07);
            border-radius: var(--br-radius-lg);

            box-shadow: var(--br-shadow-sm);
        }

        .reservation-panel__header {
            display: flex;
            align-items: center;
            gap: 10px;

            padding: 15px;

            border-bottom: 1px solid #EFEFEF;
        }

        .reservation-panel__header-icon {
            width: 38px;
            height: 38px;
            flex-shrink: 0;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 12px;

            background: var(--br-glow);
            color: var(--br-deep);

            font-size: 18px;
        }

        .reservation-panel__header-content {
            min-width: 0;
        }

        .reservation-panel__title {
            display: block;

            margin-bottom: 1px;

            color: var(--br-text);

            font-size: 13.5px;
            font-weight: 650;
        }

        .reservation-panel__description {
            display: block;

            color: var(--br-muted);

            font-size: 10.5px;
            line-height: 1.7;
        }

        .reservation-panel__body {
            padding: 4px 15px;
        }


        /* ==========================================================
           Info row
        ========================================================== */

        .reservation-info-row {
            min-height: 61px;

            display: flex;
            align-items: center;
            gap: 10px;

            padding-block: 11px;

            border-bottom: 1px solid #F1F1F1;
        }

        .reservation-info-row:last-child {
            border-bottom: 0;
        }

        .reservation-info-row__icon {
            width: 33px;
            height: 33px;
            flex: 0 0 33px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 11px;

            background: #F8F8F8;
            color: var(--br-primary);

            font-size: 16px;
        }

        .reservation-info-row__content {
            flex: 1;
            min-width: 0;
        }

        .reservation-info-row__label {
            display: block;

            margin-bottom: 3px;

            color: var(--br-muted);

            font-size: 10.5px;
            line-height: 1.5;
            font-weight: 500;
        }

        .reservation-info-row__value {
            display: block;

            overflow-wrap: anywhere;

            color: var(--br-text);

            font-size: 12.5px;
            line-height: 1.7;
            font-weight: 600;
        }


        /* ==========================================================
           Copy card number
        ========================================================== */

        .copy-card-number {
            width: fit-content;
            max-width: 100%;
            min-height: 42px;

            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;

            margin-top: 5px;
            padding: 7px 10px;

            border: 1px solid rgba(140, 198, 63, .28);
            border-radius: 12px;

            outline: none;

            background: var(--br-glow);
            color: var(--br-deep);

            font-family: inherit;

            cursor: pointer;

            user-select: none;
            -webkit-user-select: none;
            -webkit-tap-highlight-color: transparent;

            transition:
                transform .18s ease,
                background-color .18s ease,
                border-color .18s ease,
                box-shadow .18s ease;
        }

        .copy-card-number:hover {
            background: #F3FAE9;
            border-color: var(--br-primary);
        }

        .copy-card-number:active {
            transform: scale(.98);
        }

        .copy-card-number:focus-visible {
            box-shadow:
                0 0 0 4px rgba(140, 198, 63, .14);
        }

        .copy-card-number__icon {
            width: 24px;
            height: 24px;
            flex: 0 0 24px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 8px;

            background: rgba(140, 198, 63, .13);
            color: var(--br-secondary);

            font-size: 15px;
        }

        .copy-card-number__number {
            direction: ltr;
            unicode-bidi: embed;

            color: var(--br-deep);

            font-size: 12.5px;
            line-height: 1.5;
            font-weight: 650;

            letter-spacing: .4px;
        }

        .copy-card-number__hint {
            color: #6D7B68;

            font-size: 9.5px;
            font-weight: 500;

            white-space: nowrap;
        }

        .copy-card-number.is-copied {
            background: var(--br-success-bg);

            border-color: rgba(77, 152, 44, .35);
        }

        .copy-card-number.is-copied .copy-card-number__icon {
            background: rgba(77, 152, 44, .12);
            color: var(--br-success);
        }

        .copy-card-number.is-copied .copy-card-number__number,
        .copy-card-number.is-copied .copy-card-number__hint {
            color: var(--br-success);
        }

        .payment-card-copy-wrap {
            margin-top: 8px;
        }


        /* ==========================================================
           Copy toast
        ========================================================== */

        .copy-toast {
            position: fixed;
            z-index: 99999;

            right: 50%;
            bottom: 20px;

            min-height: 46px;

            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;

            padding: 10px 15px;

            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 14px;

            background: var(--br-deep);
            color: #fff;

            box-shadow:
                0 14px 40px rgba(46, 78, 36, .25);

            font-size: 11.5px;
            line-height: 1.6;
            font-weight: 500;

            opacity: 0;
            visibility: hidden;

            pointer-events: none;

            transform: translate(50%, 14px);

            transition:
                opacity .2s ease,
                visibility .2s ease,
                transform .2s ease;
        }

        .copy-toast i {
            color: var(--br-primary);
            font-size: 19px;
        }

        .copy-toast.is-show {
            opacity: 1;
            visibility: visible;

            transform: translate(50%, 0);
        }


        /* ==========================================================
           Timeline details
        ========================================================== */

        .reservation-history {
            overflow: hidden;

            margin-bottom: 14px;

            border: 1px solid rgba(46, 78, 36, .07);
            border-radius: var(--br-radius-lg);

            background: var(--br-surface);

            box-shadow: var(--br-shadow-sm);
        }

        .reservation-history summary {
            list-style: none;

            display: flex;
            align-items: center;
            gap: 10px;

            padding: 15px;

            color: var(--br-text);

            font-size: 13px;
            font-weight: 600;

            cursor: pointer;
        }

        .reservation-history summary::-webkit-details-marker {
            display: none;
        }

        .reservation-history summary::after {
            content: "\ea4e";

            font-family: "remixicon";

            margin-right: auto;

            color: var(--br-muted);

            font-size: 18px;

            transition: transform .2s ease;
        }

        .reservation-history[open] summary::after {
            transform: rotate(180deg);
        }

        .reservation-history__icon {
            width: 37px;
            height: 37px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 12px;

            background: var(--br-glow);
            color: var(--br-deep);

            font-size: 18px;
        }

        .reservation-history__body {
            padding: 8px 15px 13px;

            border-top: 1px solid #EFEFEF;
        }

        .history-item {
            position: relative;

            display: flex;
            gap: 11px;

            padding: 8px 0;
        }

        .history-item__dot {
            width: 10px;
            height: 10px;
            flex: 0 0 10px;

            margin-top: 7px;

            border-radius: 50%;

            background: var(--br-primary);

            box-shadow:
                0 0 0 4px var(--br-glow);
        }

        .history-item__content {
            min-width: 0;
        }

        .history-item__label {
            margin-bottom: 2px;

            color: var(--br-muted);

            font-size: 10.5px;
        }

        .history-item__date {
            color: var(--br-text);

            font-size: 12px;
            line-height: 1.7;
            font-weight: 600;
        }


        /* ==========================================================
           Form sections
        ========================================================== */

        .student-action-card {
            position: relative;

            overflow: hidden;

            margin-bottom: 14px;

            background: var(--br-surface);

            border: 1px solid rgba(46, 78, 36, .08);
            border-radius: var(--br-radius-xl);

            box-shadow: var(--br-shadow);
        }

        .student-action-card::before {
            content: "";

            position: absolute;

            right: 0;
            top: 0;

            width: 4px;
            height: 100%;

            background: linear-gradient(180deg,
                    var(--br-primary),
                    var(--br-secondary));
        }

        .student-action-card__head {
            padding: 17px 17px 13px;

            border-bottom: 1px solid #EFEFEF;
        }

        .student-action-card__title-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .student-action-card__icon {
            width: 42px;
            height: 42px;
            flex: 0 0 42px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 14px;

            color: #fff;
            background: var(--br-deep);

            font-size: 19px;
        }

        .student-action-card__title {
            margin: 0 0 2px;

            color: var(--br-text);

            font-size: 14.5px;
            line-height: 1.7;
            font-weight: 650;
        }

        .student-action-card__subtitle {
            color: var(--br-muted);

            font-size: 10.5px;
            line-height: 1.8;
        }

        .student-action-card__body {
            padding: 16px;
        }


        /* ==========================================================
           Inputs
        ========================================================== */

        .student-form-grid {
            display: grid;
            grid-template-columns: 1fr;

            gap: 13px;
        }

        .student-field {
            min-width: 0;
        }

        .student-field--full {
            grid-column: 1 / -1;
        }

        .student-field__label {
            display: block;

            margin-bottom: 7px;

            color: #424242;

            font-size: 12px;
            line-height: 1.7;
            font-weight: 550;
        }

        .student-reservation-page .student-input,
        .student-reservation-page .student-select,
        .student-reservation-page .student-textarea {
            width: 100%;

            border: 1px solid var(--br-border);
            border-radius: 14px;

            outline: none;

            background: #fff;
            color: var(--br-text);

            font-family: inherit;
            font-size: 13px;
            font-weight: 400;

            transition:
                border-color .18s ease,
                box-shadow .18s ease,
                background-color .18s ease;
        }

        .student-reservation-page .student-input,
        .student-reservation-page .student-select {
            height: 48px;

            padding: 0 13px;
        }

        .student-reservation-page .student-textarea {
            min-height: 100px;

            padding: 12px 13px;

            resize: vertical;

            line-height: 1.9;
        }

        .student-reservation-page .student-input:focus,
        .student-reservation-page .student-select:focus,
        .student-reservation-page .student-textarea:focus {
            border-color: var(--br-secondary);

            box-shadow:
                0 0 0 4px rgba(140, 198, 63, .12);
        }

        .student-reservation-page .student-input::placeholder,
        .student-reservation-page .student-textarea::placeholder {
            color: #B2B2B2;
        }


        /* ==========================================================
           Exam choices
        ========================================================== */

        .exam-choice-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;

            gap: 8px;
        }

        .exam-choice {
            position: relative;

            min-width: 0;

            cursor: pointer;
        }

        .exam-choice input {
            position: absolute;

            opacity: 0;

            pointer-events: none;
        }

        .exam-choice__box {
            min-height: 44px;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 8px 10px;

            border: 1px solid var(--br-border);
            border-radius: 13px;

            background: #fff;
            color: #555;

            font-size: 11.5px;
            font-weight: 500;

            text-align: center;

            transition:
                background-color .18s ease,
                color .18s ease,
                border-color .18s ease,
                box-shadow .18s ease;
        }

        .exam-choice input:checked+.exam-choice__box {
            color: var(--br-deep);
            background: var(--br-glow);

            border-color: var(--br-primary);

            box-shadow:
                inset 0 0 0 1px rgba(140, 198, 63, .15);
        }


        /* ==========================================================
           Buttons
        ========================================================== */

        .student-btn {
            min-height: 48px;

            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;

            padding: 10px 16px;

            border: 0;
            border-radius: 14px;

            text-decoration: none !important;

            font-family: inherit;
            font-size: 12.5px;
            font-weight: 600;

            cursor: pointer;

            transition:
                transform .18s ease,
                box-shadow .18s ease,
                background-color .18s ease;
        }

        .student-btn:active {
            transform: scale(.985);
        }

        .student-btn--primary {
            width: 100%;

            color: #fff !important;

            background: linear-gradient(135deg,
                    var(--br-primary),
                    var(--br-secondary));

            box-shadow:
                0 8px 22px rgba(107, 191, 58, .18);
        }

        .student-btn--outline {
            color: var(--br-deep) !important;
            background: #fff;

            border: 1px solid rgba(46, 78, 36, .14);
        }


        /* ==========================================================
           Upload
        ========================================================== */

        .student-upload {
            position: relative;

            padding: 18px 13px;

            border: 1.5px dashed rgba(140, 198, 63, .45);
            border-radius: 17px;

            background:
                linear-gradient(180deg,
                    rgba(232, 245, 216, .4),
                    rgba(250, 248, 242, .25));

            text-align: center;
        }

        .student-upload__icon {
            width: 48px;
            height: 48px;

            display: flex;
            align-items: center;
            justify-content: center;

            margin: 0 auto 9px;

            border-radius: 15px;

            background: var(--br-glow);
            color: var(--br-deep);

            font-size: 22px;
        }

        .student-upload__title {
            margin-bottom: 3px;

            color: var(--br-text);

            font-size: 12px;
            line-height: 1.8;
            font-weight: 600;
        }

        .student-upload__hint {
            margin-bottom: 12px;

            color: var(--br-muted);

            font-size: 10.5px;
            line-height: 1.8;
        }

        .student-file-input {
            width: 100%;

            padding: 6px;

            border: 1px solid var(--br-border);
            border-radius: 12px;

            background: #fff;
            color: var(--br-muted);

            font-family: inherit;
            font-size: 11px;
        }

        .student-file-input::file-selector-button {
            margin-left: 8px;
            padding: 8px 11px;

            border: 0;
            border-radius: 9px;

            background: var(--br-deep);
            color: #fff;

            font-family: inherit;
            font-size: 11px;

            cursor: pointer;
        }


        /* ==========================================================
           Upload preview
        ========================================================== */

        .upload-preview {
            display: none;

            margin-top: 14px;

            text-align: right;
        }

        .upload-preview.is-visible {
            display: block;
        }

        .upload-preview__card {
            position: relative;

            overflow: hidden;

            border: 1px solid var(--br-border);
            border-radius: 16px;

            background: #fff;

            box-shadow:
                0 5px 18px rgba(46, 78, 36, .05);
        }

        .upload-preview__image-wrap {
            position: relative;

            width: 100%;
            height: 250px;

            display: flex;
            align-items: center;
            justify-content: center;

            overflow: hidden;

            background:
                linear-gradient(135deg,
                    #F7F7F7,
                    #FCFCFC);
        }

        .upload-preview__image {
            display: block;

            width: 100%;
            height: 100%;

            object-fit: contain;
        }

        .upload-preview__file {
            display: flex;
            align-items: center;
            gap: 11px;

            padding: 12px;
        }

        .upload-preview__file-icon {
            width: 44px;
            height: 44px;
            flex: 0 0 44px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 13px;

            background: var(--br-glow);
            color: var(--br-deep);

            font-size: 21px;
        }

        .upload-preview__file-content {
            flex: 1;
            min-width: 0;
        }

        .upload-preview__file-name {
            overflow: hidden;

            margin-bottom: 3px;

            color: var(--br-text);

            font-size: 11.5px;
            font-weight: 600;

            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .upload-preview__file-meta {
            color: var(--br-muted);

            font-size: 10px;
            line-height: 1.7;
        }

        .upload-preview__actions {
            display: flex;
            align-items: center;
            gap: 7px;

            padding: 10px 12px;

            border-top: 1px solid #F0F0F0;
        }

        .upload-preview__action {
            min-height: 38px;

            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;

            padding: 7px 11px;

            border: 1px solid var(--br-border);
            border-radius: 11px;

            outline: none;

            background: #fff;
            color: var(--br-deep);

            font-family: inherit;
            font-size: 10.5px;
            font-weight: 500;

            text-decoration: none !important;

            cursor: pointer;

            transition:
                background-color .18s ease,
                border-color .18s ease,
                color .18s ease,
                transform .18s ease;
        }

        .upload-preview__action:hover {
            background: var(--br-glow);
            border-color: var(--br-primary);

            color: var(--br-deep);
        }

        .upload-preview__action:active {
            transform: scale(.98);
        }

        .upload-preview__action--danger {
            margin-right: auto;

            background: var(--br-danger-bg);
            color: var(--br-danger);

            border-color: rgba(200, 74, 74, .18);
        }

        .upload-preview__action--danger:hover {
            background: var(--br-danger-bg);
            color: var(--br-danger);

            border-color: rgba(200, 74, 74, .35);
        }

        .upload-preview__pdf {
            min-height: 170px;

            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;

            padding: 22px;

            background:
                linear-gradient(180deg,
                    #FFFFFF,
                    #FAFAFA);

            text-align: center;
        }

        .upload-preview__pdf-icon {
            width: 60px;
            height: 60px;

            display: flex;
            align-items: center;
            justify-content: center;

            margin-bottom: 10px;

            border-radius: 18px;

            background: #FFF0F0;
            color: var(--br-danger);

            font-size: 29px;
        }

        .upload-preview__pdf-title {
            width: 100%;
            max-width: 320px;

            overflow: hidden;

            color: var(--br-text);

            font-size: 12px;
            font-weight: 600;

            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .upload-preview__pdf-meta {
            margin-top: 4px;

            color: var(--br-muted);

            font-size: 10px;
        }

        .upload-preview__generic {
            min-height: 140px;

            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;

            padding: 20px;

            text-align: center;
        }

        .upload-preview__generic-icon {
            width: 56px;
            height: 56px;

            display: flex;
            align-items: center;
            justify-content: center;

            margin-bottom: 8px;

            border-radius: 17px;

            background: var(--br-glow);
            color: var(--br-deep);

            font-size: 26px;
        }


        /* ==========================================================
           Contact
        ========================================================== */

        .reservation-contact {
            display: flex;
            align-items: center;
            gap: 10px;

            padding: 13px 14px;
            margin-bottom: 14px;

            border: 1px solid var(--br-border);
            border-radius: 17px;

            background: rgba(255, 255, 255, .72);
        }

        .reservation-contact__icon {
            width: 39px;
            height: 39px;
            flex: 0 0 39px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 13px;

            background: var(--br-glow);
            color: var(--br-deep);

            font-size: 19px;
        }

        .reservation-contact__text {
            min-width: 0;

            color: #666;

            font-size: 11.5px;
            line-height: 1.9;
        }

        .reservation-contact__phone {
            display: inline-block;

            color: var(--br-deep);

            font-weight: 650;

            text-decoration: none;
        }


        /* ==========================================================
           Tablet
        ========================================================== */

        @media (min-width: 576px) {

            .student-reservation-page {
                padding-inline: 16px;
            }

            .reservation-hero {
                padding: 22px;
            }

            .reservation-hero__head {
                flex-direction: row;
                align-items: flex-start;
                justify-content: space-between;
            }

            .hero-actions {
                grid-template-columns: repeat(2, max-content);
                justify-content: start;
            }

            .student-form-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .student-btn--primary {
                width: auto;
                min-width: 150px;
            }

            .exam-choice-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .upload-preview__image-wrap {
                height: 300px;
            }
        }


        /* ==========================================================
           Desktop
        ========================================================== */

        @media (min-width: 768px) {

            .student-reservation-page {
                padding-top: 24px;
            }

            .reservation-overview {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .reservation-brand__title {
                font-size: 20px;
            }

            .reservation-brand__subtitle {
                font-size: 13px;
            }

            .reservation-steps {
                grid-auto-flow: initial;
                grid-auto-columns: initial;

                grid-template-columns: repeat(6, minmax(0, 1fr));

                overflow: visible;
            }

            .reservation-step {
                min-height: 98px;
            }

            .student-action-card__body {
                padding: 20px;
            }

            .student-action-card__head {
                padding: 19px 20px 15px;
            }

            .upload-preview__image-wrap {
                height: 340px;
            }
        }


        @media (min-width: 992px) {

            .reservation-hero {
                padding: 25px;
            }

            .reservation-progress-card {
                padding: 18px;
            }

            .reservation-overview {
                gap: 14px;
            }
        }


        /* ==========================================================
           Mobile
        ========================================================== */

        @media (max-width: 575.98px) {

            /* stack the card so long exam names and the buttons never collide */
            .plan-card {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }

            .plan-card__actions {
                width: 100%;
            }

            .plan-btn {
                flex: 1 1 0;
                min-width: 0;
            }

            .copy-card-number {
                width: 100%;

                min-height: 46px;

                justify-content: center;

                padding-inline: 10px;
            }

            .copy-card-number__number {
                font-size: 12px;
            }

            .copy-card-number__hint {
                font-size: 9px;
            }

            .copy-toast {
                width: calc(100% - 28px);

                bottom: 14px;
            }

            .upload-preview__image-wrap {
                height: 230px;
            }

            .upload-preview__actions {
                flex-wrap: wrap;
            }

            .upload-preview__action {
                flex: 1 1 calc(50% - 4px);

                min-width: 0;
            }

            .upload-preview__action--danger {
                margin-right: 0;
            }
        }
    </style>
@endpush


@section('content')

    @php

        $missing = $reservation->missingStudentFields();

        $canUpdate = !$isLinkExpired
            && $reservation->status->allowsPublicUpdates();

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

        $statusTone = match (true) {

            in_array(
                $reservation->status,
                [
                    \App\Enums\ReservationStatus::Confirmed,
                    \App\Enums\ReservationStatus::Completed
                ],
                true
            ) => 'success',

            in_array(
                $reservation->status,
                [
                    \App\Enums\ReservationStatus::Cancelled,
                    \App\Enums\ReservationStatus::PaymentRejected,
                    \App\Enums\ReservationStatus::NoShow
                ],
                true
            ) => 'danger',

            $reservation->status === \App\Enums\ReservationStatus::Expired
            || $isLinkExpired => 'neutral',

            $reservation->status
            === \App\Enums\ReservationStatus::PendingPaymentApproval => 'info',

            default => 'warning',
        };


        $messageTone = match (true) {

            in_array(
                $reservation->status,
                [
                    \App\Enums\ReservationStatus::Cancelled,
                    \App\Enums\ReservationStatus::PaymentRejected,
                    \App\Enums\ReservationStatus::NoShow
                ],
                true
            ) => 'danger',

            $reservation->status === \App\Enums\ReservationStatus::Expired
            || $isLinkExpired => 'warning',

            in_array(
                $reservation->status,
                [
                    \App\Enums\ReservationStatus::Confirmed,
                    \App\Enums\ReservationStatus::Completed
                ],
                true
            ) => 'success',

            default => 'info',
        };


        $stepIcons = [
            'created' => 'ri-file-list-3-line',
            'completion' => 'ri-user-line',
            'prepayment' => 'ri-bank-card-line',
            'receipt_approval' => 'ri-shield-check-line',
            'confirmed' => 'ri-checkbox-circle-line',
            'completed' => 'ri-check-double-line',
        ];


        $currentFlowStep =
            collect($flowSteps)->firstWhere('state', 'active')
            ?? collect($flowSteps)->firstWhere('state', 'failed')
            ?? collect($flowSteps)->where('state', 'completed')->last()
            ?? collect($flowSteps)->first();


        $studentReportCard =
            $reservation->reportCards
                ->firstWhere(
                    'source',
                    \App\Models\ReservationDocument::SOURCE_STUDENT
                )
            ?: $reservation->reportCards->first();


        $contactPhone = $settings->get('contact_phone');

        $contactPhoneHref = $contactPhone
            ? preg_replace('/[^0-9+]/', '', $contactPhone)
            : null;

    @endphp


    <div class="student-reservation-page">

        {{-- ========================================================
        HERO
        ========================================================= --}}

        <section class="reservation-hero">

            <div class="reservation-hero__head">

                <div class="reservation-brand">

                    <div class="reservation-brand__icon">
                        <i class="ri-graduation-cap-line"></i>
                    </div>

                    <div class="reservation-brand__content">

                        <h1 class="reservation-brand__title">
                            {{ $settings->get('institute_name', 'آموزشگاه') }}
                        </h1>

                        <div class="reservation-brand__subtitle">
مشاور: علیرضا مرادی                        </div>

                    </div>

                </div>


                <span class="reservation-status tone-{{ $statusTone }}">
                    {{ $reservation->status->label() }}
                </span>

            </div>


            @if($currentFlowStep)

                <div class="current-stage">

                    <div class="current-stage__icon">
                        <i class="{{ $stepIcons[$currentFlowStep['key']] ?? 'ri-route-line' }}"></i>
                    </div>

                    <div class="current-stage__text">

                        <span class="current-stage__label">
                            وضعیت فعلی رزرو شما
                        </span>

                        <span class="current-stage__value">
                            {{ $currentFlowStep['label'] }}
                        </span>

                    </div>

                </div>

            @endif


            @if($contactPhone || $publishedFieldSelectionPlan)

                <div class="hero-actions">

                    @if($publishedFieldSelectionPlan)

                    <a href="{{ route(
                                    'public.reservations.field-selection.show',
                                    $reservation->public_token
                                ) }}" class="hero-action hero-action--primary">
                        <i class="ri-list-check-3"></i>

                        مشاهده انتخاب رشته
                    </a>

                    @endif


                    @if($contactPhoneHref)

                        <a href="tel:{{ $contactPhoneHref }}" class="hero-action hero-action--soft">
                            <i class="ri-phone-line"></i>

                            تماس با آموزشگاه
                        </a>

                    @endif

                </div>

            @endif

            @if($publishedFieldSelectionPlan)
                <div class="text-center mt-3 text-success fw-semibold">انتخاب رشته شما آماده است.</div>
            @else
                <div class="text-center mt-3 text-muted">انتخاب رشته شما هنوز توسط آموزشگاه منتشر نشده است.</div>
            @endif

        </section>

        @if($visibleFieldSelectionPlans->isNotEmpty())
            <section class="reservation-progress-card">

                <div class="progress-heading">
                    <div class="progress-heading__title">
                        <i class="ri-list-check-2"></i>
                        انتخاب رشته‌های ثبت‌شده برای شما
                    </div>
                    <div class="progress-heading__hint">
                        {{ \App\Support\PersianDate::number($visibleFieldSelectionPlans->count()) }} مورد
                    </div>
                </div>

                <div class="plan-group-list">
                    @foreach($visibleFieldSelectionPlansByExamType as $examTypeLabel => $plans)
                        <div class="plan-group">
                            <div class="plan-group__title">
                                <i class="ri-graduation-cap-line"></i>
                                {{ $examTypeLabel }}
                            </div>

                            @foreach($plans as $visiblePlan)
                                <article class="plan-card">
                                    <div class="plan-card__info">
                                        <div class="plan-card__name">انتخاب رشته {{ $examTypeLabel }}</div>
                                        <div class="plan-card__meta">
                                            <span class="plan-card__date">
                                                <i class="ri-calendar-check-line"></i>
                                                {{ \App\Support\PersianDate::dateTime($visiblePlan->published_at) }}
                                            </span>
                                            <span class="plan-card__state">
                                                <i class="ri-checkbox-circle-fill"></i>
                                                منتشر شده
                                            </span>
                                        </div>
                                    </div>

                                    <div class="plan-card__actions">
                                        <a class="plan-btn plan-btn--primary"
                                            href="{{ route('public.reservations.field-selection.plan.show', [$reservation->public_token, $visiblePlan]) }}">
                                            <i class="ri-eye-line"></i> مشاهده انتخاب رشته
                                        </a>
                                        <a class="plan-btn plan-btn--ghost" target="_blank"
                                            href="{{ route('public.reservations.field-selection.plan.print', [$reservation->public_token, $visiblePlan]) }}">
                                            <i class="ri-printer-line"></i> چاپ
                                        </a>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endforeach
                </div>

            </section>
        @endif



        {{-- ========================================================
        PROGRESS
        ========================================================= --}}

        <section class="reservation-progress-card">

            <div class="progress-heading">

                <div class="progress-heading__title">

                    <i class="ri-route-line"></i>

                    مراحل رزرو

                </div>

                <div class="progress-heading__hint">
                    وضعیت مراحل رزرو شما
                </div>

            </div>


            <div class="reservation-steps">

                @foreach($flowSteps as $step)

                    <div @class([
                        'reservation-step',

                        'is-done'
                        => $step['state'] === 'completed',

                        'is-current'
                        => $step['state'] === 'active',

                        'is-failed'
                        => $step['state'] === 'failed',
                    ])>

                        <div class="reservation-step__icon">

                            <i class="{{ $stepIcons[$step['key']] ?? 'ri-circle-line' }}"></i>

                        </div>

                        <div class="reservation-step__label">
                            {{ $step['label'] }}
                        </div>

                    </div>

                @endforeach

            </div>

        </section>



        {{-- ========================================================
        IMPORTANT MESSAGES
        ========================================================= --}}

        <div class="reservation-notices">

            @if($flowMessage || $isLinkExpired)

                <div class="reservation-notice reservation-notice--{{ $messageTone }}">

                    <i class="ri-information-line"></i>

                    <div>
                        {{
                $flowMessage
                ?: 'مهلت تکمیل اطلاعات یا پرداخت به پایان رسیده است. لطفاً با آموزشگاه تماس بگیرید.'
                            }}
                    </div>

                </div>

            @endif


            @if($showCompletionWarning)

                <div class="reservation-notice reservation-notice--warning">

                    <i class="ri-error-warning-line"></i>

                    <div>

                        <strong>
                            اطلاعات رزرو شما هنوز کامل نشده است.
                        </strong>

                        <div>
                            در صورت عدم تکمیل اطلاعات، رزرو شما باطل خواهد شد.
                        </div>


                        @if($reservation->public_token_expires_at)

                                    <div>
                                        مهلت تکمیل:

                                        {{ \App\Support\PersianDate::dateTime(
                                $reservation->public_token_expires_at
                            ) }}
                                    </div>

                        @endif

                    </div>

                </div>

            @endif


            @if($showPaymentWarning)

                    <div class="reservation-notice reservation-notice--warning">

                        <i class="ri-bank-card-line"></i>

                        <div>

                            <strong>
                                پیش‌پرداخت رزرو در انتظار پرداخت است.
                            </strong>

                            <div>
                                مبلغ:

                                {{ \App\Support\PersianDate::money(
                    $reservation->prepayment_amount
                ) }}
                            </div>


                            @if($reservation->payment_deadline_at)

                                        <div class="mt-1">در صورت عدم پرداخت پیش‌پرداخت در زمان مقرر، رزرو شما باطل خواهد شد.</div>

                                        <div>
                                            مهلت پرداخت:

                                            {{ \App\Support\PersianDate::dateTime(
                                    $reservation->payment_deadline_at
                                ) }}
                                        </div>

                            @endif


                            @if($reservation->paymentCard)

                                <div>
                                    {{ $reservation->paymentCard->bank_name }}
                                    -
                                    {{ $reservation->paymentCard->holder_name }}
                                </div>


                                <div class="payment-card-copy-wrap">

                                    <button type="button" class="copy-card-number js-copy-card"
                                        data-card-number="{{ $reservation->paymentCard->formattedNumber() }}"
                                        aria-label="کپی شماره کارت">

                                        <span class="copy-card-number__icon">
                                            <i class="ri-file-copy-line"></i>
                                        </span>

                                        <span class="copy-card-number__number">
                                            {{ $reservation->paymentCard->formattedNumber() }}
                                        </span>

                                        <span class="copy-card-number__hint">
                                            لمس برای کپی
                                        </span>

                                    </button>

                                </div>

                            @endif

                        </div>

                    </div>

            @endif


            <div class="reservation-notice reservation-notice--soft">

                <i class="ri-time-line"></i>

                <div>

                    برای لغو یا تغییر زمان رزرو، حداقل

                    <strong>
                        ۲۴ ساعت قبل
                    </strong>

                    با آموزشگاه هماهنگ کنید.

                </div>

            </div>

        </div>



        {{-- ========================================================
        INFORMATION
        ========================================================= --}}

        <div class="reservation-overview">

            {{-- Reservation info --}}

            <section class="reservation-panel">

                <div class="reservation-panel__header">

                    <div class="reservation-panel__header-icon">
                        <i class="ri-calendar-check-line"></i>
                    </div>

                    <div class="reservation-panel__header-content">

                        <span class="reservation-panel__title">
                            اطلاعات رزرو
                        </span>

                        <span class="reservation-panel__description">
                            زمان و مشاور رزرو شما
                        </span>

                    </div>

                </div>


                <div class="reservation-panel__body">

                    <div class="reservation-info-row">

                        <div class="reservation-info-row__icon">
                            <i class="ri-user-star-line"></i>
                        </div>

                        <div class="reservation-info-row__content">

                            <span class="reservation-info-row__label">
                                مشاور
                            </span>

                            <span class="reservation-info-row__value">

                                {{
        $reservation->advisor?->name
        ?: $reservation->slot?->advisor?->name
        ?: '-'
                                }}

                            </span>

                        </div>

                    </div>



                    <div class="reservation-info-row">

                        <div class="reservation-info-row__icon">
                            <i class="ri-calendar-line"></i>
                        </div>

                        <div class="reservation-info-row__content">

                            <span class="reservation-info-row__label">
                                تاریخ رزرو
                            </span>

                            <span class="reservation-info-row__value">

                                {{
        $reservation->slot
        ? \App\Support\PersianDate::date(
            $reservation->slot->date
        )
        : '-'
                                }}

                            </span>

                        </div>

                    </div>



                    <div class="reservation-info-row">

                        <div class="reservation-info-row__icon">
                            <i class="ri-time-line"></i>
                        </div>

                        <div class="reservation-info-row__content">

                            <span class="reservation-info-row__label">
                                ساعت رزرو
                            </span>

                            <span class="reservation-info-row__value ltr">

                                @if($reservation->slot)

                                                        {{
                                    \App\Support\PersianDate::time(
                                        $reservation->assignedStartTime()
                                    )
                                                            }}

                                                        -

                                                        {{
                                    \App\Support\PersianDate::time(
                                        $reservation->assignedEndTime()
                                    )
                                                            }}

                                @else

                                    -

                                @endif

                            </span>

                        </div>

                    </div>



                    <div class="reservation-info-row">

                        <div class="reservation-info-row__icon">
                            <i class="ri-checkbox-circle-line"></i>
                        </div>

                        <div class="reservation-info-row__content">

                            <span class="reservation-info-row__label">
                                وضعیت
                            </span>

                            <span class="reservation-info-row__value">
                                {{ $reservation->status->label() }}
                            </span>

                        </div>

                    </div>

                </div>

            </section>



            {{-- Student information --}}

            <section class="reservation-panel">

                <div class="reservation-panel__header">

                    <div class="reservation-panel__header-icon">
                        <i class="ri-user-smile-line"></i>
                    </div>

                    <div class="reservation-panel__header-content">

                        <span class="reservation-panel__title">
                            اطلاعات دانش‌آموز
                        </span>

                        <span class="reservation-panel__description">
                            مشخصات ثبت‌شده شما
                        </span>

                    </div>

                </div>


                <div class="reservation-panel__body">

                    <div class="reservation-info-row">

                        <div class="reservation-info-row__icon">
                            <i class="ri-user-line"></i>
                        </div>

                        <div class="reservation-info-row__content">

                            <span class="reservation-info-row__label">
                                نام و نام خانوادگی
                            </span>

                            <span class="reservation-info-row__value">
                                {{ $reservation->student?->full_name ?: '-' }}
                            </span>

                        </div>

                    </div>



                    <div class="reservation-info-row">

                        <div class="reservation-info-row__icon">
                            <i class="ri-graduation-cap-line"></i>
                        </div>

                        <div class="reservation-info-row__content">

                            <span class="reservation-info-row__label">
                                رشته
                            </span>

                            <span class="reservation-info-row__value">
                                {{ $reservation->student?->major ?: '-' }}
                            </span>

                        </div>

                    </div>



                    <div class="reservation-info-row">

                        <div class="reservation-info-row__icon">
                            <i class="ri-map-pin-line"></i>
                        </div>

                        <div class="reservation-info-row__content">

                            <span class="reservation-info-row__label">
                                منطقه
                            </span>

                            <span class="reservation-info-row__value">
                                {{ $reservation->student?->region ?: '-' }}
                            </span>

                        </div>

                    </div>



                    <div class="reservation-info-row">

                        <div class="reservation-info-row__icon">
                            <i class="ri-bar-chart-line"></i>
                        </div>

                        <div class="reservation-info-row__content">

                            <span class="reservation-info-row__label">
                                تراز
                            </span>

                            <span class="reservation-info-row__value">
                                {{ $reservation->student?->score ?: '-' }}
                            </span>

                        </div>

                    </div>



                    <div class="reservation-info-row">

                        <div class="reservation-info-row__icon">
                            <i class="ri-file-text-line"></i>
                        </div>

                        <div class="reservation-info-row__content">

                            <span class="reservation-info-row__label">
                                نوع کنکور
                            </span>

                            <span class="reservation-info-row__value">
                                {{ $reservation->student?->examTypeLabel() ?: '-' }}
                            </span>

                        </div>

                    </div>



                    <div class="reservation-info-row">

                        <div class="reservation-info-row__icon">
                            <i class="ri-phone-line"></i>
                        </div>

                        <div class="reservation-info-row__content">

                            <span class="reservation-info-row__label">
                                شماره تماس
                            </span>

                            <span class="reservation-info-row__value ltr">

                                {{
        $reservation
            ->student?->phones
            ->pluck('phone')
            ->implode(' / ')
        ?: '-'
                                }}

                            </span>

                        </div>

                    </div>

                </div>

            </section>



            {{-- Payment

            @if($reservation->prepayment_required)

            <section class="reservation-panel">

                <div class="reservation-panel__header">

                    <div class="reservation-panel__header-icon">
                        <i class="ri-bank-card-line"></i>
                    </div>

                    <div class="reservation-panel__header-content">

                        <span class="reservation-panel__title">
                            اطلاعات پرداخت
                        </span>

                        <span class="reservation-panel__description">
                            اطلاعات پیش‌پرداخت رزرو
                        </span>

                    </div>

                </div>


                <div class="reservation-panel__body">

                    <div class="reservation-info-row">

                        <div class="reservation-info-row__icon">
                            <i class="ri-money-dollar-circle-line"></i>
                        </div>

                        <div class="reservation-info-row__content">

                            <span class="reservation-info-row__label">
                                مبلغ پیش‌پرداخت
                            </span>

                            <span class="reservation-info-row__value">

                                {{ \App\Support\PersianDate::money(
                                $reservation->prepayment_amount
                                ) }}

                            </span>

                        </div>

                    </div>



                    <div class="reservation-info-row">

                        <div class="reservation-info-row__icon">
                            <i class="ri-hourglass-line"></i>
                        </div>

                        <div class="reservation-info-row__content">

                            <span class="reservation-info-row__label">
                                مهلت پرداخت
                            </span>

                            <span class="reservation-info-row__value">

                                {{ \App\Support\PersianDate::dateTime(
                                $reservation->payment_deadline_at
                                ) }}

                            </span>

                        </div>

                    </div>


                    @if($reservation->paymentCard)

                    <div class="reservation-info-row">

                        <div class="reservation-info-row__icon">
                            <i class="ri-bank-card-2-line"></i>
                        </div>

                        <div class="reservation-info-row__content">

                            <span class="reservation-info-row__label">
                                شماره کارت
                            </span>


                            <button type="button" class="copy-card-number js-copy-card"
                                data-card-number="{{ $reservation->paymentCard->formattedNumber() }}"
                                aria-label="کپی شماره کارت">

                                <span class="copy-card-number__icon">
                                    <i class="ri-file-copy-line"></i>
                                </span>

                                <span class="copy-card-number__number">
                                    {{ $reservation->paymentCard->formattedNumber() }}
                                </span>

                                <span class="copy-card-number__hint">
                                    لمس برای کپی
                                </span>

                            </button>

                        </div>

                    </div>



                    <div class="reservation-info-row">

                        <div class="reservation-info-row__icon">
                            <i class="ri-bank-line"></i>
                        </div>

                        <div class="reservation-info-row__content">

                            <span class="reservation-info-row__label">
                                بانک
                            </span>

                            <span class="reservation-info-row__value">
                                {{ $reservation->paymentCard->bank_name }}
                            </span>

                        </div>

                    </div>



                    <div class="reservation-info-row">

                        <div class="reservation-info-row__icon">
                            <i class="ri-user-line"></i>
                        </div>

                        <div class="reservation-info-row__content">

                            <span class="reservation-info-row__label">
                                صاحب کارت
                            </span>

                            <span class="reservation-info-row__value">
                                {{ $reservation->paymentCard->holder_name }}
                            </span>

                        </div>

                    </div>

                    @endif

                </div>

            </section>

            @endif--}}

        </div>



        {{-- ========================================================
        RESERVATION HISTORY
        ========================================================= --}}

        @if(collect($statusDates)->filter()->count())

            <details class="reservation-history">

                <summary>

                    <span class="reservation-history__icon">
                        <i class="ri-history-line"></i>
                    </span>

                    سوابق و زمان‌های رزرو

                </summary>


                <div class="reservation-history__body">

                    @foreach($statusDates as $label => $date)

                        @if($date)

                            <div class="history-item">

                                <div class="history-item__dot"></div>

                                <div class="history-item__content">

                                    <div class="history-item__label">
                                        {{ $label }}
                                    </div>

                                    <div class="history-item__date">
                                        {{ \App\Support\PersianDate::dateTime($date) }}
                                    </div>

                                </div>

                            </div>

                        @endif

                    @endforeach

                </div>

            </details>

        @endif



        {{-- ========================================================
        CONTACT
        ========================================================= --}}

        <div class="reservation-contact">

            <div class="reservation-contact__icon">
                <i class="ri-customer-service-2-line"></i>
            </div>

            <div class="reservation-contact__text">

                برای لغو یا تغییر زمان رزرو، لطفاً با آموزشگاه تماس بگیرید.

                @if($contactPhone)

                    <br>

                    @if($contactPhoneHref)

                        <a class="reservation-contact__phone ltr" href="tel:{{ $contactPhoneHref }}">
                            {{ $contactPhone }}
                        </a>

                    @else

                        <span class="reservation-contact__phone ltr">
                            {{ $contactPhone }}
                        </span>

                    @endif

                @endif

            </div>

        </div>



        {{-- ========================================================
        COMPLETE INFORMATION
        ========================================================= --}}

        @if($canUpdate && count($missing) > 0)

            <section class="student-action-card">

                <div class="student-action-card__head">

                    <div class="student-action-card__title-row">

                        <div class="student-action-card__icon">
                            <i class="ri-user-settings-line"></i>
                        </div>

                        <div>

                            <h2 class="student-action-card__title">
                                تکمیل اطلاعات
                            </h2>

                            <div class="student-action-card__subtitle">
                                اطلاعات زیر برای ادامه فرایند رزرو لازم است.
                            </div>

                        </div>

                    </div>

                </div>


                <div class="student-action-card__body">

                    <form method="post" action="{{ route(
                'public.reservations.complete',
                $reservation->public_token
            ) }}">

                        @csrf


                        <div class="student-form-grid">

                            @if(in_array('full_name', $missing, true))

                                <div class="student-field">

                                    <label class="student-field__label">
                                        نام و نام خانوادگی
                                    </label>

                                    <input type="text" name="full_name" value="{{ old('full_name') }}" class="student-input"
                                        placeholder="مثلاً علی رضایی" required>

                                </div>

                            @endif



                            @if(in_array('major', $missing, true))

                                <div class="student-field">

                                    <label class="student-field__label">
                                        رشته
                                    </label>

                                    <select name="major" class="student-select" required>

                                        <option value="">
                                            انتخاب کنید
                                        </option>


                                        @foreach($settings->get('majors', []) as $major)

                                            <option value="{{ $major }}" @selected(old('major') === $major)>
                                                {{ $major }}
                                            </option>

                                        @endforeach

                                    </select>

                                </div>

                            @endif



                            @if(in_array('region', $missing, true))

                                <div class="student-field">

                                    <label class="student-field__label">
                                        منطقه
                                    </label>

                                    <select name="region" class="student-select" required>

                                        <option value="">
                                            انتخاب کنید
                                        </option>


                                        @foreach(\App\Models\Student::regionOptions() as $region)

                                            <option value="{{ $region }}" @selected(old('region') === $region)>
                                                {{ $region }}
                                            </option>

                                        @endforeach

                                    </select>

                                </div>

                            @endif



                            @if(in_array('score', $missing, true))

                                <div class="student-field">

                                    <label class="student-field__label">
                                        تراز
                                    </label>

                                    <input type="text" inputmode="numeric" name="score" value="{{ old('score') }}"
                                        class="student-input" placeholder="تراز خود را وارد کنید" required>

                                </div>

                            @endif



                            @if(in_array('phone_one', $missing, true))

                                <div class="student-field">

                                    <label class="student-field__label">
                                        شماره تماس اول
                                    </label>

                                    <input type="tel" inputmode="tel" name="phone_one" value="{{ old('phone_one') }}"
                                        class="student-input ltr" placeholder="09xxxxxxxxx" required>

                                </div>



                                <div class="student-field">

                                    <label class="student-field__label">
                                        شماره تماس دوم
                                    </label>

                                    <input type="tel" inputmode="tel" name="phone_two" value="{{ old('phone_two') }}"
                                        class="student-input ltr" placeholder="اختیاری">

                                </div>

                            @endif



                            @if(in_array('exam_type', $missing, true))

                                <div class="student-field student-field--full">

                                    <label class="student-field__label">
                                        نوع کنکور
                                    </label>


                                    <div class="exam-choice-grid">

                                        @foreach($settings->get('exam_types', []) as $examType)

                                            <label class="exam-choice">

                                                <input type="checkbox" name="exam_type[]" value="{{ $examType }}" @checked(
                                                    in_array(
                                                        $examType,
                                                        old('exam_type', []),
                                                        true
                                                    )
                                                )>

                                                <span class="exam-choice__box">
                                                    {{ $examType }}
                                                </span>

                                            </label>

                                        @endforeach

                                    </div>

                                </div>

                            @endif



                            <div class="student-field student-field--full">

                                <label class="student-field__label">
                                    توضیح دانش‌آموز
                                </label>

                                <textarea name="student_note" class="student-textarea"
                                    placeholder="اگر توضیحی برای مشاور دارید اینجا بنویسید...">{{ old('student_note') }}</textarea>

                            </div>

                        </div>


                        <div style="margin-top: 16px;">

                            <button type="submit" class="student-btn student-btn--primary">

                                <i class="ri-check-line"></i>

                                ثبت و ادامه

                            </button>

                        </div>

                    </form>

                </div>

            </section>

        @endif



        {{-- ========================================================
        REPORT CARD
        ========================================================= --}}

        @if($studentReportCard || $canUploadReportCard)

            <section class="student-action-card">

                <div class="student-action-card__head">

                    <div class="student-action-card__title-row">

                        <div class="student-action-card__icon">
                            <i class="ri-file-chart-line"></i>
                        </div>

                        <div>

                            <h2 class="student-action-card__title">
                                کارنامه دانش‌آموز
                            </h2>

                            <div class="student-action-card__subtitle">
                                مشاهده یا ارسال فایل کارنامه
                            </div>

                        </div>

                    </div>

                </div>


                <div class="student-action-card__body">

                    @if($studentReportCard)

                            <div class="reservation-notice reservation-notice--success" style="margin-bottom: 12px;">

                                <i class="ri-checkbox-circle-line"></i>

                                <div>

                                    <strong>
                                        کارنامه شما ثبت شده است.
                                    </strong>

                                    <div>
                                        {{ $studentReportCard->original_name }}
                                    </div>

                                    <div>

                                        {{ \App\Support\PersianDate::dateTime(
                            $studentReportCard->created_at
                        ) }}

                                    </div>

                                </div>

                            </div>


                            <a href="{{ route(
                            'public.reservations.report-card.show',
                            [
                                $reservation->public_token,
                                $studentReportCard
                            ]
                        ) }}" class="student-btn student-btn--outline" style="margin-bottom: 14px;" target="_blank">

                                <i class="ri-eye-line"></i>

                                مشاهده کارنامه

                            </a>

                    @endif



                    @if($canUploadReportCard)

                            <form method="post" action="{{ route(
                            'public.reservations.report-card.store',
                            $reservation->public_token
                        ) }}" enctype="multipart/form-data">

                                @csrf


                                <div class="student-upload">

                                    <div class="student-upload__icon">
                                        <i class="ri-upload-cloud-2-line"></i>
                                    </div>

                                    <div class="student-upload__title">

                                        {{
                            $studentReportCard
                            ? 'جایگزینی کارنامه'
                            : 'آپلود کارنامه'
                                                }}

                                    </div>

                                    <div class="student-upload__hint">

                                        فایل‌های مجاز:

                                        JPG، JPEG، PNG، WEBP یا PDF

                                    </div>

                                    <input type="file" name="report_card" accept=".jpg,.jpeg,.png,.webp,.pdf"
                                        class="student-file-input js-file-preview-input" data-preview-target="report-card-preview"
                                        required>


                                    <div id="report-card-preview" class="upload-preview"></div>

                                </div>


                                <div style="margin-top: 13px; text-align: center;">

                                    <button type="submit" class="student-btn student-btn--primary">

                                        <i class="ri-upload-2-line"></i>

                                        {{
                            $studentReportCard
                            ? 'جایگزینی کارنامه'
                            : 'ثبت کارنامه'
                                                }}

                                    </button>

                                </div>

                            </form>

                    @endif

                </div>

            </section>

        @endif



        {{-- ========================================================
        PAYMENT RECEIPT
        ========================================================= --}}

        @if(
                $canUpdate
                && $reservation->prepayment_required
                && count($missing) === 0
                && in_array(
                    $reservation->status,
                    [
                        \App\Enums\ReservationStatus::PendingPrepayment,
                        \App\Enums\ReservationStatus::PaymentRejected
                    ],
                    true
                )
            )

            <section class="student-action-card">

                <div class="student-action-card__head">

                    <div class="student-action-card__title-row">

                        <div class="student-action-card__icon">
                            <i class="ri-bank-card-line"></i>
                        </div>

                        <div>

                            <h2 class="student-action-card__title">
                                ارسال فیش پرداخت
                            </h2>

                            <div class="student-action-card__subtitle">
                                پس از پرداخت، تصویر فیش را ارسال کنید.
                            </div>

                        </div>

                    </div>

                </div>


                <div class="student-action-card__body">

                    <div class="reservation-notice reservation-notice--info" style="margin-bottom: 12px;">

                        <i class="ri-bank-line"></i>

                        <div>

                            <strong>
                                اطلاعات پرداخت
                            </strong>

                            <div>
                                مبلغ:

                                {{ \App\Support\PersianDate::money(
                $reservation->prepayment_amount
            ) }}
                            </div>


                            @if($reservation->paymentCard)

                                <div>
                                    بانک:
                                    {{ $reservation->paymentCard->bank_name }}
                                </div>

                                <div>
                                    صاحب کارت:
                                    {{ $reservation->paymentCard->holder_name }}
                                </div>


                                <div class="payment-card-copy-wrap">

                                    <button type="button" class="copy-card-number js-copy-card"
                                        data-card-number="{{ $reservation->paymentCard->formattedNumber() }}"
                                        aria-label="کپی شماره کارت">

                                        <span class="copy-card-number__icon">
                                            <i class="ri-file-copy-line"></i>
                                        </span>

                                        <span class="copy-card-number__number">
                                            {{ $reservation->paymentCard->formattedNumber() }}
                                        </span>

                                        <span class="copy-card-number__hint">
                                            لمس برای کپی
                                        </span>

                                    </button>

                                </div>

                            @endif

                        </div>

                    </div>



                    @if($payment?->rejection_reason)

                        <div class="reservation-notice reservation-notice--danger" style="margin-bottom: 12px;">

                            <i class="ri-error-warning-line"></i>

                            <div>

                                <strong>
                                    دلیل رد فیش
                                </strong>

                                <div>
                                    {{ $payment->rejection_reason }}
                                </div>

                            </div>

                        </div>

                    @endif



                    <form method="post" action="{{ route(
                'public.reservations.upload-receipt',
                $reservation->public_token
            ) }}" enctype="multipart/form-data">

                        @csrf


                        <div class="student-upload">

                            <div class="student-upload__icon">
                                <i class="ri-image-add-line"></i>
                            </div>

                            <div class="student-upload__title">
                                تصویر فیش پرداخت
                            </div>

                            <div class="student-upload__hint">
                                حداکثر حجم مجاز فیش پرداخت
                                {{ \App\Support\PersianDate::number((int) ceil($settings->receiptMaxKilobytes() / 1024)) }}
                                مگابایت است. فرمت‌های مجاز: JPG، PNG، WEBP
                            </div>

                            <input type="file" name="receipt_image" accept=".jpg,.jpeg,.png,.webp"
                                class="student-file-input js-file-preview-input" data-preview-target="receipt-preview"
                                data-max-size-bytes="{{ $settings->receiptMaxKilobytes() * 1024 }}"
                                data-max-size-message="حجم فایل فیش پرداخت نباید بیشتر از {{ \App\Support\PersianDate::number((int) ceil($settings->receiptMaxKilobytes() / 1024)) }} مگابایت باشد."
                                required>


                            <div id="receipt-preview" class="upload-preview"></div>

                        </div>


                        <div style="margin-top: 13px; text-align: center;">

                            <button type="submit" class="student-btn student-btn--primary">

                                <i class="ri-send-plane-line"></i>

                                ارسال فیش پرداخت

                            </button>

                        </div>

                    </form>

                </div>

            </section>

        @endif


        {{-- ========================================================
        COPY TOAST
        ========================================================= --}}

        <div id="copy-card-toast" class="copy-toast" role="status" aria-live="polite">
            <i class="ri-checkbox-circle-line"></i>

            <span>
                شماره کارت کپی شد
            </span>
        </div>

    </div>

@endsection


@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const toast = document.getElementById('copy-card-toast');

            let toastTimer = null;


            /*
            |--------------------------------------------------------------------------
            | Convert Persian / Arabic numbers to English
            |--------------------------------------------------------------------------
            */

            function normalizeDigits(value) {

                if (!value) {
                    return '';
                }

                return String(value)

                    .replace(/[۰-۹]/g, function (digit) {
                        return String('۰۱۲۳۴۵۶۷۸۹'.indexOf(digit));
                    })

                    .replace(/[٠-٩]/g, function (digit) {
                        return String('٠١٢٣٤٥٦٧٨٩'.indexOf(digit));
                    })

                    .replace(/\D/g, '');
            }


            /*
            |--------------------------------------------------------------------------
            | Show toast
            |--------------------------------------------------------------------------
            */

            function showCopyToast() {

                if (!toast) {
                    return;
                }

                clearTimeout(toastTimer);

                toast.classList.add('is-show');

                toastTimer = setTimeout(function () {

                    toast.classList.remove('is-show');

                }, 1800);
            }


            /*
            |--------------------------------------------------------------------------
            | Fallback clipboard
            |--------------------------------------------------------------------------
            */

            function fallbackCopy(text) {

                const textarea = document.createElement('textarea');

                textarea.value = text;

                textarea.setAttribute('readonly', '');

                textarea.style.position = 'fixed';
                textarea.style.top = '-9999px';
                textarea.style.left = '-9999px';
                textarea.style.opacity = '0';

                document.body.appendChild(textarea);

                textarea.focus();
                textarea.select();

                textarea.setSelectionRange(
                    0,
                    textarea.value.length
                );

                let copied = false;

                try {

                    copied = document.execCommand('copy');

                } catch (error) {

                    copied = false;

                }

                textarea.remove();

                return copied;
            }


            /*
            |--------------------------------------------------------------------------
            | Successful copy UI
            |--------------------------------------------------------------------------
            */

            function setCopiedState(button) {

                const icon = button.querySelector(
                    '.copy-card-number__icon i'
                );

                const hint = button.querySelector(
                    '.copy-card-number__hint'
                );


                if (!button.dataset.defaultHint && hint) {
                    button.dataset.defaultHint = hint.textContent.trim();
                }


                button.classList.add('is-copied');


                if (icon) {
                    icon.className = 'ri-check-line';
                }


                if (hint) {
                    hint.textContent = 'کپی شد';
                }


                showCopyToast();


                setTimeout(function () {

                    button.classList.remove('is-copied');


                    if (icon) {
                        icon.className = 'ri-file-copy-line';
                    }


                    if (hint) {
                        hint.textContent =
                            button.dataset.defaultHint
                            || 'لمس برای کپی';
                    }

                }, 1500);
            }


            /*
            |--------------------------------------------------------------------------
            | Copy card
            |--------------------------------------------------------------------------
            */

            async function copyCard(button) {

                const formattedNumber =
                    button.dataset.cardNumber || '';

                const cardNumber =
                    normalizeDigits(formattedNumber);


                if (!cardNumber) {
                    return;
                }


                try {

                    if (
                        navigator.clipboard
                        && window.isSecureContext
                    ) {

                        await navigator.clipboard.writeText(
                            cardNumber
                        );

                    } else {

                        const copied =
                            fallbackCopy(cardNumber);

                        if (!copied) {
                            throw new Error('Copy failed');
                        }
                    }


                    setCopiedState(button);

                } catch (error) {

                    const copied =
                        fallbackCopy(cardNumber);


                    if (copied) {

                        setCopiedState(button);

                        return;
                    }


                    console.error(
                        'Unable to copy card number:',
                        error
                    );
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Card copy event
            |--------------------------------------------------------------------------
            */

            document.addEventListener(
                'click',
                function (event) {

                    const button =
                        event.target.closest(
                            '.js-copy-card'
                        );


                    if (!button) {
                        return;
                    }


                    event.preventDefault();

                    copyCard(button);
                }
            );


            /*
            |--------------------------------------------------------------------------
            | File Preview
            |--------------------------------------------------------------------------
            */

            const previewInputs =
                document.querySelectorAll(
                    '.js-file-preview-input'
                );

            const previewObjectUrls =
                new Map();


            /*
            |--------------------------------------------------------------------------
            | Format file size
            |--------------------------------------------------------------------------
            */

            function formatFileSize(bytes) {

                if (!bytes) {
                    return '0 KB';
                }


                const units = [
                    'B',
                    'KB',
                    'MB',
                    'GB'
                ];


                const index =
                    Math.floor(
                        Math.log(bytes)
                        / Math.log(1024)
                    );


                const safeIndex =
                    Math.min(
                        index,
                        units.length - 1
                    );


                const size =
                    bytes
                    / Math.pow(
                        1024,
                        safeIndex
                    );


                return (
                    size.toFixed(
                        safeIndex === 0
                            ? 0
                            : 1
                    )
                    + ' '
                    + units[safeIndex]
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Release object URL
            |--------------------------------------------------------------------------
            */

            function releasePreviewUrl(input) {

                const oldUrl =
                    previewObjectUrls.get(input);


                if (!oldUrl) {
                    return;
                }


                URL.revokeObjectURL(
                    oldUrl
                );


                previewObjectUrls.delete(
                    input
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Clear preview
            |--------------------------------------------------------------------------
            */

            function clearPreview(
                input,
                preview
            ) {

                releasePreviewUrl(
                    input
                );


                input.value = '';

                preview.innerHTML = '';

                preview.classList.remove(
                    'is-visible'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Bind remove preview
            |--------------------------------------------------------------------------
            */

            function bindRemovePreview(
                input,
                preview
            ) {

                const removeButton =
                    preview.querySelector(
                        '.js-remove-preview'
                    );


                if (!removeButton) {
                    return;
                }


                removeButton.addEventListener(
                    'click',
                    function () {

                        clearPreview(
                            input,
                            preview
                        );
                    }
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Image preview
            |--------------------------------------------------------------------------
            */

            function renderImagePreview(
                file,
                fileUrl,
                input,
                preview
            ) {

                preview.innerHTML = `
                    <div class="upload-preview__card">

                        <div class="upload-preview__image-wrap">

                            <img
                                src="${fileUrl}"
                                class="upload-preview__image"
                                alt="پیش نمایش تصویر انتخاب شده"
                            >

                        </div>


                        <div class="upload-preview__file">

                            <div class="upload-preview__file-icon">
                                <i class="ri-image-line"></i>
                            </div>

                            <div class="upload-preview__file-content">

                                <div class="upload-preview__file-name"></div>

                                <div class="upload-preview__file-meta">

                                    تصویر

                                    •

                                    ${formatFileSize(file.size)}

                                </div>

                            </div>

                        </div>


                        <div class="upload-preview__actions">

                            <a
                                href="${fileUrl}"
                                target="_blank"
                                rel="noopener"
                                class="upload-preview__action"
                            >
                                <i class="ri-fullscreen-line"></i>

                                مشاهده کامل
                            </a>


                            <button
                                type="button"
                                class="upload-preview__action upload-preview__action--danger js-remove-preview"
                            >
                                <i class="ri-delete-bin-line"></i>

                                حذف فایل
                            </button>

                        </div>

                    </div>
                `;


                const nameElement =
                    preview.querySelector(
                        '.upload-preview__file-name'
                    );


                if (nameElement) {

                    nameElement.textContent =
                        file.name;
                }


                bindRemovePreview(
                    input,
                    preview
                );
            }


            /*
            |--------------------------------------------------------------------------
            | PDF preview
            |--------------------------------------------------------------------------
            */

            function renderPdfPreview(
                file,
                fileUrl,
                input,
                preview
            ) {

                preview.innerHTML = `
                    <div class="upload-preview__card">

                        <div class="upload-preview__pdf">

                            <div class="upload-preview__pdf-icon">
                                <i class="ri-file-pdf-2-line"></i>
                            </div>

                            <div class="upload-preview__pdf-title"></div>

                            <div class="upload-preview__pdf-meta">

                                فایل PDF

                                •

                                ${formatFileSize(file.size)}

                            </div>

                        </div>


                        <div class="upload-preview__actions">

                            <a
                                href="${fileUrl}"
                                target="_blank"
                                rel="noopener"
                                class="upload-preview__action"
                            >
                                <i class="ri-eye-line"></i>

                                مشاهده PDF
                            </a>


                            <button
                                type="button"
                                class="upload-preview__action upload-preview__action--danger js-remove-preview"
                            >
                                <i class="ri-delete-bin-line"></i>

                                حذف فایل
                            </button>

                        </div>

                    </div>
                `;


                const titleElement =
                    preview.querySelector(
                        '.upload-preview__pdf-title'
                    );


                if (titleElement) {

                    titleElement.textContent =
                        file.name;
                }


                bindRemovePreview(
                    input,
                    preview
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Generic file preview
            |--------------------------------------------------------------------------
            */

            function renderGenericPreview(
                file,
                fileUrl,
                input,
                preview
            ) {

                preview.innerHTML = `
                    <div class="upload-preview__card">

                        <div class="upload-preview__generic">

                            <div class="upload-preview__generic-icon">
                                <i class="ri-file-line"></i>
                            </div>

                            <div class="upload-preview__file-name"></div>

                            <div class="upload-preview__file-meta">

                                ${formatFileSize(file.size)}

                            </div>

                        </div>


                        <div class="upload-preview__actions">

                            <a
                                href="${fileUrl}"
                                target="_blank"
                                rel="noopener"
                                class="upload-preview__action"
                            >
                                <i class="ri-eye-line"></i>

                                مشاهده فایل
                            </a>


                            <button
                                type="button"
                                class="upload-preview__action upload-preview__action--danger js-remove-preview"
                            >
                                <i class="ri-delete-bin-line"></i>

                                حذف فایل
                            </button>

                        </div>

                    </div>
                `;


                const nameElement =
                    preview.querySelector(
                        '.upload-preview__file-name'
                    );


                if (nameElement) {

                    nameElement.textContent =
                        file.name;
                }


                bindRemovePreview(
                    input,
                    preview
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Handle file inputs
            |--------------------------------------------------------------------------
            */

            previewInputs.forEach(
                function (input) {

                    input.addEventListener(
                        'change',
                        function () {

                            const targetId =
                                input.dataset.previewTarget;


                            if (!targetId) {
                                return;
                            }


                            const preview =
                                document.getElementById(
                                    targetId
                                );


                            if (!preview) {
                                return;
                            }


                            const file =
                                input.files
                                && input.files[0];


                            if (!file) {

                                clearPreview(
                                    input,
                                    preview
                                );

                                return;
                            }

                            const maxSizeBytes = Number(input.dataset.maxSizeBytes || 0);
                            if (maxSizeBytes && file.size > maxSizeBytes) {
                                input.value = '';
                                preview.textContent = input.dataset.maxSizeMessage || 'حجم فایل مجاز نیست.';
                                preview.classList.add('is-visible');
                                return;
                            }


                            releasePreviewUrl(
                                input
                            );


                            const fileUrl =
                                URL.createObjectURL(
                                    file
                                );


                            previewObjectUrls.set(
                                input,
                                fileUrl
                            );


                            const fileName =
                                file.name
                                    .toLowerCase();


                            const isImage =
                                file.type
                                    .startsWith(
                                        'image/'
                                    )
                                ||
                                /\.(jpg|jpeg|png|webp)$/i
                                    .test(
                                        fileName
                                    );


                            const isPdf =
                                file.type ===
                                'application/pdf'
                                ||
                                fileName
                                    .endsWith(
                                        '.pdf'
                                    );


                            if (isImage) {

                                renderImagePreview(
                                    file,
                                    fileUrl,
                                    input,
                                    preview
                                );

                            } else if (isPdf) {

                                renderPdfPreview(
                                    file,
                                    fileUrl,
                                    input,
                                    preview
                                );

                            } else {

                                renderGenericPreview(
                                    file,
                                    fileUrl,
                                    input,
                                    preview
                                );
                            }


                            preview.classList.add(
                                'is-visible'
                            );
                        }
                    );
                }
            );


            /*
            |--------------------------------------------------------------------------
            | Cleanup object URLs
            |--------------------------------------------------------------------------
            */

            window.addEventListener(
                'beforeunload',
                function () {

                    previewObjectUrls.forEach(
                        function (url) {

                            URL.revokeObjectURL(
                                url
                            );
                        }
                    );


                    previewObjectUrls.clear();
                }
            );

        });
    </script>
@endpush
