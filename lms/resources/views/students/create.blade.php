@extends('admin')
@section('content')
<div class="row">
    <div class="col">
        <h1 class="display-4">Add a Student Profile</h1>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <form action="{{ route('students.store') }}" method="POST">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @csrf
            <div class="mb-3">
                <label for="fname" class="form-label">First Name</label>
                <input type="text" name="fname" class="form-control @error('fname') is-invalid @enderror"
                    placeholder="First Name" value="{{ old('fname') }}">
                @error('fname')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="mb-3">
                <label for="lname" class="form-label">Last Name</label>
                <input type="text" name="lname" class="form-control @error('lname') is-invalid @enderror"
                    placeholder="Last Name" value="{{ old('lname') }}">
                @error('lname')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                    placeholder="Email" value="{{ old('email') }}">
                @error('email')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="mb-3">
                <label for="courses" class="form-label">Select Courses</label>
                <select name="courses[]" id="courses" class="form-control @error('courses') is-invalid @enderror" multiple>
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}" {{ in_array($course->id, old('courses', [])) ? 'selected' : '' }}>
                            {{ $course->name }}
                        </option>
                    @endforeach
                </select>
                <small class="form-text text-muted">Hold Ctrl (or Cmd on Mac) to select multiple courses</small>
                @error('courses')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="mb-3">
                <button type="submit" class="btn btn-primary">Add Student</button>
                <a href="{{ route('students.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection