<?php

namespace App\Services;

use App\Models\AccessLog;
use App\Models\AccessLogEntry;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\DB;

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
            return false;
        }
        return true;
    }

    /**
     * Get file name from log label
     * 
     * @param string $name
     * @return string|bool $fileName|false
     */
    public function getFilename(string $name)
    {
        $fileName = AccessLog::where('name', $name)->first('file_name')->toArray();
        if(isset($fileName)) {
            return $fileName['file_name'];
        }
        return false;
    }

    /**
     * Delete log and it's entries
     *
     * @param string $name
     * @return void
     */
    public function deleteLogByName(string $name)
    {
        // Fetch log data
        $log = AccessLog::where('name', $name)->first();
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
     * @param string $startDateTimeString
     * @param string $endDateTimeString
     * @return string aggregatedData
     */
    public function getAggregate(string $aggregateBy, string $startDateTimeString, string $endDateTimeString): string
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
        $query = DB::table('access_log_entries');
        // Add query start time condition if valid
        if($startDateTimeString !== '') {
            $startDateTime = Carbon::createFromFormat($dateTimeFormat, $startDateTimeString);
            if($startDateTime !== false) {
                $query = $query->whereDate('request_datetime', '>', $startDateTime);
            }
        }
        // Add query end time condition if valid
        if($endDateTimeString !== '') {
            $endDateTime = Carbon::createFromFormat($dateTimeFormat, $endDateTimeString);
            if($endDateTime !== false) {
                $query = $query->whereDate('request_datetime', '<', $endDateTime);
            }
        }
        $query = $query->select($toColumnLabel[$aggregateBy] . ' as ' . $toAlias[$aggregateBy], DB::raw('COUNT(id) as cnt'))
                    ->groupBy($aggregateBy);

        return $query->get()->toJson();
    }
}