@extends('layouts.admin')

@section('title', 'کاربر جدید')
@push('styles')
<style>
 .user-create-wrapper{
    max-width:1200px;
    margin:0 auto;
}

.user-create-header{
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

.user-create-header .title{
    display:flex;
    align-items:center;
    gap:.6rem;
    font-size:1.1rem;
    font-weight:700;
    color:var(--ink-800);
}

.user-create-header .title i{
    color:var(--brand-600);
    font-size:1.35rem;
}

.user-create-header .back-btn{
    display:flex;
    align-items:center;
    gap:.4rem;
}

.user-form-container{
    background:transparent;
}

.user-form-container form{
    width:100%;
}

@media(max-width:768px){
    .user-create-header{
        flex-direction:column;
        align-items:flex-start;
    }

    .user-create-header .back-btn{
        width:100%;
        justify-content:center;
    }
}
</style>
@endpush
@section('content')

<div class="user-create-wrapper">

    <div class="user-create-header">

        <div class="title">
            <i class="ri-user-add-line"></i>
            ایجاد کاربر جدید
        </div>

        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary back-btn">
            <i class="ri-arrow-right-line"></i>
            بازگشت به لیست
        </a>

    </div>

    <div class="user-form-container">

        <form method="post" action="{{ route('admin.users.store') }}">

            @include('admin.users._form', [
                'mode' => 'create'
            ])

        </form>

    </div>

</div>

@endsection