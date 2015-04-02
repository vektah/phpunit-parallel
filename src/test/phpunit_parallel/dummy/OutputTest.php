<?php

namespace phpunit_parallel\dummy;

class OutputTest extends \PHPUnit_Framework_TestCase
{
    public function testFwrite()
    {
        fwrite(STDOUT, "[---A---]");
    }

    public function testEcho()
    {
        echo "[---B---]";
    }
}
