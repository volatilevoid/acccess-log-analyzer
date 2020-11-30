<?php

namespace App\Services;

use App\Contracts\LogParserInterface;
use App\Models\AccessLog;
use App\Models\AccessLogEntry;
use Exception;
use Illuminate\Support\Facades\Log;

class TxtLogParseService extends BaseLogParseService implements LogParserInterface
{
    public function __construct(AccessLogStorageService $alss)
    {
        parent::__construct($alss);
    }

    public function parse(string $fileName, string $logName)
    {
        \set_time_limit(0);

        /**
         * Number of buffered entries
         */
        $counter = 0;

        /**
         * Max buffered entries. Prevent PHP memory limit error
         */
        $maxEntries = 50000;
        
        $buffer = [];

        /**
         * Save log file details in DB
         */
        $log = new AccessLog();
        $log->name = $logName;
        $log->file_name = $fileName;
        $log->size = $this->storageService->getFileSize($fileName);

        $logSaveSuccess = $log->save();

         /**
         * Remove log file from filesystem if unable to persist it's info
         */
        if(!$logSaveSuccess) {
            $this->storageService->delete($fileName);
            return 'Unable to store log file data in DB';
        }

        /**
         * Open file handle
         */
        $fp = fopen($this->storageService->getFilePath($fileName), "r");

        /**
         * Unable to read file
         */
        if($fp === false) {
            return 'Can\'t open ' . $fileName . ' for read';
        }

        while(!feof($fp)) {
            try {
                $lineData = $this->parseLogLine(fgets($fp), $log->id);
            }
            catch(Exception $e) {
                $log->delete();
                return $e->getMessage();
            }
            /**
             * Log error string
             */
            if(is_string($lineData)) {
                Log::warning($lineData . ' in log: ' . $fileName);
                continue;
            }

            array_push($buffer, $lineData);
            $counter++;
            
            if($counter === $maxEntries) {
                $chunks = array_chunk($buffer, 1000);
                $buffer = [];
                foreach($chunks as $entriesChunk) {
                    AccessLogEntry::insert($entriesChunk);
                }
                $counter = 0;
            }
        }

        if(count($buffer) !== 0) {
            $chunks = array_chunk($buffer, 1000);
            $buffer = [];
            foreach($chunks as $entriesChunk) {
                AccessLogEntry::insert($entriesChunk);
            }
        }

        /**
         * Close file handle
         */
        fclose($fp);

        /**
         * Refresh model in order to get upload_time
         */
        $log->refresh();

        return [
            'name' => $log->name,
            'upload_time' => $log->upload_time
        ];
    }
}