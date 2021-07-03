<?php

namespace app\core;

class MemoryLogger extends Logger implements LoggerInterface {
    const FILE_NEW = 'w';
    const FILE_APPEND = 'a';
    
    private $mode;
    
    public function __construct($mode = self::FILE_NEW)
    {
        $this->clear();
        $this->mode = $mode;
    }

    public function clear()
    {
        $this->entries = [];
    }

    public function log($message = false, $messageType = self::LOG_INFO)
    {
        if (!$message) return false;
        $this->entries[] = [
            "type" => $messageType,
            "time" => $this->getCurrentTime(),
            "message" => $message
        ];
    }

    public function dumpToScreen($type = self::LOG_ALL)
    {
        echo $this->getLogContent($type);
        return $this;
    }

    public function getLogContent($type = self::LOG_ALL)
    {
        $buffer = '';
        for ($i = 0; $i < count($this->entries); $i++) {
            $log = $this->entries[$i];
            if (!($log['type'] & $type)) continue;
            if ($buffer != '') $buffer .= $this->newLine;
            $line = "[{$log['time']} - " . $this->getLogLevel($log['type']) . "]: {$log['message']}";
            $buffer .= $line;
        }
        return $buffer;
    }

    public function dumpToFile($file = false, $type = self::LOG_ALL)
    {
        if (!$file) throw new MemoryLogFileNotSpecifiedException();
        try {
            $file = $this->openFile($file);
            $this->writeToFile($file, $this->getLogContent($type));
            $this->closeFile($file);
        } catch (\Exception $e) {

        }
    }

    private function openFile($path)
    {
        try {
            return fopen($path, $this->mode);
        } catch (\Exception $e) {
            throw new MemoryLogCannotOpenFileException();
        }
    }

    private function writeToFile($handler, $data)
    {
        return fwrite($handler, $data);
    }

    private function closeFile($handler)
    {
        return fclose($handler);
    }

    private function fileExists($path)
    {
        return file_exists($path);
    }
}