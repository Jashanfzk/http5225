@extends('layouts.app')

@section('title', 'Teacher Details')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('profile.index') }}">Profile</a></li>
                <li class="breadcrumb-item"><a href="{{ route('teachers.index') }}">Teachers</a></li>
                <li class="breadcrumb-item active">{{ $teacher->name }}</li>
            </ol>
        </nav>
        <h1 class="display-5 fw-bold">Teacher Details</h1>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="bi bi-person-circle"></i> Information</h4>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-3">Name:</dt>
                    <dd class="col-sm-9">{{ $teacher->name }}</dd>

                    <dt class="col-sm-3">Email:</dt>
                    <dd class="col-sm-9">{{ $teacher->email }}</dd>

                    <dt class="col-sm-3">Role:</dt>
                    <dd class="col-sm-9">
                        <span class="badge bg-info">
                            {{ $teacher->roles->first()->name ?? 'Educator' }}
                        </span>
                    </dd>

                    <dt class="col-sm-3">Registered:</dt>
                    <dd class="col-sm-9">{{ $teacher->created_at->format('F d, Y \a\t g:i A') }}</dd>

                    <dt class="col-sm-3">Last Login:</dt>
                    <dd class="col-sm-9">{{ $teacher->updated_at->format('F d, Y \a\t g:i A') }}</dd>
                </dl>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">Activity Statistics</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-4">
                        <div class="p-3">
                            <h3 class="text-primary">{{ $teacher->progress()->count() }}</h3>
                            <p class="text-muted mb-0">Activities Tracked</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3">
                            <h3 class="text-success">{{ $teacher->progress()->where('is_favorite', true)->count() }}</h3>
                            <p class="text-muted mb-0">Favorites</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3">
                            <h3 class="text-info">{{ $teacher->galleryPosts()->count() }}</h3>
                            <p class="text-muted mb-0">Gallery Posts</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('teachers.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Teachers
                    </a>
                    <a href="{{ route('teachers.edit', $teacher) }}" class="btn btn-primary">
                        <i class="bi bi-pencil-square"></i> Edit Teacher
                    </a>
                    <form method="POST" action="{{ route('teachers.destroy', $teacher) }}" onsubmit="return confirm('Are you sure you want to remove this teacher? This action cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="bi bi-trash"></i> Remove Teacher
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

