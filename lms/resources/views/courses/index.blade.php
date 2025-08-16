@extends('admin')
@section('content')
<div class="row">
    <div class="col">
        <h1 class="display-4">Courses</h1>
    </div>
    <div class="col text-end">
        <a href="{{ route('courses.create') }}" class="btn btn-primary">Add New Course</a>
    </div>
</div>

<div class="row mt-4">
    <div class="col">
        @if($courses->count() > 0)
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($courses as $course)
                        <tr>
                            <td>{{ $course->id }}</td>
                            <td>{{ $course->name }}</td>
                            <td>{{ Str::limit($course->description, 100) }}</td>
                            <td>{{ $course->created_at->format('M d, Y') }}</td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('courses.show', $course->id) }}" class="btn btn-sm btn-info">View</a>
                                    <a href="{{ route('courses.edit', $course) }}" class="btn btn-sm btn-warning">Edit</a>
                                    <form action="{{ route('courses.destroy', $course) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this course?')">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="alert alert-info">
                <h4 class="alert-heading">No Courses Found!</h4>
                <p>There are no courses available. <a href="{{ route('courses.create') }}" class="alert-link">Create your first course</a>.</p>
            </div>
        @endif
    </div>
</div>
@endsection
