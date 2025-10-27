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

    // تجاهل بارامترات شائعة يضيفها Cloudflare/UTM/Telegram
    $ignore = [
        'utm_source','utm_medium','utm_campaign','utm_term','utm_content',
        'gclid','fbclid','__cf_chl_tk','__cf_chl_captcha_tk__','__cf_chl_f_tk',
        '__cf_bm','t','s'
    ];

    // Laravel 10+ يوفّر تجاهل بارامترات في hasValidSignature
    if (! URL::hasValidSignature($r, absolute: true, ignoreQuery: $ignore)) {
        abort(403);
    }

    $disk = (string) $r->query('disk');
    $path = (string) $r->query('path');

    $fs = Storage::disk($disk);
    abort_unless($fs->exists($path), 404);

    $filename = basename($path);
    return response($fs->get($path), 200, [
        'Content-Type' => 'application/zip',
        'Content-Disposition' => "attachment; filename=\"{$filename}\"",
    ]);
})->name('backup.download');
