<?php

namespace App\Support;

use App\Models\BackupLog;
use App\Models\BackupSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\BackupAdmin;

class NotificationManager
{
    // حجم أقصى (بايت) لإرفاق الملف مباشرة في Telegram / Email.
    // Telegram bot limit (practical) ~ 50MB but to be safe we use 25MB default.
    protected const ATTACHMENT_SIZE_LIMIT = 25 * 1024 * 1024;

    /**
     * إرسال إشعارات حسب الإعدادات (نجاح/فشل) مع إمكانية إرفاق الملف.
     */
    /**
     * إرسال إشعارات اكتمال/فشل النسخ الاحتياطي مع ملفات مرفقة إن أمكن.
     * - يجمع حسابات الإدمن الفعّالة من BackupAdmin (telegram/email).
     * - يبني روابط تنزيل موقّتة عبر الـ signed route: backup.download
     * - Telegram: يرسل نص ثم يحاول إرفاق المستند (إن كان حجمه <= 25MB)، وإلا يرسل رابط.
     * - Email: يرفق الصغيرة ويرسل الروابط للبقية.
     * - Webhook: يرسل JSON مع التوقيع HMAC إن كان secret موجودًا.
     */
    public static function notify(\App\Models\BackupLog $log): void
    {
        \Log::info('NotificationManager::notify called', ['log_id' => $log->id, 'status' => $log->status]);

        // تحميل الإعدادات العامة
        $settings = \App\Models\BackupSetting::first();
        if (!$settings || !$settings->notify_enabled) {
            \Log::info('Notifications disabled or settings not found');
            return;
        }

        // فلترة نوع الإشعار المطلوب
        $event = $log->status === 'success' ? 'success' : 'failure';
        if ($settings->notify_on === 'success' && $event !== 'success') return;
        if ($settings->notify_on === 'failure' && $event !== 'failure') return;

        // جلب الإدمنية الفعّالين ووسائل الإرسال المختارة لهم
        $admins   = \App\Models\BackupAdmin::where('active', true)->get();
        $emails   = $admins->filter(fn($a) => in_array('email', (array)$a->notify_via) && $a->email)->pluck('email')->all();
        $chatIds  = $admins->filter(fn($a) => in_array('telegram', (array)$a->notify_via) && $a->telegram_id)->pluck('telegram_id')->all();

        \Log::info('Notification recipients', ['emails' => $emails, 'chat_ids' => $chatIds]);

        // إن لم يوجد أحد لاستلام الإشعار، لا نكمل
        if (empty($emails) && empty($chatIds) && empty($settings->webhook_urls)) {
            return;
        }

        // إعداد الملفات/المسارات والروابط المؤقتة
        $disk   = $log->storage_disk ?? ($settings->disk ?? 'local');
        $paths  = (array)($log->backup_paths ?? []);
        $expiryMinutes = max(1, (int)($settings->temp_link_expiry ?? 60)); // بالدقائق
        $encode = fn(string $s) => rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
        $tempUrls = [];

        foreach ($paths as $p) {
            $tempUrls[$p] = url()->temporarySignedRoute(
                'backup.download',
                now()->addMinutes($expiryMinutes),
                [
                    'disk' => $disk,
                    'p'    => $encode($p), // ← استخدم p بدل path
                ]
            );
        }

        // بناء الـ payload النصّي/المنظّم
        $payload = [
            'event'     => 'backup.' . $event,                      // backup.success | backup.failure
            'timestamp' => now()->toIso8601String(),
            'type'      => $log->include_files ? 'db+files' : 'db',
            'paths'     => array_values($paths),
            'temp_urls' => $tempUrls,
            'size'      => (int)($log->total_size ?? 0),
            'message'   => $log->message ?? ($event === 'success' ? 'Backup succeeded' : 'Backup failed'),
            'checksums' => (array)($log->checksums ?? []),
        ];

        // حدّ المرفقات (25MB افتراضيًا لتجنب مشاكل تيليجرام والبريد)
        $attachmentLimit = 25 * 1024 * 1024;

        // ===========================
        // 1) Telegram
        // ===========================
        if ($settings->telegram_enabled && !empty($chatIds) && !empty($settings->telegram_bot_token)) {
            $token = trim($settings->telegram_bot_token);

            // نص مختصر (HTML-safe) للرسالة الأولى
            $lines = [];
            $lines[] = "Event: {$payload['event']}";
            $lines[] = "Time: {$payload['timestamp']}";
            $lines[] = "Type: {$payload['type']}";
            $lines[] = "Size: " . number_format($payload['size']) . " bytes";
            $lines[] = "Message: {$payload['message']}";
            if (!empty($payload['temp_urls'])) {
                $lines[] = "Files:";
                foreach ($payload['temp_urls'] as $path => $url) {
                    $lines[] = htmlspecialchars(basename($path), ENT_QUOTES | ENT_SUBSTITUTE) . " -> " . htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE);
                }
            }
            $tgText = implode("\n", $lines);

            foreach ($chatIds as $chatId) {
                try {
                    // إرسال الرسالة النصية أولاً
                    Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                        'chat_id' => $chatId,
                        'text' => $tgText,
                        'parse_mode' => 'HTML',
                    ]);

                    // إرسال الملفات
                    \Log::info('Telegram: Starting to send files', ['total_files' => count($paths)]);

                    foreach ($paths as $p) {
                        try {
                            $fs = Storage::disk($disk);
                            if (!$fs->exists($p)) {
                                \Log::warning('Telegram: File not found', ['path' => $p]);
                                continue;
                            }

                            $size = $fs->size($p);
                            $fileName = basename($p);

                            \Log::info('Telegram: Sending file', [
                                'path' => $p,
                                'size' => $size,
                                'size_mb' => round($size / (1024 * 1024), 2),
                                'limit_mb' => round($attachmentLimit / (1024 * 1024), 2)
                            ]);

                            // تنسيق حجم الملف (KB/MB/GB)
                            $formattedSize = self::formatFileSize($size);

                            // للملفات الصغيرة (<= 25MB)، نرسلها مباشرة
                            if ($size <= $attachmentLimit) {
                                $fileContent = $fs->get($p);

                                $response = Http::attach(
                                    'document',
                                    $fileContent,
                                    $fileName
                                )->post("https://api.telegram.org/bot{$token}/sendDocument", [
                                    'chat_id' => $chatId,
                                    'caption' => "📦 {$fileName}\nSize: {$formattedSize}",
                                ]);

                                \Log::info('Telegram: File sent', [
                                    'file' => $fileName,
                                    'status' => $response->status(),
                                    'success' => $response->successful()
                                ]);
                            } else {
                                // للملفات الكبيرة، نرسل رابط التحميل
                                if (isset($tempUrls[$p])) {
                                    $response = Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                                        'chat_id' => $chatId,
                                        'text'    => "📦 Large file ({$formattedSize})\n\nDownload: {$tempUrls[$p]}",
                                    ]);

                                    \Log::info('Telegram: Large file link sent', [
                                        'file' => $fileName,
                                        'status' => $response->status()
                                    ]);
                                }
                            }
                        } catch (\Throwable $e) {
                            \Log::error('Telegram: Failed to send file', [
                                'path' => $p,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                } catch (\Throwable $e) {
                    // يمكنك تسجيل الخطأ إن أحببت:
                    // \Log::error('Telegram notify error: '.$e->getMessage());
                }
            }
        }

