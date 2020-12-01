<?php

namespace App\Services;

use App\Models\AccessLog;
use App\Models\AccessLogEntry;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\DB;

use function GuzzleHttp\Promise\queue;

/**
 * Service class for communication with database
 */
class AccessLogDataService
{
    /**
     * Get all log details
     *
     * @return void
     */
    public function allLogs(): string
    {
        return AccessLog::all('name', 'upload_time', 'size')->toJson();
    }

    /**
     * Check if name is available
     *
     * @param string $name
     * @return boolean
     */
    public function isNameAvailable(string $name): bool
    {
        $result = AccessLog::where('name', $name)->get();
        if($result->isEmpty()) {
            return true;
        }
        return false;
    }

    /**
     * Get file name from log label
     * 
     * @param string $name
     * @return string|bool $filePath|false
     */
    public function getFilePath(string $name)
    {
        $filePath = AccessLog::where('name', $name)->first('file_path')->toArray();
        if(isset($filePath['file_path'])) {
            return $filePath['file_path'];
        }
        return false;
    }

    /**
     * Delete log and it's entries
     *
     * @param string $name
     * @return void
     */
    public function deleteByName(string $name)
    {
        // Fetch log data
        $log = AccessLog::where('name', $name)->first();
        // Disable log entries for reading
        $log->is_enabled = false;
        $log->save();
        // Delete all entries 
        AccessLogEntry::where('access_log_id', $log->id)->delete();
        // Delete log
        $log->delete();
    }

    /**
     * Fetch aggregated data 
     * 
     * Get client ip, http method or url aggregated data
     * from given timespan
     *
     * @param string $aggregateBy 
     * @param string $name 
     * @param string $startDateTimeString
     * @param string $endDateTimeString
     * @return string aggregatedData
     */
    public function getAggregate(string $aggregateBy, string $name, string $startDateTimeString, string $endDateTimeString): string
    {
        $dateTimeFormat = 'Y-m-d H:i:s';
        // Convert parameter to column label
        $toColumnLabel = [
            'ip' => 'ip_address',
            'url' => 'url',
            'method' => 'http_method'
        ];
        // Convert parameter to column alias
        $toAlias = [
            'ip' => 'ip',
            'url' => 'url',
            'method' => 'method'
        ];

        // Begin querry
        $query = DB::table('access_log_entries as ale')
                        ->join('access_logs as al', 'ale.access_log_id', '=', 'al.id')
                        ->where('al.is_enabled', '=', true);

        // Return aggregate from all logs if log name not specified
        if($name !== '') {
            $log = AccessLog::where('name', $name)
            ->where('is_enabled', true)
            ->first();
            // Add query log name condition
            if($log && $log->is_enabled) {
                $query = $query->where('ale.access_log_id', '=', $log->id);
            }

        }

        // Time span. Prevent double where date "where" condition
        if($startDateTimeString !== '' && $endDateTimeString !== '') {
            $startDateTime = Carbon::createFromFormat($dateTimeFormat, $startDateTimeString);
            $endDateTime = Carbon::createFromFormat($dateTimeFormat, $endDateTimeString);
            $query = $query->whereDate('ale.request_datetime', '=', $startDateTime->toDateString());
            if($startDateTime !== false) {
                $query = $query->whereTime('ale.request_datetime', '>=', $startDateTime->toTimeString());
            }
            if($endDateTime !== false) {
                $query = $query->whereTime('ale.request_datetime', '<=', $endDateTime->toTimeString());
            }
        }
        // Only one of the datetime conditions
        else {
            // Add query start time condition
            if($startDateTimeString !== '') {
                $startDateTime = Carbon::createFromFormat($dateTimeFormat, $startDateTimeString);
                if($startDateTime !== false) {
                    $query = $query->whereDate('ale.request_datetime', '=', $startDateTime->toDateString())
                                        ->whereTime('ale.request_datetime', '>=', $startDateTime->toTimeString());
                }
            }
            // Add query end time condition
            if($endDateTimeString !== '') {
                $endDateTime = Carbon::createFromFormat($dateTimeFormat, $endDateTimeString);
                if($endDateTime !== false) {
                    $query = $query->whereDate('ale.request_datetime', '=', $endDateTime->toDateString())
                                        ->whereTime('ale.request_datetime', '<=', $endDateTime->toTimeString());
                }
            }
        }
        $query = $query->select($toColumnLabel[$aggregateBy] . ' as ' . $toAlias[$aggregateBy], DB::raw('COUNT(ale.id) as cnt'))
        ->groupBy($aggregateBy);

        return $query->get()->toJson();
    }
}