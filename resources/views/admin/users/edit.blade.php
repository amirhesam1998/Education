@extends('layouts.admin')

@section('title', 'ویرایش کاربر')
@push('styles')
    <style>
        .user-edit-wrapper {
            max-width: 1200px;
            margin: 0 auto;
        }

        .user-edit-header {
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

        .user-edit-header .title {
            display: flex;
            align-items: center;
            gap: .6rem;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--ink-800);
        }

        .user-edit-header .title i {
            color: var(--brand-600);
            font-size: 1.35rem;
        }

        .user-edit-header .back-btn {
            display: flex;
            align-items: center;
            gap: .4rem;
        }

        .user-form-container {
            background: transparent;
        }

        .user-form-container form {
            width: 100%;
        }

        @media(max-width:768px) {
            .user-edit-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .user-edit-header .back-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
@endpush
@section('content')

    <div class="user-edit-wrapper">

        <div class="user-edit-header">

            <div class="title">
                <i class="ri-user-settings-line"></i>
                ویرایش کاربر
            </div>

            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary back-btn">
                <i class="ri-arrow-right-line"></i>
                بازگشت به لیست
            </a>

        </div>

        <div class="user-form-container">

            <form method="post" action="{{ route('admin.users.update', $user) }}">
                @method('put')

                @include('admin.users._form', [
                    'mode' => 'edit'
                ])

            </form>

        </div>

        </div>

@endsection