<?php

namespace App\Services;

use DateTime;
use Illuminate\Support\Facades\Validator;

abstract class BaseLogParseService
{
    protected AccessLogStorageService $storageService;
    protected array $httpMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'CONNECT', 'OPTIONS', 'TRACE'];
    protected string $logDateTimeFormat = 'd/M/Y:H:i:s';
    protected string $targetDateTimeFormat = 'Y-m-d H:i:s';
    protected array $validationRules;

    public function __construct(AccessLogStorageService $alss)
    {
        $this->storageService = $alss;
        $this->validationRules = [
            'access_log_id' => 'required|integer',
            'ip_address' =>  'required|ip',
            'http_method' => 'required|string',
            'url' => 'required|string',
            'request_datetime' => 'required|date_format:' . $this->targetDateTimeFormat
        ];
    }

    /**
     * Parse and validate each line
     * 
     * Return error string on line parsing fail or array with desired data on success
     *
     * @param string $line
     * @param integer $logID
     * @return array|string $logEntry | failure cause
     */
    protected function parseLogLine(string $line, int $logID)
    {
        $lineArray = explode(' ', $line);

        $ip = $lineArray[0];
        $dateTimeString = str_replace('[', '', $lineArray[3]);
        $dateTime = DateTime::createFromFormat($this->logDateTimeFormat, $dateTimeString);
        $method = strtoupper(str_replace('"', '', $lineArray[5]));
        $urlWithLeadingSlash = $method === 'GET' ? explode('?', $lineArray[6])[0] : $lineArray[6];

        if($dateTime === false) {
            return 'Unable to parse log entry datetime string';
        }

        if(!$this->isMethodValid($method)) {
            return 'Invalid method in request: ' . $line;
        }

        $logEntry = [
            'access_log_id' => $logID,
            'ip_address' => $ip,
            'http_method' => $method,
            'url' => $this->urlWithoutLeadingSlash($urlWithLeadingSlash),
            'request_datetime' => $dateTime->format($this->targetDateTimeFormat)
        ];

        $validator = Validator::make($logEntry, $this->validationRules);

        if($validator->fails()) {
            return $validator->errors()->first();
        }

        return $logEntry;

    }

    protected function urlWithoutLeadingSlash(string $urlWithLeadingSlash): string
    {
        if($urlWithLeadingSlash[0] === '/') {
            return $urlWithLeadingSlash;
        }
        return substr($urlWithLeadingSlash, 1);
    }

    private function isMethodValid(string $method)
    {
        return in_array($method, $this->httpMethods, true);
    }
}
