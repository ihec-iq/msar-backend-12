<?php

use App\Jobs\RunBackupJob;
use App\Models\BackupSetting;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

require __DIR__.'/api/authRoute.php';
require __DIR__.'/api/archiveRoute.php';
require __DIR__.'/api/stockRoute.php';
require __DIR__.'/api/userRoute.php';
require __DIR__.'/api/employeeRoute.php';
require __DIR__.'/api/vacationRoute.php';
require __DIR__.'/api/botRoute.php';
require __DIR__.'/api/settingRoute.php';
require __DIR__.'/api/promotionRoute.php';
require __DIR__.'/api/backupRoute.php';



Route::get(uri:'/check', action:function (): \Illuminate\Http\JsonResponse {
    return response()->json(data:    ['state' => 'ERP MSAR API running...']);
    ;
});

//region upload file to drive
Route::get('/info', function () {
    return 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
});
Route::get('/getBotInfo', function () {
    $url = 'https://api.telegram.org/bot'.env('TELEGRAM_BOT_TOKEN').'/getWebhookInfo';// return $url;
    $reposnse = Http::get($url);
    return response()->json($reposnse->json());
});
Route::get('/setBotWebhook/{site}', function ($site) {
    $url = 'https://api.telegram.org/bot'.env('TELEGRAM_BOT_TOKEN').'/setWebhook?url=https://'.$site.'/ihec-backend/public/api/bot/onBoard&drop_pending_updates=true';// return $url;
    $reposnse = Http::get($url);
    return response()->json($reposnse->json());
});

// Route::post('/backup/run', function () {
//     $s = BackupSetting::first();
//     abort_unless($s && $s->enabled, 400, 'Backup disabled.');
//     dispatch_sync(new RunBackupJob('manual')); // فوري للـ response
//     return response()->json(['status' => 'ok', 'ran_at' => now()]);
// });
