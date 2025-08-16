@extends('admin')
@section('content')
<div class="row">
    <div class="col">
        <h1 class="display-4">Student Details</h1>
    </div>
    <div class="col text-end">
        <a href="{{ route('students.edit', $student) }}" class="btn btn-warning">Edit Student</a>
        <a href="{{ route('students.index') }}" class="btn btn-secondary">Back to Students</a>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ $student->fname }} {{ $student->lname }}</h5>
            </div>
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted">Student ID: {{ $student->id }}</h6>
                <p class="card-text"><strong>Email:</strong> {{ $student->email }}</p>
                <p class="card-text"><small class="text-muted">Created: {{ $student->created_at->format('F d, Y \a\t g:i A') }}</small></p>
                <p class="card-text"><small class="text-muted">Last updated: {{ $student->updated_at->format('F d, Y \a\t g:i A') }}</small></p>
            </div>
        </div>
    </div>
</div>
@endsection