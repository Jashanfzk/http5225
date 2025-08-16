<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use Illuminate\Support\Facades\Session;
use App\Models\Course;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $students = Student::all();
        return view('students.index', compact('students'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $courses = Course::all();
        return view('students.create', compact('courses'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStudentRequest $request)
    {
        try {
            $student = Student::create($request->validated());
            
            // Attach courses if selected
            if ($request->has('courses') && is_array($request->courses)) {
                $student->courses()->attach($request->courses);
            }
            
            Session::flash('success', 'Student added successfully');
            return redirect()->route('students.index');
        } catch (\Exception $e) {
            Session::flash('error', 'Failed to create student. Please try again.');
            return redirect()->back()->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Student $student)
    {
        return view('students.show', compact('student'));
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
        try {
            $student->update($request->validated());
            Session::flash('success', 'Student updated successfully');
            return redirect()->route('students.index');
        } catch (\Exception $e) {
            Session::flash('error', 'Failed to update student. Please try again.');
            return redirect()->back()->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Student $student)
    {
        try {
            $student->delete();
            Session::flash('success', 'Student deleted successfully');
            return redirect()->route('students.index');
        } catch (\Exception $e) {
            Session::flash('error', 'Failed to delete student. Please try again.');
            return redirect()->back();
        }
    }

    /**
     * Soft delete a student
     */
    public function trash($id)
    {
        try {
            $student = Student::findOrFail($id);
            $student->delete();
            Session::flash('success', 'Student trashed successfully');
            return redirect()->route('students.index');
        } catch (\Exception $e) {
            Session::flash('error', 'Failed to trash student. Please try again.');
            return redirect()->back();
        }
    }

    /**
     * Permanently delete a student
     */
    public function forceDelete($id)
    {
        try {
            $student = Student::withTrashed()->where('id', $id)->first();
            if ($student) {
                $student->forceDelete();
                Session::flash('success', 'Student permanently deleted');
            } else {
                Session::flash('error', 'Student not found');
            }
            return redirect()->route('students.index');
        } catch (\Exception $e) {
            Session::flash('error', 'Failed to delete student. Please try again.');
            return redirect()->back();
        }
    }

    /**
     * Restore a soft deleted student
     */
    public function restore($id)
    {
        try {
            $student = Student::withTrashed()->where('id', $id)->first();
            if ($student) {
                $student->restore();
                Session::flash('success', 'Student restored successfully');
            } else {
                Session::flash('error', 'Student not found');
            }
            return redirect()->route('students.index');
        } catch (\Exception $e) {
            Session::flash('error', 'Failed to restore student. Please try again.');
            return redirect()->back();
        }
    }

    /**
     * Show trashed students
     */
    public function trashed()
    {
        $students = Student::onlyTrashed()->get();
        return view('students.trashed', compact('students'));
    }
}