<?php

use App\Http\Controllers\Api\v1\PermissionController;
use App\Http\Controllers\Api\v1\RoleController;
use App\Http\Controllers\Api\v1\SectionController;
use App\Http\Controllers\Api\v1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('/user')->middleware(['auth:sanctum', 'maintenance', 'locale'])->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::get('/get_lite', [UserController::class, 'getLite']);
    Route::get('/filter', [UserController::class, 'filter']);
    Route::get('/{id}', [UserController::class, 'show']);
    Route::post('/', [UserController::class, 'store']);
    Route::post('/{id}', [UserController::class, 'update']);
    Route::post('/update/MyPassword', [UserController::class, 'updateMyPassword']);
    Route::delete('/delete/{id}', [UserController::class, 'destroy']);
});

Route::prefix('/section')->group(function () {
    Route::get('/', [SectionController::class, 'index']);
    Route::get('/{id}', [SectionController::class, 'show']);
    Route::post('/store', [SectionController::class, 'store']);
    Route::post('/addUserSections', [SectionController::class, 'addUserSections']);
    Route::post('/addUserSection', [SectionController::class, 'addUserSection']);
    Route::post('/update/{id}', [SectionController::class, 'update']);
    Route::delete('/delete/{id}', [SectionController::class, 'destroy']);
});

Route::prefix('/role')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/', [RoleController::class, 'index']);
    Route::post('/', [RoleController::class, 'store']);
    Route::get('/{id}', [RoleController::class, 'show']);
    Route::post('/{id}', [RoleController::class, 'update']);
    Route::delete('/delete/{id}', [RoleController::class, 'destroy']);
});
Route::prefix('/permission')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/', [PermissionController::class, 'index']);
});
