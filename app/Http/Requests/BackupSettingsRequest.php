<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BackupSettingsRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            // عام
            'enabled' => ['boolean'],
            'cron' => ['string'],
            'timezone' => ['string'],
            'max_storage_mb' => ['integer','min:100'],

            // النطاق
            'include_files' => ['boolean'],
            'include_paths' => ['array','nullable'],
            'include_paths.*' => ['string'],
            'exclude_paths' => ['array','nullable'],
            'exclude_paths.*' => ['string'],
            'multi_db' => ['boolean'],
            'selected_databases' => ['array','nullable'],
            'selected_databases.*' => ['string'],

            // الاحتفاظ
            'keep_daily_days' => ['integer','min:0'],
            'keep_weekly_weeks' => ['integer','min:0'],
            'keep_monthly_months' => ['integer','min:0'],
            'keep_yearly_years' => ['integer','min:0'],

            // التخزين
            'disk' => ['string'],
            'drive_folder' => ['string'],
            'temp_link_expiry' => ['integer','min:5','max:1440'],

            // التحقق
            'checksum_enabled' => ['boolean'],

            // الإشعارات
            'notify_enabled' => ['boolean'],
            'notify_on' => ['in:success,failure,both'],
            'emails' => ['nullable','string'], // نفصّلها بفارزة في السيرفر
            'telegram_bot_token' => ['nullable','string'],
            'telegram_chat_ids' => ['nullable','string'], // عدة IDs مفصولة بفارزة
            'webhook_urls' => ['nullable','string'], // عدة URLs بفارزة
            'webhook_secret' => ['nullable','string'],
            'stale_hours' => ['integer','min:6','max:168'], // 6 إلى 7 أيام
        ];
    }
}
