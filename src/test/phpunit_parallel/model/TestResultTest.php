<?php

namespace phpunit_parallel\model;

class TestResultTest extends \PHPUnit_Framework_TestCase
{
    public function testEncodeDecode()
    {
        $request = new TestResult([
            'testId' =>1,
            'class' => 'a',
            'name' => 'b',
            'filename' => 'c',
            'elapsed' => 0.1,
            'memoryUsed' => 100,
            'errors' => [new Error()],
            'risky' => true,
            'incomplete' => true,
            'skipped' => true
        ]);

        $recodedRequest = TestResult::decode($request->encode());

        $this->assertEquals($recodedRequest, $request);
    }
}
