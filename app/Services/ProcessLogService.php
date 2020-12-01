<?php

namespace App\Services;

use App\Contracts\LogParserInterface;
use App\Models\AccessLog;
use Illuminate\Support\Facades\App;

/**
 * Service class responsible for dispatching correct parser based on file extension
 */
class ProcessLogService
{
    private LogParserInterface $parser;

    /**
     * Set parser implementation
     *
     * @param LogParserInterface $lpi
     * @return void
     */
    private function setParser(LogParserInterface $lpi)
    {
        $this->parser = $lpi;
    }

    /**
     * Run log parser on file
     *
     * @param string $fileName
     * @param string $logName
     * @return void
     */
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