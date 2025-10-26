<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/test', function () {
    return 'Its Case';
});
 

Route::get('/download/backup', function (\Illuminate\Http\Request $r) {
    $r->validate([
        'disk' => ['required','string'],
        'path' => ['required','string'],
    ]);
    $disk = $r->string('disk');
    $path = $r->string('path');
    abort_unless($r->hasValidSignature(), 403);

    $fs = Storage::disk($disk);
    abort_unless($fs->exists($path), 404);

    $filename = basename($path);
    $mime = 'application/zip';
    return response($fs->get($path), 200, [
        'Content-Type' => $mime,
        'Content-Disposition' => "attachment; filename=\"{$filename}\"",
    ]);
})->name('backup.download');
