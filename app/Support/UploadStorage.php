<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToWriteFile;

/**
 * Unggah ke disk cloud (biznet/S3). Jika gagal (jam server miring, jaringan, dll.)
 * simpan ke disk public agar transaksi tidak 500.
 */
final class UploadStorage
{
    public static function disk(): string
    {
        return (string) config('filesystems.upload_disk', 'public');
    }

    public static function store(UploadedFile $file, string $directory): string
    {
        $path = self::put($file, $directory, null);
        if ($path) {
            return $path;
        }

        throw UnableToWriteFile::atLocation($directory, 'Gagal menyimpan file.');
    }

    public static function storeAs(UploadedFile $file, string $directory, string $name): string
    {
        $path = self::put($file, $directory, $name);
        if ($path) {
            return $path;
        }

        throw UnableToWriteFile::atLocation($directory.'/'.$name, 'Gagal menyimpan file.');
    }

    public static function url(?string $path): ?string
    {
        $path = ltrim((string) $path, '/');
        if ($path === '') {
            return null;
        }

        if (is_file(storage_path('app/public/'.$path))) {
            return Storage::disk('public')->url($path);
        }

        return Storage::disk(self::disk())->url($path);
    }

    public static function delete(?string $path): void
    {
        $path = ltrim((string) $path, '/');
        if ($path === '') {
            return;
        }

        foreach (array_unique([self::disk(), 'public']) as $disk) {
            try {
                Storage::disk($disk)->delete($path);
            } catch (\Throwable) {
            }
        }
    }

    private static function put(UploadedFile $file, string $directory, ?string $name): ?string
    {
        $primary = self::disk();
        $path = self::putOn($file, $directory, $name, $primary);
        if ($path) {
            return $path;
        }

        if ($primary !== 'public') {
            Log::warning('Unggah ke '.$primary.' gagal, memakai disk public.', [
                'directory' => $directory,
            ]);
            $path = self::putOn($file, $directory, $name, 'public');
            if ($path) {
                return $path;
            }
        }

        return null;
    }

    private static function putOn(UploadedFile $file, string $directory, ?string $name, string $disk): ?string
    {
        try {
            $path = $name === null
                ? $file->store($directory, $disk)
                : $file->storeAs($directory, $name, $disk);

            return is_string($path) && $path !== '' ? $path : null;
        } catch (UnableToWriteFile $e) {
            Log::warning('UnableToWriteFile', [
                'disk' => $disk,
                'directory' => $directory,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
