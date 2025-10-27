<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/test', function () {
    return 'Its Case';
});
 

Route::get('/download/backup', function (Request $r) {
    // تجاهل بارامترات يضيفها البروكسي/UTM الخ..
    $ignore = [
        'utm_source','utm_medium','utm_campaign','utm_term','utm_content',
        'gclid','fbclid','__cf_bm','t','s'
    ];

    // ✅ اجعل التحقق "نسبي" (absolute = false) لتفادي اختلاف الدومين/البروتوكول
    if (! URL::hasValidSignature($r, false, $ignore)) {
        // Debug اختياري:
        // \Log::info('sig_failed', ['full' => $r->fullUrl(), 'app_url' => config('app.url')]);
        abort(403);
    }

    $r->validate([
        'disk' => ['required','string'],
        'p'    => ['required','string'], // path مشفّر Base64 (url-safe)
    ]);

    // فك ترميز Base64 (url-safe)
    $p = $r->query('p');
    $pad  = strlen($p) % 4 ? str_repeat('=', 4 - strlen($p) % 4) : '';
    $path = base64_decode(strtr($p.$pad, '-_', '+/'), true);
    abort_if($path === false, 400, 'Invalid path encoding');

    $disk = (string) $r->query('disk');
    $fs = Storage::disk($disk);
    abort_unless($fs->exists($path), 404);

    $filename = basename($path);
    return response($fs->get($path), 200, [
        'Content-Type' => 'application/zip',
        'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        'Cache-Control' => 'private, max-age=0, no-cache',
    ]);
})->name('backup.download');