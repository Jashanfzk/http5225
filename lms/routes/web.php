<?php

use App\Models\Student;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\ProfessorController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('students.index');
});

// Student routes
Route::resource('students', StudentController::class);
Route::get('/students/trash/{id}', [StudentController::class, 'trash'])->name('students.trash');
Route::get('/students/trashed/', [StudentController::class, 'trashed'])->name('students.trashed');
Route::get('/students/restore/{id}', [StudentController::class, 'restore'])->name('students.restore');
Route::get('/students/force-delete/{id}', [StudentController::class, 'forceDelete'])->name('students.forceDelete');

// Course routes
Route::resource('courses', CourseController::class);
Route::get('/courses/trash/{id}', [CourseController::class, 'trash'])->name('courses.trash');
Route::get('/courses/trashed/', [CourseController::class, 'trashed'])->name('courses.trashed');
Route::get('/courses/restore/{id}', [CourseController::class, 'restore'])->name('courses.restore');
Route::get('/courses/force-delete/{id}', [CourseController::class, 'forceDelete'])->name('courses.forceDelete');

// Professor routes
Route::resource('professors', ProfessorController::class);