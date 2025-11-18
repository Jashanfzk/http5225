@extends('layouts.app')

@section('title', 'Profile')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1 class="display-5 fw-bold">My Profile</h1>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body text-center">
                <i class="bi bi-person-circle display-1 text-primary"></i>
                <h4 class="mt-3">{{ $user->name }}</h4>
                <p class="text-muted">{{ $user->email }}</p>
                @if($user->school)
                    <p><strong>School:</strong> {{ $user->school->school_name }}</p>
                    <p><strong>School Board:</strong> {{ $user->school->school_board }}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-8">
        @if($user->is_owner && $user->school)
        <div class="card shadow-sm mb-4 border-primary">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-people"></i> School Management</h5>
            </div>
            <div class="card-body">
                <p class="mb-3">As the school owner, you can manage teachers for your school.</p>
                <a href="{{ route('teachers.index') }}" class="btn btn-primary">
                    <i class="bi bi-people"></i> Manage Teachers
                </a>
                <small class="d-block text-muted mt-2">
                    <i class="bi bi-info-circle"></i> 
                    {{ $user->school->users()->where('is_owner', false)->count() }} teacher(s) registered
                </small>
            </div>
        </div>
        @endif

        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5>Memberships</h5>
            </div>
            <div class="card-body">
                @if($memberships->count() > 0)
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Division</th>
                                    <th>Status</th>
                                    <th>Expires At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($memberships as $membership)
                                <tr>
                                    <td>{{ ucfirst(str_replace('_', ' ', $membership->membership_type)) }}</td>
                                    <td>
                                        <span class="badge bg-{{ $membership->status === 'active' ? 'success' : 'danger' }}">
                                            {{ ucfirst($membership->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $membership->expires_at ? $membership->expires_at->format('Y-m-d') : 'Never' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted">No memberships found.</p>
                @endif
                <a href="{{ route('membership.index') }}" class="btn btn-primary">Manage Memberships</a>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5>Favorites</h5>
            </div>
            <div class="card-body">
                @if($favorites->count() > 0)
                    <div class="list-group">
                        @foreach($favorites as $favorite)
                        <a href="{{ route('activities.show', [$favorite->activity->division->slug, $favorite->activity->slug]) }}" class="list-group-item list-group-item-action">
                            {{ $favorite->activity->title }}
                        </a>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted">No favorites yet.</p>
                @endif
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header">
                <h5>Gallery of Growth Posts</h5>
            </div>
            <div class="card-body">
                @if($galleryPosts->count() > 0)
                    <div class="row">
                        @foreach($galleryPosts as $post)
                        <div class="col-md-6 mb-3">
                            <div class="card">
                                @if($post->image)
                                    <img src="{{ asset('storage/' . $post->image) }}" class="card-img-top" alt="{{ $post->title }}">
                                @endif
                                <div class="card-body">
                                    <h6 class="card-title">{{ $post->title }}</h6>
                                    <p class="card-text">{{ Str::limit($post->content, 100) }}</p>
                                    <small class="text-muted">{{ $post->created_at->format('M d, Y') }}</small>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted">No gallery posts yet.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

