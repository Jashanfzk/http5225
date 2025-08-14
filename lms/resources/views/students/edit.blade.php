@extends('admin')
@section('content')
    <h1>Update Student Profile</h1>
    <div>
        <form action="{{ route('students.update', $student->id) }}" method="POST">
            <div>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            {{ csrf_field() }}
            @method('PUT')
            <div>
                <label for="fname" class="form-label">First Name</label>
                <input type="text" class="form-control" id="fname" name="fname" aria-describedby="fname"
                    value="{{ $student->fname }}">
            </div>
            <div>
                <label for="lname" class="form-label">Last Name</label>
                <input type="text" class="form-control" id="lname" name="lname" aria-describedby="lname"
                    value="{{ $student->lname }}">
            </div>
            <div>
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" aria-describedby="email"
                    value="{{ $student->email }}">
                @error('email')
                    <span>
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            <button type="submit">Submit</button>
        </form>
    </div>
@endsection