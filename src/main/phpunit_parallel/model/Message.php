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
    const PREAMBLE = '!!(╯°□°）╯︵ ┻━┻!!';

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
        $preamble = substr($line, 0, strlen(self::PREAMBLE));

        // If the line does not start with the preamble then this is output from a test, or an error.
        if (!$line || $preamble !== self::PREAMBLE) {
            return null;
        }

        return static::fromArray(Json::decode(substr($line, strlen(self::PREAMBLE))));
    }

    abstract public function toArray();

    public function encode()
    {
        return self::PREAMBLE . Json::encode($this->toArray()) . "\n";
    }
}
