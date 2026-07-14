@extends('layouts.admin')

@section('title', 'ویرایش نقش')
@push('styles')
    <style>
        .role-edit-wrapper {
            max-width: 1200px;
            margin: 0 auto;
        }

        .role-edit-header {
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

        .role-edit-header .title {
            display: flex;
            align-items: center;
            gap: .6rem;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--ink-800);
        }

        .role-edit-header .title i {
            color: var(--brand-600);
            font-size: 1.35rem;
        }

        .role-edit-header .back-btn {
            display: flex;
            align-items: center;
            gap: .4rem;
        }

        .role-form-container {
            background: transparent;
        }

        .role-form-container form {
            width: 100%;
        }

        @media(max-width:768px) {
            .role-edit-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .role-edit-header .back-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
@endpush
@section('content')

    <div class="role-edit-wrapper">

        <div class="role-edit-header">

            <div class="title">
                <i class="ri-shield-user-line"></i>
                ویرایش نقش
            </div>

            <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary back-btn">
                <i class="ri-arrow-right-line"></i>
                بازگشت به لیست
            </a>

        </div>

        <div class="role-form-container">

            <form method="post" action="{{ route('admin.roles.update', $role) }}">
                @method('put')

                @include('admin.roles._form')

            </form>

        </div>

    </div>

@endsection