        // ===========================
        // 2) Email
        // ===========================
        if ($settings->email_enabled && !empty($emails)) {
            // جهّز مرفقات صغيرة فقط
            $attachments = [];
            foreach ($paths as $p) {
                try {
                    $size = Storage::disk($disk)->size($p);
                    if ($size <= $attachmentLimit) {
                        $data = Storage::disk($disk)->get($p);
                        $attachments[] = ['name' => basename($p), 'data' => $data];
                    }
                } catch (\Throwable $e) {
                    // تجاهل الأخطاء
                }
            }

            foreach ($emails as $to) {
                try {
                    Mail::to($to)->send(
                        new \App\Mail\BackupNotificationMail(
                            payload: $payload,
                            event: $event,
                            attachmentData: $attachments
                        )
                    );
                } catch (\Throwable $e) {
                    // \Log::error('Mail notify error: '.$e->getMessage());
                }
            }
        }

        // ===========================
        // 3) Webhook
        // ===========================
        if ($settings->webhook_enabled && !empty($settings->webhook_urls)) {
            $urls = collect(explode(',', $settings->webhook_urls))
                ->map(fn($u) => trim($u))
                ->filter()
                ->all();

            foreach ($urls as $url) {
                try {
                    $body = $payload; // يتضمن temp_urls
                    $req  = Http::asJson();

                    if (!empty($settings->webhook_secret)) {
                        $json = json_encode($body);
                        $sig  = hash_hmac('sha256', $json, $settings->webhook_secret);
                        $req  = $req->withHeaders(['X-Backup-Signature' => "sha256={$sig}"]);
                    }

                    $req->post($url, $body);
                } catch (\Throwable $e) {
                    // \Log::error('Webhook notify error: '.$e->getMessage());
                }
            }
        }
    }


    /**
     * تنسيق نص الإشعار.
     * @param array $payload
     * @param bool $forTelegram - لو true نستخدم HTML-escaped مختصر
     * @return string
     */
    private static function formatText(array $payload, bool $forTelegram = false): string
    {
        $lines = [];
        $lines[] = "Event: {$payload['event']}";
        $lines[] = "Time: {$payload['timestamp']}";
        $lines[] = "Type: {$payload['type']}";
        $lines[] = "Size: " . number_format($payload['size']) . " bytes";
        $lines[] = "Message: {$payload['message']}";

        if (!empty($payload['temp_urls'])) {
            $lines[] = "Files:";
            foreach ($payload['temp_urls'] as $path => $url) {
                $lines[] = basename($path) . " -> " . $url;
            }
        } elseif (!empty($payload['paths'])) {
            $lines[] = "Files:";
            foreach ($payload['paths'] as $p) $lines[] = " - " . basename($p);
        }

        $text = implode("\n", $lines);
        if ($forTelegram) {
            // نستخدم HTML limited escape
            return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE);
        }
        return $text;
    }

    /**
     * تنسيق حجم الملف بشكل مقروء (Bytes/KB/MB/GB)
     *
     * @param int $bytes حجم الملف بالبايت
     * @param int $precision دقة الأرقام العشرية
     * @return string الحجم المنسق
     */
    private static function formatFileSize(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
