@extends('layouts.public')

@section('title', 'دسترسی به رزرو')

@section('content')
    <div class="card mt-4">
        <div class="card-body">
            <div class="alert alert-warning mb-0">
                <i class="ri-lock-line"></i>
                <div>{{ $message }}</div>
            </div>
        </div>
    </div>
@endsection
