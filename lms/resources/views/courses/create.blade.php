@extends('admin')
@section('content')
<div class="row">
    <div class="col">
        <h1 class="display-4">Add a Course</h1>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <form action="{{ route('courses.store') }}" method="POST">
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
                <label for="name" class="form-label">Course Name</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                    placeholder="Course Name" value="{{ old('name') }}">
                @error('name')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                    placeholder="Course Description" rows="4">{{ old('description') }}</textarea>
                @error('description')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="mb-3">
                <button type="submit" class="btn btn-primary">Create Course</button>
                <a href="{{ route('courses.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
