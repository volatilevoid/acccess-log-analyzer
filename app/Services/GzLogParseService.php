<?php

namespace App\Services;

use App\Contracts\LogParserInterface;
use App\Models\AccessLog;
use App\Models\AccessLogEntry;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Parse .gz log line
 */
class GzLogParseService extends BaseLogParseService implements LogParserInterface
{
    public function __construct(AccessLogStorageService $alss)
    {
        parent::__construct($alss);
    }

    /**
     * Parse compressed log file's line
     * 
     * Process and presist each log entry
     *
     * @param string $fileName
     * @param string $logName
     * @return string $parsingStatus
     */
    public function parse(string $fileName, string $logName)
    {
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
        $log->is_enabled = false;
        $logSaveSuccess = $log->save();
        // Remove log file from filesystem if unable to persist it's info
        if(!$logSaveSuccess) {
            $this->storageService->delete($fileName);
            return 'Unable to store log file data in DB';
        }
        // Open  gzipped file handle
        $zp = gzopen($this->storageService->getFilePath($fileName), "r");
        // Unable to read compressed file
        if($zp === false) {
            return 'Can\'t open ' . $fileName . ' for read';
        }
        // Parse each log line
        while(!gzeof($zp)) {
            try {
                $lineData = $this->parseLogLine(gzgets($zp), $log->id);
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
                $chunks = array_chunk($buffer, 1000);
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
        gzclose($zp);
        // Enable log for reading
        $log->is_enabled = true;
        $log->save();
        
        return [
            'name' => $log->name,
            'upload_time' => $log->upload_time
        ];
    }

}