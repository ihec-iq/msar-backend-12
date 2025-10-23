<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class StorageManager
{
    /**
     * Listing current zip files on a disk (optionally under a prefix).
     */
    public static function listZipFiles(string $disk, ?string $prefix = null): array
    {
        $fs = Storage::disk($disk);
        $files = $prefix ? $fs->allFiles($prefix) : $fs->allFiles();
        return array_values(array_filter($files, fn ($f) => str_ends_with(strtolower($f), '.zip')));
    }

    /**
     * Move/rename a file on the disk (ensures target dir exists implicitly).
     */
    public static function move(string $disk, string $from, string $to): bool
    {
        $fs = Storage::disk($disk);
        // create dir if needed
        $dir = trim(str_replace(basename($to), '', $to), '/');
        if ($dir && !$fs->exists($dir)) {
            $fs->makeDirectory($dir);
        }
        return $fs->move($from, $to);
    }

    /**
     * Get size (bytes) and checksum (sha256) for a file on the disk.
     */
    public static function meta(string $disk, string $path): array
    {
        $fs = Storage::disk($disk);
        $size = $fs->size($path);

        // stream hashing (memory-friendly)
        $hashCtx = hash_init('sha256');
        $stream = $fs->readStream($path);
        if ($stream === false) {
            throw new \RuntimeException("Cannot read stream for checksum: {$path}");
        }
        while (!feof($stream)) {
            $buf = fread($stream, 1024 * 1024);
            if ($buf === false) break;
            hash_update($hashCtx, $buf);
        }
        fclose($stream);
        $checksum = 'sha256:' . hash_final($hashCtx);

        return ['size' => $size, 'checksum' => $checksum];
    }

    /**
     * Build destination path convention.
     * Backups/{APP_NAME}/{TYPE}/{DBNAME}/backup_{type}_{dbname}_{YYYY-MM-DD_HH-mm}.zip
     */
    public static function buildPath(
        string $appName,
        string $type,           // db | files
        string $dbNameOrDash,   // db name or "-"
        \DateTimeInterface $when
    ): string {
        $stamp = $when->format('Y-m-d_H-i');
        $safeApp = preg_replace('/[^a-z0-9\-_]+/i', '-', $appName);
        $safeDb  = preg_replace('/[^a-z0-9\-_]+/i', '-', $dbNameOrDash);

        return "Backups/{$safeApp}/{$type}/{$safeDb}/backup_{$type}_{$safeDb}_{$stamp}.zip";
    }
}
