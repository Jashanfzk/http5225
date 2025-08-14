<?php

use app\Models\Student;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::resource('students', StudentController::class);
Route::get(
'students/trash/{id}',
[StudentController::class, 'trash']
)->name('students.trash');
Route::get(
'students/trashed/',
[StudentController::class, 'trashed']
)->name('students.trashed');
Route::get(
'students/restore/{id}',
[StudentController::class, 'trash']
)->name('students.restore');
Route::resource('students', StudentController::class);

Route::get('/students/{id}/trash', [StudentController::class, 'trash'])->name('students.trash');