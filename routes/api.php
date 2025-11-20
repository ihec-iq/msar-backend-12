<?php

use App\Http\Controllers\Api\v1\LogFileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

require __DIR__ . '/api/authRoute.php';
require __DIR__ . '/api/archiveRoute.php';
require __DIR__ . '/api/stockRoute.php';
require __DIR__ . '/api/userRoute.php';
require __DIR__ . '/api/employeeRoute.php';
require __DIR__ . '/api/vacationRoute.php';
require __DIR__ . '/api/botRoute.php';
require __DIR__ . '/api/settingRoute.php';
require __DIR__ . '/api/promotionRoute.php';
require __DIR__ . '/api/backupRoute.php';
require __DIR__ . '/api/dashboardRoute.php';



Route::get(uri: '/check', action: function (): \Illuminate\Http\JsonResponse {
    return response()->json(data: ['state' => 'ERP MSAR API running...']);;
});

//region upload file to drive
Route::get('/info', function () {
    return 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
});
Route::get('/getBotInfo', function () {
    $url = config('telegram.api.base_url') . config('telegram.bot_token') . '/getWebhookInfo';
    $reposnse = Http::get($url);
    return response()->json($reposnse->json());
});
Route::get('/setBotWebhook/{site}', function ($site) {
    $webhookUrl = 'https://' . $site . config('telegram.webhook.path');
    $url = config('telegram.api.base_url') . config('telegram.bot_token') . '/setWebhook?url=' . $webhookUrl . '&drop_pending_updates=true';
    $reposnse = Http::get($url);
    return response()->json($reposnse->json());
});
Route::get('/testwebhook', function ($resq='test webhook working fine') { 
    $reposnse = ['message' => 'test webhook working fine' , 'status' => 200,'request'=>$resq];
    Log::info('test webhook working fine', ['request'=>$resq]);
    return response()->json($reposnse);
});
//http://localhost/msar-backend-12/public/api/testwebhook