<?php

use App\Http\Controllers\Api\v1\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware(['locale'])->group(function () {
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:auth'); // 5 requests per minute
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:auth');
});
Route::get('/me', [AuthController::class, 'me'])->middleware(['auth:sanctum', 'locale', 'maintenance']);
Route::get('/profile', [AuthController::class, 'profile'])->middleware(['auth:sanctum', 'locale', 'maintenance']);
