<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupSetting extends Model
{
    protected $fillable = [
        'enabled', 'cron', 'timezone', 'max_storage_mb',
        'include_files', 'include_paths', 'exclude_paths', 'multi_db', 'selected_databases',
        'keep_daily_days', 'keep_weekly_weeks', 'keep_monthly_months', 'keep_yearly_years',
        'disk', 'drive_folder', 'temp_link_expiry', 'checksum_enabled',
        'notify_enabled', 'notify_on', 'emails', 'telegram_bot_token',
        'telegram_chat_ids', 'webhook_urls', 'webhook_secret', 'stale_hours',
        'last_run_at',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'include_files' => 'boolean',
        'multi_db' => 'boolean',
        'checksum_enabled' => 'boolean',
        'notify_enabled' => 'boolean',
        'include_paths' => 'array',
        'exclude_paths' => 'array',
        'selected_databases' => 'array',
        'last_run_at' => 'datetime',
    ];
}
