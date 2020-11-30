<?php

namespace App\Services;

use App\Contracts\LogParserInterface;
use App\Models\AccessLog;
use Illuminate\Support\Facades\App;

class ProcessLogService
{
    private LogParserInterface $parser;

    private function setParser(LogParserInterface $lpi)
    {
        $this->parser = $lpi;
    }

    public function handle(string $fileName, string $logName)
    {
        $filePathArray = explode('.', $fileName);
        $extension = $filePathArray[count($filePathArray) - 1];

        if($extension === 'txt') {
            $this->setParser(App::make(\App\Services\TxtLogParseService::class));
        }
        else if($extension === 'gz') {
            $this->setParser(App::make(\App\Services\GzLogParseService::class));
        }
        else {
            return 'File not txt or gz';
        }

        return $this->parser->parse($fileName, $logName);
    }
}