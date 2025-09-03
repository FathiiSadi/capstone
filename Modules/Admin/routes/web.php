<?php

use Illuminate\Support\Facades\Route;
use Modules\Admin\Http\Controllers\AdminController;
use Modules\Admin\Http\Controllers\TableController;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
});

// Route::middleware(['auth', 'admin'])->group(function () {
//     Route::get('/', [AdminController::class, 'index'])->name('index');
// });

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('tables', [TableController::class, 'index'])->name('tables');
});
