<?php

namespace phpunit_parallel\model;

use vektah\common\json\Json;

/**
 * A message containing a preamble terminated by a newline. This allows
 * us to easily distinguish between test output/fatal errors and test
 * results being sent.
 */
abstract class Message
{
    /**
     * @return static
     */
    public static function fromArray(array $data) {
        return new static($data);
    }

    /**
     * @return static
     */
    public static function decode($line)
    {
        return static::fromArray(Json::decode($line));
    }

    abstract public function toArray();

    public function encode()
    {
        return Json::encode($this->toArray()) . "\n";
    }
}
