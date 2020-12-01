<?php

namespace App\Services;

use App\Contracts\LogParserInterface;
use App\Models\AccessLog;
use App\Models\AccessLogEntry;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Parse .txt log line
 */
class TxtLogParseService extends BaseLogParseService implements LogParserInterface
{
    private $memoryMax = 0;

    public function __construct(AccessLogStorageService $alss)
    {
        parent::__construct($alss);
    }

    public function parse(string $fileName, string $logName)
    {
        $t1 = microtime(true);
        // Remove limit on max execution time
        \set_time_limit(0);
        // Set entries counter
        $counter = 0;
        // Max buffered entries. Prevent PHP memory limit error
        $maxEntries = 50000;
        $buffer = [];
        // Save log file details in DB
        $log = new AccessLog();
        $log->name = $logName;
        $log->file_name = $fileName;
        $log->size = $this->storageService->getFileSize($fileName);
        // Disable log entries for reading until all are processed
        $log->is_enabled = false;
        $logSaveSuccess = $log->save();
        // Remove log file from filesystem if unable to persist it's info
        if(!$logSaveSuccess) {
            $this->storageService->delete($fileName);
            return 'Unable to store log file data in DB';
        }
        // Open file handle
        $fp = fopen($this->storageService->getFilePath($fileName), "r");
        // Unable to read file
        if($fp === false) {
            return 'Can\'t open ' . $fileName . ' for read';
        }
        // Parse each log line
        while(!feof($fp)) {
            try {
                $lineData = $this->parseLogLine(fgets($fp), $log->id);
            }
            catch(Exception $e) {
                $log->delete();
                $this->storageService->delete($fileName);
                return $e->getMessage();
            }
            // Log error string
            if(is_string($lineData)) {
                Log::warning($lineData . ' in log: ' . $fileName);
                continue;
            }
            // Buffer valid log entry
            array_push($buffer, $lineData);
            $counter++;
            // Buffer is full
            if($counter === $maxEntries) {
                $chunks = array_chunk($buffer, 2000);
                unset($buffer);
                $buffer = [];
                foreach($chunks as $entriesChunk) {
                    AccessLogEntry::insert($entriesChunk);
                }
                $counter = 0;
            }
        }
        // Document end. Check if buffer not empty
        if(isset($buffer[0])) {
            $chunks = array_chunk($buffer, 2000);
            unset($buffer);
            $buffer = [];
            foreach($chunks as $entriesChunk) {
                AccessLogEntry::insert($entriesChunk);
            }
        }
        // Close file handle
        fclose($fp);
        // Enable log for reading
        $log->is_enabled = true;
        $log->save();
        $log->refresh();
        
        return [
            'name' => $log->name,
            'upload_time' => $log->upload_time
        ];
    }
}