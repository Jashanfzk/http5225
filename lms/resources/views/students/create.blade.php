@extends('admin');
@section('content')
<h1>Add a Student</h1>

<form action="{{ route('students.store') }}" method="POST">
    @if ($errors->any())
        <div>
            <ul>
                @foreach ($errors->all() as $error)
                    <li> {{$error}}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{ csrf_field() }}
    <input type="text" name="fname" placeholder="First Name">

    <input type="text" name="lname" placeholder="Last Name">

    <input type="email" name="email" placeholder="Email">
    @error('email')
        <span>
            <strong>{{ $message }}</strong>
        </span>
    @enderror
    <input type="submit" name="Add" value="Add">
</form>