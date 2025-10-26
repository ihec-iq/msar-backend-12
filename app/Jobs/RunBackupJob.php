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
use Throwable;

class RunBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $triggerType = 'manual') {}

    public function handle(): void
    {
        $settings = BackupSetting::first();
        if (!$settings || !$settings->enabled) return;

        BackupConfigurator::apply($settings);

        $disk  = $settings->disk;
        $now   = now()->setTimezone($settings->timezone ?? 'Asia/Baghdad');
        $app   = config('app.name', 'laravel');

        $log = BackupLog::create([
            'type'               => $this->triggerType === 'manual' ? 'manual' : 'auto',
            'include_files'      => (bool)$settings->include_files,
            'databases'          => ($settings->multi_db ? ($settings->selected_databases ?? []) : ['mysql']),
            'files'              => $settings->include_files ? [storage_path('app/public')] : [],
            'status'             => 'running',
            'storage_disk'       => $disk,
            'started_at'         => $now,
            'backup_setting_id'  => $settings->id,
        ]);

        $before     = StorageManager::listZipFiles($disk);
        $totalSize  = 0;
        $checksums  = [];
        $finalPaths = [];

        try {
            // ===== (1) قواعد البيانات =====
            $dbList = $settings->multi_db ? ($settings->selected_databases ?? []) : ['mysql'];
            $dbList = array_values(array_unique(array_filter($dbList)));

            foreach ($dbList as $db) {
                // DB فقط
                Config::set('backup.backup.source.databases', [$db]);
                Config::set('backup.backup.source.files.include', []);
                Config::set('backup.backup.source.files.exclude', []);
                Artisan::call('backup:run', ['--only-db' => true, '--no-interaction' => true]);

                $new = array_diff(StorageManager::listZipFiles($disk), $before);
                $before = StorageManager::listZipFiles($disk);

                foreach ($new as $orig) {
                    $dest = StorageManager::buildPath($app, 'db', $db, $now); // backup_db_{db}_...
                    StorageManager::move($disk, $orig, $dest);
                    $meta = StorageManager::meta($disk, $dest);
                    $totalSize += $meta['size'];
                    $checksums[$dest] = $meta['checksum'];
                    $finalPaths[] = $dest;
                }
            }

            // ===== (2) ملفات storage/app/public فقط =====
            if ($settings->include_files) {
                Config::set('backup.backup.source.files.include', [storage_path('app/public')]);
                Config::set('backup.backup.source.files.exclude', []);
                Config::set('backup.backup.source.databases', []); // لا نضمّن DB داخل هذا الأرشيف
                Artisan::call('backup:run', ['--only-files' => true, '--no-interaction' => true]);

                $new = array_diff(StorageManager::listZipFiles($disk), $before);
                $before = StorageManager::listZipFiles($disk);

                foreach ($new as $orig) {
                    $dest = StorageManager::buildPath($app, 'files', 'public', $now); // backup_files_public_...
                    StorageManager::move($disk, $orig, $dest);
                    $meta = StorageManager::meta($disk, $dest);
                    $totalSize += $meta['size'];
                    $checksums[$dest] = $meta['checksum'];
                    $finalPaths[] = $dest;
                }
            }

            // تنظيف وفق استراتيجية Spatie
            Artisan::call('backup:clean');

            // تحديث السجل نجاح + إشعار
            $log->update([
                'status'       => 'success',
                'backup_paths' => $finalPaths,
                'checksums'    => $checksums,
                'total_size'   => $totalSize,
                'finished_at'  => now(),
                'message'      => 'Backup completed successfully.',
            ]);

            \App\Support\NotificationManager::notify($log); // ✅ إشعار بعد النجاح

            // أحدث وقت تشغيل
            $settings->forceFill(['last_run_at' => now()])->save();

        } catch (Throwable $e) {
            // تحديث السجل فشل + إشعار
            $log->update([
                'status'      => 'failed',
                'finished_at' => now(),
                'message'     => $e->getMessage(),
            ]);

            \App\Support\NotificationManager::notify($log); // ✅ إشعار عند الفشل

            throw $e;
        }
    }
}
