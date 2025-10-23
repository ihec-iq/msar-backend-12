<?php

namespace App\Jobs;

use App\Models\BackupLog;
use App\Models\BackupSetting;
use App\Support\BackupConfigurator;
use App\Support\StorageManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Throwable;

class RunBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $triggerType = 'manual') {} // manual | auto

    public function handle(): void
    {
        $settings = BackupSetting::first();
        if (!$settings || !$settings->enabled) {
            return;
        }

        // سيطبّق إعداداتك على config('backup')
        BackupConfigurator::apply($settings);

        $disk  = $settings->disk;
        $now   = now()->setTimezone($settings->timezone ?? 'Asia/Baghdad');
        $app   = config('app.name', 'laravel');

        $log = BackupLog::create([
            'type'               => $this->triggerType === 'manual' ? 'manual' : 'auto',
            'include_files'      => (bool)$settings->include_files,
            'databases'          => $settings->multi_db ? ($settings->selected_databases ?? []) : ['mysql'],
            'files'              => $settings->include_files ? ($settings->include_paths ?? []) : [],
            'status'             => 'running',
            'storage_disk'       => $disk,
            'started_at'         => $now,
            'backup_setting_id'  => $settings->id,
        ]);

        // سنستخدم لالتقاط الملفات الجديدة التي أُنشئت بهذه العملية
        $before = StorageManager::listZipFiles($disk);

        $totalSize = 0;
        $checksums = [];
        $finalPaths = [];

        try {
            // =========== (1) قواعد البيانات ===========
            $dbList = $settings->multi_db ? ($settings->selected_databases ?? []) : ['mysql'];
            $dbList = array_values(array_unique(array_filter($dbList)));

            foreach ($dbList as $db) {
                // تعديل الإعدادات المؤقتة لهذه الجولة: DB فقط لهذه القاعدة
                Config::set('backup.backup.source.databases', [$db]);
                Config::set('backup.backup.source.files.include', []);
                Config::set('backup.backup.source.files.exclude', []);
                // شغّل النسخ
                Artisan::call('backup:run', ['--only-db' => true, '--no-interaction' => true]);

                // التقط الملفات الجديدة
                $new = array_diff(StorageManager::listZipFiles($disk), $before);
                $before = StorageManager::listZipFiles($disk); // حدّث لقطة "قبل" للجولة القادمة

                // نتوقع ملفًا واحدًا جديدًا لكل جولة
                foreach ($new as $origPath) {
                    // أنقل/أعد التسمية إلى مسارنا المتفق
                    $dest = StorageManager::buildPath($app, 'db', $db, $now);
                    StorageManager::move($disk, $origPath, $dest);

                    // احسب الحجم والـ checksum وسجّل
                    $meta = StorageManager::meta($disk, $dest);
                    $totalSize += $meta['size'];
                    $checksums[$dest] = $meta['checksum'];
                    $finalPaths[] = $dest;
                }
            }

            // =========== (2) الملفات (اختياري) ===========
            if ($settings->include_files) {
                // ضبط ملفات فقط
                Config::set('backup.backup.source.files.include', $settings->include_paths ?? [base_path()]);
                Config::set('backup.backup.source.files.exclude', $settings->exclude_paths ?? []);
                Config::set('backup.backup.source.databases', []); // حتى لا يضع DB داخل هذا الأرشيف
                Artisan::call('backup:run', ['--only-files' => true, '--no-interaction' => true]);

                $new = array_diff(StorageManager::listZipFiles($disk), $before);
                $before = StorageManager::listZipFiles($disk);

                foreach ($new as $origPath) {
                    $dest = StorageManager::buildPath($app, 'files', '-', $now);
                    StorageManager::move($disk, $origPath, $dest);

                    $meta = StorageManager::meta($disk, $dest);
                    $totalSize += $meta['size'];
                    $checksums[$dest] = $meta['checksum'];
                    $finalPaths[] = $dest;
                }
            }

            // تنظيف حسب سياسة الاحتفاظ (Spatie cleanup)
            Artisan::call('backup:clean');

            // تحديث السجل نجاح
            $log->update([
                'status'       => 'success',
                'backup_paths' => $finalPaths,
                'checksums'    => $checksums,
                'total_size'   => $totalSize,
                'finished_at'  => now(),
                'message'      => 'Backup completed successfully.',
            ]);

            // تحديث آخر وقت تشغيل
            $settings->forceFill(['last_run_at' => now()])->save();

        } catch (Throwable $e) {
            $log->update([
                'status'      => 'failed',
                'finished_at' => now(),
                'message'     => $e->getMessage(),
            ]);
            // ممكن هنا نستدعي NotificationManager للفشل
            throw $e;
        }
    }
}
