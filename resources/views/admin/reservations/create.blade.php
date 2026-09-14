@extends('layouts.admin')

@section('title', 'رزرو جدید')

@push('styles')
<style>
    .reservation-create-wrapper{
        max-width:1200px;
        margin:0 auto;
    }

    .reservation-create-header{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:1rem;
        margin-bottom:1.25rem;
        padding:1rem 1.25rem;
        background:var(--bg-card, #fff);
        border-radius:14px;
        border:1px solid rgba(0,0,0,.05);
    }

    .reservation-create-header .title{
        display:flex;
        align-items:center;
        gap:.6rem;
        font-size:1.1rem;
        font-weight:700;
        color:var(--ink-800);
    }

    .reservation-create-header .title i{
        color:var(--brand-600);
        font-size:1.35rem;
    }

    .reservation-create-header .back-btn{
        display:flex;
        align-items:center;
        gap:.4rem;
    }

    .reservation-form-container{
        background:transparent;
    }

    .reservation-form-container form{
        width:100%;
    }

    @media(max-width:768px){

        .reservation-create-header{
            flex-direction:column;
            align-items:flex-start;
        }

        .reservation-create-header .back-btn{
            width:100%;
            justify-content:center;
        }
    }
</style>
@endpush


@section('content')

<div class="reservation-create-wrapper">

    <div class="reservation-create-header">

        <div class="title">
            <i class="ri-calendar-add-line"></i>
            ایجاد رزرو جدید
        </div>

        <a href="{{ route('admin.reservations.index') }}" class="btn btn-outline-secondary back-btn">
            <i class="ri-arrow-right-line"></i>
            بازگشت به لیست
        </a>

    </div>


    <div class="reservation-form-container">

        <form method="post" action="{{ route('admin.reservations.store') }}">

            @if($reservationRequest ?? null)
                <input type="hidden" name="reservation_request_id" value="{{ $reservationRequest->id }}">
            @endif

            @include('admin.reservations._form', [
                'mode' => 'create'
            ])

        </form>

    </div>

</div>

@endsection
