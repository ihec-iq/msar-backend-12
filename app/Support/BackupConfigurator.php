<?php
// app/Support/BackupConfigurator.php
namespace App\Support;

use App\Models\BackupSetting;
use Illuminate\Support\Facades\Config;

class BackupConfigurator
{
    public static function apply(?BackupSetting $s = null): void
    {
        $s ??= BackupSetting::first();
        if (!$s || !$s->enabled) return;

        // القرص
        Config::set('backup.destination.disks', [$s->disk]);

        // ملفات + قواعد
        if ($s->include_files) {
            Config::set('backup.backup.source.files.include', $s->include_paths ?: [base_path()]);
            Config::set('backup.backup.source.files.exclude', $s->exclude_paths ?: []);
        } else {
            // بدون ملفات
            Config::set('backup.backup.source.files.include', []);
            Config::set('backup.backup.source.files.exclude', []);
        }
        // مفاتيح files الإضافية المطلوبة بواسطة Spatie v9+
        Config::set('backup.backup.source.files.follow_links', false);
        Config::set('backup.backup.source.files.ignore_unreadable_directories', false);
        Config::set('backup.backup.source.files.relative_path', base_path());

        // قواعد البيانات (واحدة أو عدة)
        Config::set(
            'backup.backup.source.databases',
            ($s->multi_db && $s->selected_databases) ? $s->selected_databases : ['mysql']
        );

        // سياسات الاحتفاظ + الحد الأعلى للمساحة
        Config::set('backup.cleanup.default_strategy.keep_all_backups_for_days', $s->keep_daily_days);
        Config::set('backup.cleanup.default_strategy.keep_daily_backups_for_days', $s->keep_daily_days);
        Config::set('backup.cleanup.default_strategy.keep_weekly_backups_for_weeks', $s->keep_weekly_weeks);
        Config::set('backup.cleanup.default_strategy.keep_monthly_backups_for_months', $s->keep_monthly_months);
        Config::set('backup.cleanup.default_strategy.keep_yearly_backups_for_years', $s->keep_yearly_years);
        Config::set('backup.cleanup.default_strategy.delete_oldest_backups_when_using_more_megabytes_than', $s->max_storage_mb);

        // (اختياري) الإشعارات بالبريد — نضبطها لاحقًا عند إرسال تقاريرنا المخصصة
        // Config::set('backup.notifications.mail.to', [...]);

        // المنطقة الزمنية
        Config::set('app.timezone', $s->timezone ?? 'Asia/Baghdad');
    }
}

