<?php

namespace phpunit_parallel\dummy;

class SleepTest extends \PHPUnit_Framework_TestCase
{

    public static function provider()
    {
        $tests = [];
        for ($i = 0; $i < 2; $i++) {
            $tests[] = ['a'];
        }

        return $tests;
    }

    /**
     * @dataProvider provider
     */
    public function testSleeps()
    {
        sleep(1);
    }
}
