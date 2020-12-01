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
        return Storage::putFileAs($this->logsFolder, $file, $file->getClientOriginalName());
    }

    public function delete(string $fileName): bool
    {
        if(Storage::disk('local')->exists("{$this->logsFolder}/{$fileName}")) {
            return Storage::disk('local')->delete("{$this->logsFolder}/{$fileName}");
        }
        return true;
    }

    public function isFilePresent(string $fileName): bool
    {
        if(Storage::disk('local')->exists("{$this->logsFolder}/{$fileName}")) {
            return true;
        }
        return false;
    }

    public function getFilePath(string $fileName): string
    {
        return storage_path("app/{$this->logsFolder}/{$fileName}");
    }

    public function getFileSize(string $fileName): int
    {
        return Storage::disk('local')->size("{$this->logsFolder}/{$fileName}");
    }
}