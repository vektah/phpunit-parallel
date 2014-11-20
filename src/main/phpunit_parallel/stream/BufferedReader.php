<?php

namespace phpunit_parallel\stream;

use Evenement\EventEmitterInterface;
use React\Promise\Deferred;
use React\Promise\FulfilledPromise;

class BufferedReader
{
    private $buf;
    private $lines = [];
    private $promisedLines = [];

    /** @var callable */
    private $onLine;

    public function __construct(EventEmitterInterface $stream)
    {
        $this->lines = new \SplQueue();
        $this->promisedLines = new \SplQueue();

        $stream->on('data', function ($data) {
            $newline_count = substr_count($data, "\n");
            $this->buf .= $data;

            if ($newline_count > 0) {
                $lines = explode("\n", $this->buf);
                $this->buf = array_pop($lines);

                foreach ($lines as $line) {
                    $line .= "\n";

                    if ($this->onLine) {
                        $onLine = $this->onLine;
                        $onLine($line);
                    }

                    if ($this->promisedLines->count() > 0) {
                        $this->promisedLines->dequeue()->resolve($line);
                    } else {
                        $this->lines->enqueue($line);
                    }
                }
            }
        });
    }

    public function linesWaiting()
    {
        return count($this->lines);
    }

    public function onLine(callable $callable)
    {
        $this->onLine = $callable;
    }

    public function readline()
    {
        if (count($this->lines) > 0) {
            return new FulfilledPromise($this->lines->dequeue());
        }

        $deferred = new Deferred();
        $this->promisedLines->enqueue($deferred);
        return $deferred->promise();
    }

    public function readUntil(callable $function) {
        $done = new Deferred();

        $doOne = function($line) use ($function, $done, &$doOne) {
            $result = $function($line);
            if ($result !== null) {
                $done->resolve($result);
            } else {
                $this->readline()->then($doOne);
            }
        };

        $this->readline()->then($doOne);

        return $done->promise();
    }

    public function getAllWaiting()
    {
        $result = '';

        foreach ($this->lines as $line) {
            $result .= $line;

        }

        return $result;
    }
}
