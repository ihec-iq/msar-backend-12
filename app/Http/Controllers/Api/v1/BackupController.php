<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BackupRestoreRequest;
use App\Jobs\RunBackupJob;
use App\Models\BackupSetting;
use App\Support\StorageManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BackupController extends Controller
{
    // تشغيل يدوي
    public function runNow()
    {
        $s = BackupSetting::firstOrFail();
        abort_unless($s->enabled, 400, 'Backup disabled.');
        dispatch_sync(new RunBackupJob('manual'));
        
        return response()->json(['status' => 'ok', 'ran_at' => now()]);
    }

    // قائمة النسخ (نعرض .zip فقط)
    public function list(Request $r)
    {
        $s = BackupSetting::firstOrFail();
        $disk = $s->disk;
        $prefix = 'Backups/'.preg_replace('/[^a-z0-9\-_]+/i', '-', config('app.name', 'laravel'));
        $files = StorageManager::listZipFiles($disk, $prefix);

        $data = array_map(function ($p) use ($disk) {
            $fs = Storage::disk($disk);
            return [
                'path' => $p,
                'size' => $fs->size($p),
                'lastModified' => $fs->lastModified($p),
                'url' => null, // سنوفر عبر tempLink
            ];
        }, $files);

        return $data;
    }

    // حذف نسخة
    public function delete(Request $r)
    {
        $r->validate(['path' => ['required','string']]);
        $s = BackupSetting::firstOrFail();
        $ok = Storage::disk($s->disk)->delete($r->string('path'));
        return ['deleted' => (bool)$ok];
    }

    // رابط مؤقت (Proxy بسيط موقّع)
    public function tempLink(Request $r)
    {
        $r->validate(['path' => ['required','string']]);
        $s = BackupSetting::firstOrFail();

        // ننشئ URL موقّع إلى مسار تحميل داخلي
        $minutes = max(5, (int) $s->temp_link_expiry);
        $signed = url()->temporarySignedRoute(
            'backup.download',
            now()->addMinutes($minutes),
            ['disk' => $s->disk, 'path' => $r->string('path')]
        );

        return ['url' => $signed, 'expires_in_minutes' => $minutes];
    }

    // استعادة
    public function restore(BackupRestoreRequest $req)
    {
        // سنضيف Skeleton خدمة الاستعادة الآن، والتفاصيل التنفيذية لاحقًا
        // نتأكد من Maintenance Mode + تحقق checksum قبل التنفيذ
        return ['status' => 'scheduled', 'at' => now()];
    }
}
