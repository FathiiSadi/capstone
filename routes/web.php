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

Route::get('/', [App\Http\Controllers\DashboardController::class, 'index'])->name('home');

Route::get('/auth/login', function () {
    return view('/auth/login');
});

// Protected routes - require authentication and instructor/admin role
Route::middleware(['instructor.access'])->group(function () {

    Route::get('/preferences', [App\Http\Controllers\InstructorPreferenceController::class, 'index'])->name('instructor.preferences');
    Route::post('/preferences', [App\Http\Controllers\InstructorPreferenceController::class, 'store'])->name('instructor.preferences.store');
    Route::get('/preferences/{semester}', [App\Http\Controllers\InstructorPreferenceController::class, 'show'])->name('instructor.preferences.show');
    Route::put('/preferences/{semester}', [App\Http\Controllers\InstructorPreferenceController::class, 'update'])->name('instructor.preferences.update');
    Route::delete('/preferences/{semester}', [App\Http\Controllers\InstructorPreferenceController::class, 'destroy'])->name('instructor.preferences.destroy');

    Route::get('/schedule', [App\Http\Controllers\ScheduleController::class, 'index'])->name('schedule.index');

    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'show'])->name('instructor.profile');
    Route::post('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('instructor.profile.update');
});

