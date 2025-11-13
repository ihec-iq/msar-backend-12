<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

    // التحقق من التوقيع
    // نستخدم absolute = false لتجاهل مشاكل الـ domain على production
    // هذا أكثر أماناً من تعطيل التحقق تماماً لأنه لا يزال يتحقق من التوقيع والوقت
    if (! URL::hasValidSignature($r, false, $ignore)) {
        // Debug: سجّل الخطأ لنرى ماذا حدث
        Log::warning('Signature validation failed', [
            'full_url' => $r->fullUrl(),
            'app_url' => config('app.url'),
            'query' => $r->query(),
            'path' => $r->path(),
            'host' => $r->getHost(),
            'scheme' => $r->getScheme(),
        ]);
        abort(403, 'Invalid or expired download link');
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