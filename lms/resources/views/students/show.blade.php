@extends('layouts/admin')
@section('content')
<h1>
Student Profile
</h1>
{{ $student -> fname }} 
{{ $student -> lname }} |
 {{$student -> email}}  | 
<a href="{{ route('students.edit', $student -> id ) }}" class="card-link">Edit</a>
<a href="{{ route('students.trash', $student -> id )}}" class="card-link">Delete</a>
@endsection