@extends('admin')
@section('content')
<div class="row">
    <div class="col">
        <h1 class="display-4">Edit Student</h1>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <form action="{{ route('students.update', $student) }}" method="POST">
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
            @method('PUT')
            
            <div class="mb-3">
                <label for="fname" class="form-label">First Name</label>
                <input type="text" name="fname" class="form-control @error('fname') is-invalid @enderror"
                    placeholder="First Name" value="{{ old('fname', $student->fname) }}">
                @error('fname')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="mb-3">
                <label for="lname" class="form-label">Last Name</label>
                <input type="text" name="lname" class="form-control @error('lname') is-invalid @enderror"
                    placeholder="Last Name" value="{{ old('lname', $student->lname) }}">
                @error('lname')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                    placeholder="Email" value="{{ old('email', $student->email) }}">
                @error('email')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="mb-3">
                <button type="submit" class="btn btn-primary">Update Student</button>
                <a href="{{ route('students.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection