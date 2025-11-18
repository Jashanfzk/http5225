@extends('layouts.app')

@section('title', 'Manage Teachers')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h1 class="display-5 fw-bold">Manage Teachers</h1>
            <p class="lead">Register and manage teachers for your school</p>
        </div>
        <a href="{{ route('teachers.create') }}" class="btn btn-primary">
            <i class="bi bi-person-plus"></i> Add New Teacher
        </a>
    </div>
</div>

@if($teachers->count() > 0)
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($teachers as $teacher)
                            <tr>
                                <td>
                                    <i class="bi bi-person-circle text-primary"></i>
                                    {{ $teacher->name }}
                                </td>
                                <td>{{ $teacher->email }}</td>
                                <td>
                                    <span class="badge bg-info">
                                        {{ $teacher->roles->first()->name ?? 'Educator' }}
                                    </span>
                                </td>
                                <td>{{ $teacher->created_at->format('M d, Y') }}</td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('teachers.show', $teacher) }}" class="btn btn-sm btn-outline-primary" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('teachers.edit', $teacher) }}" class="btn btn-sm btn-outline-secondary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST" action="{{ route('teachers.destroy', $teacher) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to remove this teacher?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@else
<div class="row">
    <div class="col-12">
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> No teachers registered yet. 
            <a href="{{ route('teachers.create') }}" class="alert-link">Add your first teacher</a>.
        </div>
    </div>
</div>
@endif

<div class="row mt-4">
    <div class="col-12">
        <a href="{{ route('profile.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Profile
        </a>
    </div>
</div>
@endsection

