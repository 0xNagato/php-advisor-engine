<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait MethodLogger
{
    protected function logMethodExecution($methodName, $startTime): void
    {
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        Log::info("Method $methodName executed in $executionTime ms");
    }
}
