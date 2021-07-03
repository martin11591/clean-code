<?php

namespace app\core;

interface LoggerInterface {
    public function log($type, $message);
    public function clear();
}

abstract class Logger {
    const LOG_INFO = 0b0001;
    const LOG_WARNING = 0b0010;
    const LOG_CRITICAL = 0b0100;
    const LOG_ALL = 0b0111;
    private $logNames = [
        0b0001 => 'Info',
        0b0010 => 'Warning',
        0b0100 => 'Critical',
        0b0111 => 'All'
    ];
    
    public $newLine = PHP_EOL;

    public function resetTimeStamps()
    {
        $this->timeStamps = [];
    }

    public function saveTimeStamp()
    {
        $this->timeStamps[] = hrtime(true);
    }

    public function getDiffFromTimeStamps()
    {
        $start = array_pop($this->timeStamps);
        $stop = array_pop($this->timeStamps);
        return $this->calculateTimeStampsDiff($start, $stop);
    }

    public function getDiffFromCurrentTimeStamp()
    {
        if (count($this->timeStamps) < 1) $this->saveTimeStamp();
        $this->saveTimeStamp();
        return $this->getDiffFromTimeStamps();
    }

    private function calculateTimeStampsDiff($startTimeStamp, $stopTimeStamp)
    {
        return $this->formatTimeStampsDiff($startTimeStamp - $stopTimeStamp);
    }

    private function formatTimeStampsDiff($timeStampsDiff)
    {
        $ns = $timeStampsDiff % 1000;
        $timeStampsDiff = floor($timeStampsDiff / 1000);
        $us = $timeStampsDiff % 1000;
        $timeStampsDiff = floor($timeStampsDiff / 1000);
        $ms = $timeStampsDiff % 1000;
        $timeStampsDiff = floor($timeStampsDiff / 1000);
        $s = $timeStampsDiff % 60;
        $timeStampsDiff = floor($timeStampsDiff / 60);
        $m = $timeStampsDiff % 60;
        $timeStampsDiff = floor($timeStampsDiff / 60);
        $h = $timeStampsDiff % 24;
        $timeStampsDiff = floor($timeStampsDiff / 24);
        $d = $timeStampsDiff % 7;
        $w = floor($timeStampsDiff / 7);
        $timeStampsDiff = "{$ns} nanoseconds";
        if ($us > 0) $timeStampsDiff = "{$us} microseconds {$timeStampsDiff}";
        if ($ms > 0) $timeStampsDiff = "{$ms} milliseconds {$timeStampsDiff}";
        if ($s > 0) $timeStampsDiff = "{$s} seconds {$timeStampsDiff}";
        if ($m > 0) $timeStampsDiff = "{$m} minutes {$timeStampsDiff}";
        if ($h > 0) $timeStampsDiff = "{$h} hours {$timeStampsDiff}";
        if ($d > 0) $timeStampsDiff = "{$d} days {$timeStampsDiff}";
        if ($w > 0) $timeStampsDiff = "{$w} weeks {$timeStampsDiff}";
        return $timeStampsDiff;
    }

    protected function getLogLevel($type)
    {
        if (isset($this->logNames[$type])) return $this->logNames[$type];
        return $type;
    }

    protected function getCurrentTime()
    {
        $time = new \DateTime();
        return $time->format("Y-m-d H:i:s.u");
    }
}