<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\DivisionsController;
use App\Http\Controllers\DivisionController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SubAccountController;
use App\Http\Controllers\Admin\SchoolController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\MembershipController as AdminMembershipController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/about', function () {
    return view('about');
})->name('about');

Route::get('/gallery', function () {
    return view('gallery');
})->name('gallery');

Route::get('/support', function () {
    return view('support');
})->name('support');

// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
    
    // Google OAuth routes
    Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    
    // Divisions of Learning - PRIMARY LOCKED FEATURE
    Route::get('/divisions-of-learning', [DivisionsController::class, 'index'])->name('divisions-of-learning.index');
    
    // Individual division pages (protected by membership middleware)
    Route::get('/divisions/{slug}', [DivisionController::class, 'show'])
        ->middleware('membership')
        ->name('divisions.show');
    
    // Activities
    Route::get('/divisions/{divisionSlug}/activities/{activitySlug}', [ActivityController::class, 'show'])
        ->name('activities.show');
    
    // Membership
    Route::get('/membership', [MembershipController::class, 'index'])->name('membership.index');
    
    // Payment routes
    Route::post('/payment/checkout', [PaymentController::class, 'checkout'])->name('payment.checkout');
    Route::get('/payment/success', [PaymentController::class, 'success'])->name('payment.success');
    Route::get('/payment/cancel', [PaymentController::class, 'cancel'])->name('payment.cancel');
    Route::get('/payment/customer-portal', [PaymentController::class, 'customerPortal'])->name('payment.customer-portal');
    
    // Profile
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    
    // Sub-Account Management
    Route::get('/sub-accounts/select', [SubAccountController::class, 'select'])->name('sub-accounts.select');
    Route::post('/sub-accounts/switch', [SubAccountController::class, 'switch'])->name('sub-accounts.switch');
    
    // Teacher Management (only for school owners)
    Route::resource('teachers', TeacherController::class);
});

// Admin routes (protected by admin middleware)
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Schools management
    Route::resource('schools', SchoolController::class);
    
    // Users management
    Route::resource('users', UserController::class);
    Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
    
    // Memberships management
    Route::get('/memberships', [AdminMembershipController::class, 'index'])->name('memberships.index');
    Route::get('/memberships/{membership}/edit', [AdminMembershipController::class, 'edit'])->name('memberships.edit');
    Route::put('/memberships/{membership}', [AdminMembershipController::class, 'update'])->name('memberships.update');
    Route::post('/memberships/{membership}/grant-trial', [AdminMembershipController::class, 'grantTrial'])->name('memberships.grant-trial');
    
    // Other admin pages (placeholders)
    Route::get('/content', function () {
        return view('admin.content');
    })->name('content');
    Route::get('/settings', function () {
        return view('admin.settings');
    })->name('settings');
    Route::get('/broken-links', function () {
        return view('admin.broken-links');
    })->name('broken-links');
    Route::get('/activity-log', function () {
        return view('admin.activity-log');
    })->name('activity-log');
});

// Stripe webhook (no auth required, verified by signature)
Route::post('/webhook/stripe', [PaymentController::class, 'webhook'])->name('webhook.stripe');
