<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])
    ->name('login')
    ->middleware('throttle:5,1');

Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('users.index');
        Route::post('/', [UserController::class, 'store'])->name('users.store');
    });
});
