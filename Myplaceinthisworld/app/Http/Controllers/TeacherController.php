<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class TeacherController extends Controller
{
    /**
     * Display a listing of teachers for the school.
     */
    public function index()
    {
        $user = Auth::user();
        
        // Only school owners can manage teachers
        if (!$user->is_owner || !$user->school) {
            return redirect()->route('profile.index')
                ->with('error', 'Only school owners can manage teachers.');
        }

        $teachers = $user->school->users()
            ->where('is_owner', false)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('teachers.index', compact('teachers'));
    }

    /**
     * Show the form for creating a new teacher.
     */
    public function create()
    {
        $user = Auth::user();
        
        if (!$user->is_owner || !$user->school) {
            return redirect()->route('profile.index')
                ->with('error', 'Only school owners can add teachers.');
        }

        return view('teachers.create');
    }

    /**
     * Store a newly created teacher.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->is_owner || !$user->school) {
            return redirect()->route('profile.index')
                ->with('error', 'Only school owners can add teachers.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Create teacher user
        $teacher = User::create([
            'school_id' => $user->school->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_owner' => false,
        ]);

        // Assign educator role
        $teacher->assignRole('educator');

        return redirect()->route('teachers.index')
            ->with('success', 'Teacher registered successfully!');
    }

    /**
     * Display the specified teacher.
     */
    public function show(User $teacher)
    {
        $user = Auth::user();
        
        if (!$user->is_owner || !$user->school) {
            return redirect()->route('profile.index')
                ->with('error', 'Only school owners can view teacher details.');
        }

        // Verify teacher belongs to the same school
        if ($teacher->school_id !== $user->school_id) {
            return redirect()->route('teachers.index')
                ->with('error', 'Teacher not found in your school.');
        }

        return view('teachers.show', compact('teacher'));
    }

    /**
     * Show the form for editing the specified teacher.
     */
    public function edit(User $teacher)
    {
        $user = Auth::user();
        
        if (!$user->is_owner || !$user->school) {
            return redirect()->route('profile.index')
                ->with('error', 'Only school owners can edit teachers.');
        }

        // Verify teacher belongs to the same school
        if ($teacher->school_id !== $user->school_id) {
            return redirect()->route('teachers.index')
                ->with('error', 'Teacher not found in your school.');
        }

        return view('teachers.edit', compact('teacher'));
    }

    /**
     * Update the specified teacher.
     */
    public function update(Request $request, User $teacher)
    {
        $user = Auth::user();
        
        if (!$user->is_owner || !$user->school) {
            return redirect()->route('profile.index')
                ->with('error', 'Only school owners can update teachers.');
        }

        // Verify teacher belongs to the same school
        if ($teacher->school_id !== $user->school_id) {
            return redirect()->route('teachers.index')
                ->with('error', 'Teacher not found in your school.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $teacher->id],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
        ]);

        $teacher->name = $validated['name'];
        $teacher->email = $validated['email'];
        
        if (!empty($validated['password'])) {
            $teacher->password = Hash::make($validated['password']);
        }

        $teacher->save();

        return redirect()->route('teachers.show', $teacher)
            ->with('success', 'Teacher information updated successfully!');
    }

    /**
     * Remove the specified teacher.
     */
    public function destroy(User $teacher)
    {
        $user = Auth::user();
        
        if (!$user->is_owner || !$user->school) {
            return redirect()->route('profile.index')
                ->with('error', 'Only school owners can remove teachers.');
        }

        // Verify teacher belongs to the same school
        if ($teacher->school_id !== $user->school_id) {
            return redirect()->route('teachers.index')
                ->with('error', 'Teacher not found in your school.');
        }

        // Prevent deleting the owner
        if ($teacher->is_owner) {
            return redirect()->route('teachers.index')
                ->with('error', 'Cannot delete school owner account.');
        }

        $teacher->delete();

        return redirect()->route('teachers.index')
            ->with('success', 'Teacher removed successfully!');
    }
}
