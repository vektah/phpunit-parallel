<?php

namespace phpunit_parallel\model;

class Error extends Message
{
    public $filename;
    public $message;
    public $line;
    public $severity;
    public $stacktrace;

    public function __construct($data = [])
    {
        if (is_string($data)) {
            $this->message = $data;
            return;
        }

        foreach ($data as $key => $value) {
            if (!property_exists($this, $key)) {
                throw new \InvalidArgumentException("$key does not exist");
            }

            $this->$key = $value;
        }
    }

    public function getFormatted()
    {
        $formatted = $this->filename;

        if ($this->line) {
            $formatted = "$formatted:{$this->line}";
        }

        $formatted = "$formatted {$this->message}";

        if ($this->stacktrace) {
            $formatted .= "\n{$this->stacktrace}";
        }

        return $formatted;
    }

    public static function formatter($e)
    {
        if (is_string($e)) {
            return $e;
        }
        return $e->getFormatted();
    }

    public function toArray()
    {
        return [
            'filename' => $this->filename,
            'message' => $this->message,
            'line' => $this->line,
            'severity' => $this->severity,
            'stacktrace' => $this->stacktrace,
        ];
    }
}
