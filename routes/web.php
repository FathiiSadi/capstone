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
    return view('/index');
});

Route::get('/auth/login', function () {
    return view('/auth/login');
});

Route::get('/preference', function () {
    return view('/preference');
});

Route::get('/schedule', function () {
    return view('/schedule');
});

Route::get('/course', function () {
    return view('/course');
});
