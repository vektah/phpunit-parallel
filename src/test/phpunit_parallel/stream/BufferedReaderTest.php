<?php

namespace phpunit_parallel\stream;

use Evenement\EventEmitter;
use React\Promise\PromiseInterface;

class BufferedReaderTest extends \PHPUnit_Framework_TestCase
{
    private $promiseExpectations = [];

    public function testWaitingLines()
    {
        $stream = new EventEmitter();
        $buf = new BufferedReader($stream);
        $stream->emit('data', ["asdf\n"]);

        $this->assertPromiseYields("asdf\n", $buf->readline());
    }

    public function testPendingPromises()
    {
        $stream = new EventEmitter();
        $buf = new BufferedReader($stream);
        $this->assertPromiseYields("asdf\n", $buf->readline());

        $stream->emit('data', ["asdf\n"]);
    }

    public function testMixedWaitingAndPromises()
    {
        $stream = new EventEmitter();
        $buf = new BufferedReader($stream);
        $stream->emit('data', ["line1\n"]);
        $stream->emit('data', ["line2\n"]);


        $this->assertPromiseYields("line1\n", $buf->readline());
        $this->assertPromiseYields("line2\n", $buf->readline());
        $this->assertPromiseYields("line3\n", $buf->readline());
        $this->assertPromiseYields("line4\n", $buf->readline());

        $stream->emit('data', ["line3\n"]);
        $stream->emit('data', ["line4\n"]);
    }

    public function testMultipleCallsForOneLineWaiting()
    {
        $stream = new EventEmitter();
        $buf = new BufferedReader($stream);
        $stream->emit('data', ["as"]);
        $stream->emit('data', ["df\n"]);

        $this->assertPromiseYields("asdf\n", $buf->readline());
    }

    public function testMultipleCallsForOneLinePending()
    {
        $stream = new EventEmitter();
        $buf = new BufferedReader($stream);

        $this->assertPromiseYields("asdf\n", $buf->readline());

        $stream->emit('data', ["as"]);
        $stream->emit('data', ["df\n"]);
    }

    public function testMultipleLinesInOneCallWaiting()
    {
        $stream = new EventEmitter();
        $buf = new BufferedReader($stream);
        $stream->emit('data', ["line1\nline2\n"]);

        $this->assertPromiseYields("line1\n", $buf->readline());
        $this->assertPromiseYields("line2\n", $buf->readline());
    }

    public function testMultipleLinesInOneCallPending()
    {
        $stream = new EventEmitter();
        $buf = new BufferedReader($stream);

        $this->assertPromiseYields("line1\n", $buf->readline());
        $this->assertPromiseYields("line2\n", $buf->readline());

        $stream->emit('data', ["line1\nline2\n"]);
    }

    public function testMultipleLinesSplitAcrossWaitingPending()
    {
        $stream = new EventEmitter();
        $buf = new BufferedReader($stream);
        $stream->emit('data', ["line1\nlin"]);

        $this->assertPromiseYields("line1\n", $buf->readline());
        $this->assertPromiseYields("line2\n", $buf->readline());
        $this->assertPromiseYields("line3\n", $buf->readline());

        $stream->emit('data', ["e2\nline3\n"]);
    }

    public function testPendingThenWaiting()
    {
        $stream = new EventEmitter();
        $buf = new BufferedReader($stream);
        $this->assertPromiseYields("line1\n", $buf->readline());

        $stream->emit('data', ["line1\nline2\n"]);

        $this->assertPromiseYields("line2\n", $buf->readline());
    }

    public function tearDown()
    {
        foreach ($this->promiseExpectations as $expectation) {
            if (!array_key_exists('actual', $expectation)) {
                $this->fail("one or more promises never resolved");
            }

            $this->assertEquals($expectation['expected'], $expectation['actual']);
        }
    }

    private function assertPromiseYields($expected, PromiseInterface $promise) {
        $id = sizeof($this->promiseExpectations);
        $this->promiseExpectations[$id] = ['expected' => $expected];

        $promise->then(function($value) use ($id, $expected) {
            $this->promiseExpectations[$id]['actual'] = $value;
        });
    }
}
