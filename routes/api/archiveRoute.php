<?php

use App\Http\Controllers\Api\v1\ArchiveController;
use App\Http\Controllers\Api\v1\ArchiveTypeController;
use App\Http\Controllers\Api\v1\DocumentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'maintenance', 'locale'])->prefix('/archiveSys')->group(function () {
    Route::prefix('/archive')->group(function () {
        Route::get('/', [ArchiveController::class, 'index'])->middleware('permission:show archives');
        Route::get('/test', [ArchiveController::class, 'test']);
        Route::get('/filter', [ArchiveController::class, 'filter'])->middleware('permission:show archives');
        Route::get('/{id}', [ArchiveController::class, 'show'])->middleware('permission:show archives');
        Route::post('/store', [ArchiveController::class, 'store'])->middleware('permission:add archive');
        Route::post('/update/{id}', [ArchiveController::class, 'update'])->middleware('permission:edit archive');
        Route::post('/document/store', [ArchiveController::class, 'store_document'])->middleware('permission:add archive');
        Route::get('/{archive_id}/documents', [ArchiveController::class, 'show_documents'])->middleware('permission:show archives');
        Route::delete('/delete/{id}', [ArchiveController::class, 'destroy'])->middleware('permission:delete archive');
    });

    Route::prefix('/archiveType')->group(function () {
        Route::get('/', [ArchiveTypeController::class, 'index']);
        Route::get('/{id}', [ArchiveTypeController::class, 'show']);
        Route::get('/section/{id}', [ArchiveTypeController::class, 'getBySectionId']);
        Route::get('/by/section', [ArchiveTypeController::class, 'getBySectionUser']);
        Route::post('/store', [ArchiveTypeController::class, 'store']);
        Route::post('/update/{id}', [ArchiveTypeController::class, 'update']);
        Route::delete('/delete/{id}', [ArchiveTypeController::class, 'destroy']);
    });

    Route::prefix('/document')->group(function () {
        Route::get('/', [DocumentController::class, 'index']);
        Route::get('/last', [DocumentController::class, 'last']);
        Route::get('/{id}', [DocumentController::class, 'show']);
        // Route::post('/store', [DocumentController::class, 'store']);
        Route::post('/{archive_id}/store_multi', [DocumentController::class, 'store_multi']);
        // Route::post('/update/{id}', [DocumentController::class, 'update']);
        Route::delete('/delete/{id}', [DocumentController::class, 'destroy']);
    });
});
