@extends('layouts.admin')

@section('title', 'ویرایش رزرو')

@push('styles')
    <style>
        .reservation-edit-wrapper {
            max-width: 1200px;
            margin: 0 auto;
        }

        .reservation-edit-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.25rem;
            padding: 1rem 1.25rem;
            background: var(--bg-card, #fff);
            border-radius: 14px;
            border: 1px solid rgba(0, 0, 0, .05);
        }

        .reservation-edit-header .title {
            display: flex;
            align-items: center;
            gap: .6rem;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--ink-800);
        }

        .reservation-edit-header .title i {
            color: var(--brand-600);
            font-size: 1.35rem;
        }

        .reservation-edit-header .back-btn {
            display: flex;
            align-items: center;
            gap: .4rem;
        }

        .reservation-form-container {
            background: transparent;
        }

        .reservation-form-container form {
            width: 100%;
        }

        @media(max-width:768px) {
            .reservation-edit-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .reservation-edit-header .back-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
@endpush


@section('content')

    <div class="reservation-edit-wrapper">

        <div class="reservation-edit-header">

            <div class="title">
                <i class="ri-calendar-check-line"></i>
                ویرایش رزرو
            </div>

            <a href="{{ route('admin.reservations.index') }}" class="btn btn-outline-secondary back-btn">
                <i class="ri-arrow-right-line"></i>
                بازگشت به لیست
            </a>

        </div>


        <div class="reservation-form-container">

            <form method="post" action="{{ route('admin.reservations.update', $reservation) }}">
                @method('put')

                @include('admin.reservations._form', [
                    'mode' => 'edit',
                    'defaultDeadlineHours' => 24,
                    'defaultPrepaymentAmount' => null
                ])

            </form>

        </div>

    </div>

@endsection