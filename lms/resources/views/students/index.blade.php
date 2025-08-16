@extends('admin')
@section('content')
<div class="row">
    <div class="col">
        <h1 class="display-4">Students</h1>
    </div>
    <div class="col text-end">
        <a href="{{ route('students.create') }}" class="btn btn-primary">Add New Student</a>
    </div>
</div>

<div class="row mt-4">
    <div class="col">
        @if($students->count() > 0)
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Email</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $student)
                        <tr>
                            <td>{{ $student->id }}</td>
                            <td>{{ $student->fname }}</td>
                            <td>{{ $student->lname }}</td>
                            <td>{{ $student->email }}</td>
                            <td>{{ $student->created_at->format('M d, Y') }}</td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('students.show', $student) }}" class="btn btn-sm btn-info">View</a>
                                    <a href="{{ route('students.edit', $student) }}" class="btn btn-sm btn-warning">Edit</a>
                                    <form action="{{ route('students.destroy', $student) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this student?')">Delete</button>
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
                <h4 class="alert-heading">No Students Found!</h4>
                <p>There are no students available. <a href="{{ route('students.create') }}" class="alert-link">Add your first student</a>.</p>
            </div>
        @endif
    </div>
</div>
@endsection