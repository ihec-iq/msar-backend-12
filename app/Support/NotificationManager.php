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
        // تحميل الإعدادات العامة
        $settings = \App\Models\BackupSetting::first();
        if (!$settings || !$settings->notify_enabled) {
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

        // إن لم يوجد أحد لاستلام الإشعار، لا نكمل
        if (empty($emails) && empty($chatIds) && empty($settings->webhook_urls)) {
            return;
        }

        // إعداد الملفات/المسارات والروابط المؤقتة
        $disk   = $log->storage_disk ?? ($settings->disk ?? 'local');
        $paths  = (array)($log->backup_paths ?? []);
        $expiryMinutes = max(1, (int)($settings->temp_link_expiry ?? 60)); // بالدقائق
        $encode = fn(string $s) => rtrim(strtr(base64_encode(string: $s), '+/', '-_'), '=');
        $tempUrls = [];
        // Helper inline function for safe Base64 encoding

        $expiryMinutes = max(1, (int) ($s->temp_link_expiry ?? 60));
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
        if (!empty($chatIds) && !empty($settings->telegram_bot_token)) {
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
                    // أرسل الرسالة النصية أولاً
                    Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                        'chat_id' => $chatId,
                        'text' => $tgText,
                        'parse_mode' => 'HTML',
                    ]);

                    // حاول إرفاق كل ملف إن كان صغيرًا، وإلا أرسل الرابط فقط
                    foreach ($paths as $p) {
                        $size = null;
                        try {
                            $size = Storage::disk($disk)->size($p);
                        } catch (\Throwable $e) {
                        }

                        if (is_int($size) && $size <= $attachmentLimit && isset($tempUrls[$p])) {
                            // Telegram يدعم document=URL مباشر
                            Http::post("https://api.telegram.org/bot{$token}/sendDocument", [
                                'chat_id'  => $chatId,
                                'document' => $tempUrls[$p],
                                'caption'  => "Backup file: " . basename($p),
                            ]);
                        } elseif (isset($tempUrls[$p])) {
                            Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                                'chat_id' => $chatId,
                                'text'    => "Large file — download: " . $tempUrls[$p],
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
        if (!empty($emails)) {
            foreach ($emails as $to) {
                try {
                    $subject = "[Backup] " . strtoupper($event) . " - " . config('app.name');
                    $textLines = [];
                    $textLines[] = "Event: {$payload['event']}";
                    $textLines[] = "Time: {$payload['timestamp']}";
                    $textLines[] = "Type: {$payload['type']}";
                    $textLines[] = "Size: " . number_format($payload['size']) . " bytes";
                    $textLines[] = "Message: {$payload['message']}";
                    if (!empty($payload['temp_urls'])) {
                        $textLines[] = "Files:";
                        foreach ($payload['temp_urls'] as $path => $url) {
                            $textLines[] = basename($path) . " -> " . $url;
                        }
                    }
                    $body = implode("\n", $textLines);

                    // جهّز مرفقات صغيرة فقط
                    $attachments = [];
                    foreach ($paths as $p) {
                        try {
                            $size = Storage::disk($disk)->size($p);
                            if ($size <= $attachmentLimit) {
                                $data =  Storage::disk($disk)->get($p);
                                $attachments[] = ['name' => basename($p), 'data' => $data];
                            }
                        } catch (\Throwable $e) {
                        }
                    }

                    Mail::send([], [], function ($message) use ($to, $subject, $body, $attachments) {
                        $message->to($to)->subject($subject);
                        $message->setBody($body, 'text/plain');
                        foreach ($attachments as $att) {
                            $message->attachData($att['data'], $att['name']);
                        }
                    });
                } catch (\Throwable $e) {
                    // \Log::error('Mail notify error: '.$e->getMessage());
                }
            }
        }

        // ===========================
        // 3) Webhook
        // ===========================
        if (!empty($settings->webhook_urls)) {
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
}
