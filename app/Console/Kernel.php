<?php

namespace App\Console;

use App\Jobs\RunBackupJob;
use App\Models\BackupSetting;
use Cron\CronExpression;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->call(function () {
            $s = BackupSetting::first();
            if (!$s || !$s->enabled) return;

            $cron = new CronExpression($s->cron);
            // نستخدم التوقيت الذي حدده المستخدم
            $now = now($s->timezone ?? 'Asia/Baghdad');

            if ($cron->isDue($now)) {
                dispatch(new RunBackupJob('auto'));
            }
        })->everyMinute()->name('dynamic-backup-runner')->withoutOverlapping();
    }
}
