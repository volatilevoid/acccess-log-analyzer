<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Service class for communication with filesystem
 */
class AccessLogStorageService
{
    private string $logsFolder = 'access_logs';

    public function store(UploadedFile $file)
    {
        return Storage::putFile($this->logsFolder, $file);
    }

    public function delete(string $filePath): bool
    {
        if(Storage::disk('local')->exists($filePath)) {
            return Storage::disk('local')->delete($filePath);
        }
        return true;
    }

    public function isFilePresent(string $filePath): bool
    {
        if(Storage::disk('local')->exists($filePath)) {
            return true;
        }
        return false;
    }

    public function getFullPath(string $filePath): string
    {
        return storage_path("app/{$filePath}");
    }

    public function getFileSize(string $filePath): int
    {
        return Storage::disk('local')->size($filePath);
    }
}