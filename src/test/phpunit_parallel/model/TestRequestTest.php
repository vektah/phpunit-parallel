<?php

namespace phpunit_parallel\model;

class TestRequestTest extends \PHPUnit_Framework_TestCase
{
    public function testEncodeDecode()
    {
        $request = new TestRequest(1, 'a', 'b', 'c');
        $recodedRequest = TestRequest::decode($request->encode());

        $this->assertEquals($recodedRequest, $request);
    }
}
