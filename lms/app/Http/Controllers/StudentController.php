<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use Illuminate\Support\Facades\Session;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('students.index',[
          'students' => student::all()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('students.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStudentRequest $request)
    {
       student::create($request -> validated());
       return redirect() -> route('students.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Student $student)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Student $student)
    {
        return view('students.edit', compact('student'));
    }

    

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStudentRequest $request, Student $student)
    {
        $student->update($request->validated());
        Session::flash('success', 'Student updated successfully');
        return redirect()->route('students.index');
    }
    /**
     * Remove the specified resource from storage.
     */
         public function trash($id)
    {
        Student::Destroy($id);
        Session::Flash('success', 'Student trashed successfully');
        return redirect() -> route('students.index');
    }

    public function destroy($id)
    {
        $student = Student::withTrashed() -> where('id', $id) -> first();
        $student -> forceDelete();
        Session::Flash('success', 'Student deleted successfully');
        return redirect() -> route('students.index');
    }

    public function restore($id)
    {
        $student = Student::withTrashed() -> where('id', $id) -> first();
        $student -> restore();
        Session::Flash('success', 'Student restored successfully');
        return redirect() -> route('students.trashed');
    }


}