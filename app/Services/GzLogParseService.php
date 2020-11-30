<?php

namespace App\Services;

use App\Contracts\LogParserInterface;
use App\Models\AccessLog;
use App\Models\AccessLogEntry;
use Illuminate\Support\Facades\Log;

class GzLogParseService extends BaseLogParseService implements LogParserInterface
{


    public function __construct(AccessLogStorageService $alss)
    {
        parent::__construct($alss);
    }

    /**
     * Parse compressed log file
     * 
     * Process and presist each log entry
     *
     * @param string $fileName
     * @param string $logName
     * @return string $parsingStatus
     */
    public function parse(string $fileName, string $logName)
    {
        // DEV
        $t1 = microtime(true);
        /**
         * Remove limit on max execution time
         */
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
        $zp = gzopen($this->storageService->getFilePath($fileName), "r");

        /**
         * Unable to read compressed file
         */
        if($zp === false) {
            return 'Can\'t open ' . $fileName . ' for read';
        }

        /**
         * Parse each log line
         */
        while(!gzeof($zp)) {
            $lineData = $this->parseLogLine(gzgets($zp), $log->id);
            /**
             * Check if error string
             */
            if(is_string($lineData)) {
                Log::warning($lineData . ' in log: ' . $fileName);
                continue;
            }

            array_push($buffer, $lineData);
            $counter++;
            
            if($counter === $maxEntries) {
                // $memorUsage1 =  memory_get_usage();
                $chunks = array_chunk($buffer, 1000);
                $buffer = [];
                // $tIns1 = microtime(true);
                foreach($chunks as $entriesChunk) {
                    AccessLogEntry::insert($entriesChunk);
                }
                // $insertTime = microtime(true) - $tIns1;
                // $memorUsage2 =  memory_get_usage();
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
        gzclose($zp);
        // Dev
        $totalTime = microtime(true) - $t1;
        
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