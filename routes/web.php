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
 

Route::get('/download/backup', function (\Illuminate\Http\Request $r) {
    // تجاهل بارامترات زائدة من بروكسي/UTM
    $ignore = [
        'utm_source','utm_medium','utm_campaign','utm_term','utm_content',
        'gclid','fbclid','__cf_chl_tk','__cf_chl_captcha_tk__','__cf_chl_f_tk',
        '__cf_bm','t','s'
    ];

    // ✅ تحقّق من التوقيع أولاً (على النص المشفّر كما هو)
    if (! URL::hasValidSignature($r, absolute: true, ignoreQuery: $ignore)) {
        abort(403);
    }

    // تحقق المدخلات المطلوبة
    $r->validate([
        'disk' => ['required','string'],
        'p'    => ['required','string'], // المسار مشفّر
    ]);

    // فك التشفير Base64 URL-safe
    $p = $r->query('p');
    $pad = strlen($p) % 4 ? str_repeat('=', 4 - strlen($p) % 4) : '';
    $path = base64_decode(strtr($p.$pad, '-_', '+/'), true);

    if ($path === false) abort(400, 'Invalid path encoding');

    $disk = (string) $r->query('disk');
    $fs = Storage::disk($disk);

    abort_unless($fs->exists($path), 404);

    $filename = basename($path);
    return response($fs->get($path), 200, [
        'Content-Type' => 'application/zip',
        'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        'Cache-Control' => 'private, max-age=0, no-cache',
    ]);
})->name('backup.download');
