@extends('layouts.admin')

@section('title', 'ایجاد تایم')

@push('styles')
<style>
    .slot-create-wrapper{
        max-width:1200px;
        margin:0 auto;
    }

    .slot-create-header{
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

    .slot-create-header .title{
        display:flex;
        align-items:center;
        gap:.6rem;
        font-size:1.1rem;
        font-weight:700;
        color:var(--ink-800);
    }

    .slot-create-header .title i{
        color:var(--brand-600);
        font-size:1.35rem;
    }

    .slot-create-header .back-btn{
        display:flex;
        align-items:center;
        gap:.4rem;
    }

    .slot-form-container{
        background:transparent;
    }

    .slot-form-container form{
        width:100%;
    }

    @media(max-width:768px){

        .slot-create-header{
            flex-direction:column;
            align-items:flex-start;
        }

        .slot-create-header .back-btn{
            width:100%;
            justify-content:center;
        }
    }
</style>
@endpush


@section('content')

<div class="slot-create-wrapper">

    <div class="slot-create-header">

        <div class="title">
            <i class="ri-add-circle-line"></i>
            ایجاد تایم جدید مشاور
        </div>

        <a href="{{ route('admin.slots.index') }}" class="btn btn-outline-secondary back-btn">
            <i class="ri-arrow-right-line"></i>
            بازگشت به لیست
        </a>

    </div>


    <div class="slot-form-container">

        <form method="post" action="{{ route('admin.slots.store') }}">

            @include('admin.slots._form', [
                'mode' => 'create'
            ])

        </form>

    </div>

</div>

@endsection