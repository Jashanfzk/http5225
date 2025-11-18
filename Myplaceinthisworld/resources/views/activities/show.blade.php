@extends('layouts.app')

@section('title', $activity->title)

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('divisions-of-learning.index') }}">Divisions of Learning</a></li>
                <li class="breadcrumb-item"><a href="{{ route('divisions.show', $activity->division->slug) }}">{{ $activity->division->name }}</a></li>
                <li class="breadcrumb-item active">{{ $activity->title }}</li>
            </ol>
        </nav>
        <h1 class="display-5 fw-bold">{{ $activity->title }}</h1>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body">
                @if($activity->description)
                    <p class="lead">{{ $activity->description }}</p>
                @endif

                <div class="activity-content mt-4">
                    @if($activity->content)
                        {!! $activity->content !!}
                    @else
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Content will be loaded from Google Docs integration.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5>Activity Actions</h5>
                <form method="POST" action="#" class="mb-3">
                    @csrf
                    <button type="submit" class="btn btn-{{ $isFavorite ? 'warning' : 'outline-warning' }} w-100">
                        <i class="bi bi-star{{ $isFavorite ? '-fill' : '' }}"></i> 
                        {{ $isFavorite ? 'Remove from Favorites' : 'Add to Favorites' }}
                    </button>
                </form>

                <form method="POST" action="#">
                    @csrf
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-check-circle"></i> Mark as Completed
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

