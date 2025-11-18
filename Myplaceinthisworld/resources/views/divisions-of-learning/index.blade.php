@extends('layouts.app')

@section('title', 'Divisions of Learning')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1 class="display-5 fw-bold">Divisions of Learning</h1>
        <p class="lead">Select a division to access learning resources. Unlock additional divisions by purchasing memberships.</p>
    </div>
</div>

<div class="row g-4">
    @foreach($divisionsWithAccess as $division)
    <div class="col-md-4">
        <div class="card h-100 shadow-sm division-card {{ $division->has_access ? '' : 'opacity-75' }}" 
             data-slug="{{ $division->slug }}"
             style="cursor: {{ $division->has_access ? 'pointer' : 'not-allowed' }}; transition: transform 0.2s;">
            <div class="card-body text-center">
                @if($division->icon)
                    <i class="{{ $division->icon }} display-1 text-primary mb-3"></i>
                @else
                    <i class="bi bi-book display-1 text-primary mb-3"></i>
                @endif
                
                <h3 class="card-title">{{ $division->name }}</h3>
                <p class="card-text">{{ $division->description }}</p>
                
                @if($division->has_access)
                    <a href="{{ route('divisions.show', $division->slug) }}" class="btn btn-primary">
                        Access Division <i class="bi bi-arrow-right"></i>
                    </a>
                @else
                    <div class="text-center">
                        <i class="bi bi-lock-fill text-warning display-4"></i>
                        <p class="text-muted mt-2">Upgrade to access</p>
                        <a href="{{ route('membership.index') }}" class="btn btn-outline-primary">
                            View Membership Options
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>

@push('scripts')
<script>
$(document).ready(function() {
    $('.division-card').hover(
        function() {
            if ($(this).data('slug') && $(this).find('.btn-primary').length) {
                $(this).css('transform', 'translateY(-5px)');
            }
        },
        function() {
            $(this).css('transform', 'translateY(0)');
        }
    );
});
</script>
@endpush
@endsection

