@extends('admin')
@section('content')
<div class="row">
    <div class="col">
        <h1 class="display-4">Course Details</h1>
    </div>
    <div class="col text-end">
        <a href="{{ route('courses.edit', $course) }}" class="btn btn-warning">Edit Course</a>
        <a href="{{ route('courses.index') }}" class="btn btn-secondary">Back to Courses</a>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ $course->name }}</h5>
            </div>
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted">Course ID: {{ $course->id }}</h6>
                <p class="card-text">{{ $course->description }}</p>
                
                @if($course->professor)
                    <p class="card-text"><strong>Professor:</strong> {{ $course->professor->name }}</p>
                @else
                    <p class="card-text"><strong>Professor:</strong> <span class="text-muted">Not assigned</span></p>
                @endif
                
                <p class="card-text"><small class="text-muted">Created: {{ $course->created_at->format('F d, Y \a\t g:i A') }}</small></p>
                <p class="card-text"><small class="text-muted">Last updated: {{ $course->updated_at->format('F d, Y \a\t g:i A') }}</small></p>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col">
        <h3>Enrolled Students</h3>
        @if($students->count() > 0)
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $student)
                        <tr>
                            <td>{{ $student->id }}</td>
                            <td>{{ $student->fname }} {{ $student->lname }}</td>
                            <td>{{ $student->email }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="alert alert-info">
                <h4 class="alert-heading">No Students Enrolled</h4>
                <p>This course currently has no enrolled students.</p>
            </div>
        @endif
    </div>
</div>
@endsection
