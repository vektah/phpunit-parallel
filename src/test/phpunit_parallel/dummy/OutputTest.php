<?php

namespace phpunit_parallel\dummy;

class OutputTest extends \PHPUnit_Framework_TestCase
{
    public function testEcho()
    {
        fwrite(STDOUT, " ");
    }
}
