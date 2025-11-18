@extends('layouts.app')

@section('title', 'Register Teacher')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('profile.index') }}">Profile</a></li>
                <li class="breadcrumb-item"><a href="{{ route('teachers.index') }}">Teachers</a></li>
                <li class="breadcrumb-item active">Register Teacher</li>
            </ol>
        </nav>
        <h1 class="display-5 fw-bold">Register New Teacher</h1>
        <p class="lead">Add a new teacher to your school</p>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="bi bi-person-plus"></i> Teacher Information</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('teachers.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name *</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name') }}" required autofocus>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address *</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" 
                               id="email" name="email" value="{{ old('email') }}" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">The teacher will use this email to log in.</small>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password *</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" 
                               id="password" name="password" required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Confirm Password *</label>
                        <input type="password" class="form-control" 
                               id="password_confirmation" name="password_confirmation" required>
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> The teacher will have access to all divisions that your school has purchased. 
                        They will be able to view activities, track progress, and create Gallery of Growth posts.
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="{{ route('teachers.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Register Teacher
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

