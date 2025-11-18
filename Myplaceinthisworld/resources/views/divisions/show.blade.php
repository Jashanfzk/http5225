@extends('layouts.app')

@section('title', $division->name)

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('divisions-of-learning.index') }}">Divisions of Learning</a></li>
                <li class="breadcrumb-item active">{{ $division->name }}</li>
            </ol>
        </nav>
        <h1 class="display-5 fw-bold">{{ $division->name }}</h1>
        <p class="lead">{{ $division->description }}</p>
    </div>
</div>

@if($division->activities->count() > 0)
<div class="row g-4">
    @foreach($division->activities as $activity)
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <h5 class="card-title">{{ $activity->title }}</h5>
                <p class="card-text">{{ Str::limit($activity->description, 100) }}</p>
                <a href="{{ route('activities.show', [$division->slug, $activity->slug]) }}" class="btn btn-primary">
                    View Activity <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
    @endforeach
</div>
@else
<div class="alert alert-info">
    <i class="bi bi-info-circle"></i> No activities available in this division yet.
</div>
@endif
@endsection

