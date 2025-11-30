<?php

use App\Http\Controllers\RegisteredUserController;
use App\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Route;

//Route::get('/', function () {
//    return view('home');
//});

Route::middleware('guest')->group(function () {
    Route::get('auth/register', [RegisteredUserController::class, 'create']);
    Route::post('auth/register', [RegisteredUserController::class, 'store']);

    Route::get('auth/login', [SessionController::class, 'create']);
    Route::post('auth/login', [SessionController::class, 'store']);
});

Route::get('/logout', [SessionController::class, 'destroy'])->name('logout');

Route::get('/', function () {
    $user = auth()->user();
    return view('/index', compact('user'));
})->name('home');

Route::get('/auth/login', function () {
    return view('/auth/login');
});

// Protected routes - require authentication and instructor/admin role
Route::middleware(['instructor.access'])->group(function () {
    Route::get('/schedule', function () {
        return view('/schedule');
    });

    Route::get('/course', function () {
        return view('/course');
    });

    Route::get('/home', function () {
        return view('instructorHome');
    })->name('instructor.home');

    Route::get('/preferences', [App\Http\Controllers\InstructorPreferenceController::class, 'index'])->name('instructor.preferences');
    Route::post('/preferences', [App\Http\Controllers\InstructorPreferenceController::class, 'store'])->name('instructor.preferences.store');
    Route::get('/preferences/{semester}', [App\Http\Controllers\InstructorPreferenceController::class, 'show'])->name('instructor.preferences.show');
    Route::put('/preferences/{semester}', [App\Http\Controllers\InstructorPreferenceController::class, 'update'])->name('instructor.preferences.update');
    Route::delete('/preferences/{semester}', [App\Http\Controllers\InstructorPreferenceController::class, 'destroy'])->name('instructor.preferences.destroy');

    Route::get('/profile', function () {
        $user = auth()->user()->load(['instructor', 'department']);
        return view('profile', compact('user'));
    })->name('instructor.profile');
});

