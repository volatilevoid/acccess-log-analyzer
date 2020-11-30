<?php

namespace App\Contracts;

interface LogParserInterface
{
    public function parse(string $path, string $logName);
}