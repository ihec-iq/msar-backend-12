<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// إنشاء request وهمي
$request = \Illuminate\Http\Request::create(
    '/api/v1/backup/settings',
    'PUT',
    [
        'enabled' => 'true',
        'include_files' => 'false',
        'multi_db' => 'true',
        'checksum_enabled' => 'true',
        'notify_enabled' => 'false',
        'telegram_enabled' => 'true',
        'email_enabled' => 'true',
        'webhook_enabled' => 'false',
        'max_storage_mb' => '50000',
        'keep_daily_days' => '7',
        'cron' => '0 15 * * *',
        'timezone' => 'Asia/Baghdad',
        'disk' => 'local',
        'notify_on' => 'both',
        'temp_link_expiry' => '60',
        'stale_hours' => '48',
    ]
);

// إنشاء FormRequest instance
$formRequest = App\Http\Requests\BackupSettingsRequest::createFrom($request);

// تشغيل validation
try {
    $validated = $formRequest->validated();
    echo "✓ Validation PASSED!\n\n";
    echo "Validated data:\n";
    foreach ($validated as $key => $value) {
        $type = gettype($value);
        $display = is_bool($value) ? ($value ? 'true' : 'false') : $value;
        echo "  $key: $display ($type)\n";
    }
} catch (\Illuminate\Validation\ValidationException $e) {
    echo "✗ Validation FAILED!\n\n";
    echo "Errors:\n";
    foreach ($e->errors() as $field => $errors) {
        echo "  $field:\n";
        foreach ($errors as $error) {
            echo "    - $error\n";
        }
    }
}
