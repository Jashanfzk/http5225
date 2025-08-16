<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use Illuminate\Support\Facades\Session;

class CourseController extends Controller
{
    public function index()
    {
        $courses = Course::all();
        return view('courses.index', compact('courses'));
    }

    public function create()
    {
        return view('courses.create');
    }

    public function store(StoreCourseRequest $request)
    {
        try {
            Course::create($request->validated());
            Session::flash('success', 'Course created successfully');
            return redirect()->route('courses.index');
        } catch (\Exception $e) {
            Session::flash('error', 'Failed to create course. Please try again.');
            return redirect()->back()->withInput();
        }
    }

    public function show(Course $course)
    {
        try {
            $students = $course->students;
            return view('courses.show', compact('course', 'students'));
        } catch (\Exception $e) {
            Session::flash('error', 'Course not found.');
            return redirect()->route('courses.index');
        }
    }

    public function edit(Course $course)
    {
        return view('courses.edit', compact('course'));
    }

    public function update(UpdateCourseRequest $request, Course $course)
    {
        try {
            $course->update($request->validated());
            Session::flash('success', 'Course updated successfully');
            return redirect()->route('courses.index');
        } catch (\Exception $e) {
            Session::flash('error', 'Failed to update course. Please try again.');
            return redirect()->back()->withInput();
        }
    }

    public function destroy(Course $course)
    {
        try {
            $course->delete();
            Session::flash('success', 'Course deleted successfully');
            return redirect()->route('courses.index');
        } catch (\Exception $e) {
            Session::flash('error', 'Failed to delete course. Please try again.');
            return redirect()->back();
        }
    }

    public function trash($id)
    {
        try {
            $course = Course::findOrFail($id);
            $course->delete();
            Session::flash('success', 'Course trashed successfully');
            return redirect()->route('courses.index');
        } catch (\Exception $e) {
            Session::flash('error', 'Failed to trash course. Please try again.');
            return redirect()->back();
        }
    }

    public function forceDelete($id)
    {
        try {
            $course = Course::withTrashed()->where('id', $id)->first();
            if ($course) {
                $course->forceDelete();
                Session::flash('success', 'Course permanently deleted');
            } else {
                Session::flash('error', 'Course not found');
            }
            return redirect()->route('courses.index');
        } catch (\Exception $e) {
            Session::flash('error', 'Failed to delete course. Please try again.');
            return redirect()->back();
        }
    }

    public function restore($id)
    {
        try {
            $course = Course::withTrashed()->where('id', $id)->first();
            if ($course) {
                $course->restore();
                Session::flash('success', 'Course restored successfully');
            } else {
                Session::flash('error', 'Course not found');
            }
            return redirect()->route('courses.index');
        } catch (\Exception $e) {
            Session::flash('error', 'Failed to restore course. Please try again.');
            return redirect()->back();
        }
    }

    public function trashed()
    {
        $courses = Course::onlyTrashed()->get();
        return view('courses.trashed', compact('courses'));
    }
}
