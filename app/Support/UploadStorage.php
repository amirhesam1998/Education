<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class UploadStorage
{
    /**
     * Store an uploaded file on the private disk, or fail with a form error instead of a 500.
     *
     * The local disk does not throw on write errors and Flysystem throws when a directory
     * cannot be created, so an unwritable storage folder on the server would otherwise
     * either crash the request or save a record pointing at no file.
     */
    public static function store(UploadedFile $file, string $directory, string $field): string
    {
        try {
            $path = $file->store($directory, 'local');
        } catch (Throwable $exception) {
            Log::error('Upload could not be stored.', [
                'directory' => $directory,
                'field' => $field,
                'exception' => $exception,
            ]);
            $path = false;
        }

        if (! is_string($path) || $path === '') {
            if (! isset($exception)) {
                Log::error('Upload could not be stored: the disk refused the write.', [
                    'directory' => $directory,
                    'field' => $field,
                    'root' => config('filesystems.disks.local.root'),
                ]);
            }

            throw ValidationException::withMessages([
                $field => 'ذخیره فایل روی سرور انجام نشد. لطفاً چند دقیقه بعد دوباره تلاش کنید یا با آموزشگاه تماس بگیرید.',
            ]);
        }

        return $path;
    }
}
