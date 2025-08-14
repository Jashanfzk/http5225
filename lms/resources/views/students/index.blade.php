@extends('admin')
@section('content')
   <h1>All Students</h1>
   @foreach ($students as $student)
   {{$student -> fname}} 
   {{$student -> lname }} |
   {{$student -> email}}  | <a href="{{route('students.edit', $student -> id) }}">Edit</a>
    <a href="{{route('students.trash', $student -> id) }}">Delete</a>
   <br>
   @endforeach
@endsection