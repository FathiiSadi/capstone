<?php

use App\Http\Controllers\RegisteredUserController;
use App\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Route;

//Route::get('/', function () {
//    return view('home');
//});

Route::middleware('guest')->group(function () {
    Route::get('main/auth/register', [RegisteredUserController::class, 'create']);
    Route::post('main/auth/register', [RegisteredUserController::class, 'store']);

    Route::get('main/auth/login', [SessionController::class, 'create']);
    Route::post('main/auth/login', [SessionController::class, 'store']);
});

Route::get('/logout', [SessionController::class, 'destroy'])->name('logout');

Route::get('/main', function () {
    return view('/main/index');
});

Route::get('/main/auth/login', function () {
    return view('/main/auth/login');
});

Route::get('/main/preference', function () {
    return view('/main/preference');
});

Route::get('/main/schedule', function () {
    return view('/main/schedule');
});

Route::get('/main/course', function () {
    return view('/main/course');
});
