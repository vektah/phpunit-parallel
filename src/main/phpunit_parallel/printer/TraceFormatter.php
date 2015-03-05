<?php

namespace phpunit_parallel\printer;

// TODO: Move to vektah\common
class TraceFormatter
{
    private $stacktrace;

    public static function create(array $stacktrace)
    {
        return new TraceFormatter($stacktrace);
    }

    private function __construct(array $stacktrace)
    {
        $this->stacktrace = $stacktrace;
    }

    public function replace($term, $search, $replace)
    {
        foreach ($this->stacktrace as $id => $frame) {
            if (isset($frame[$term])) {
                $this->stacktrace[$id][$term] = str_replace($search, $replace, $frame[$term]);
            }
        }

        return $this;
    }

    public function wrapNotMatching($term, $pattern, $before, $after)
    {
        foreach ($this->stacktrace as $id => $frame) {
            if (!isset($frame[$term]) || !preg_match($pattern, $frame[$term])) {
                $this->stacktrace[$id]['wrap'] = [$before, $after];
            }
        }

        return $this;
    }

    public function printf($format)
    {
        $out = '';

        foreach ($this->stacktrace as $id => $frame) {
            if (isset($frame['function'])) {
                $call = isset($frame['class']) ? "{$frame['class']}::" : '';
                $call .= $frame['function'] . '()';
            } else {
                $call = '???()';
            }

            if (isset($frame['file'])) {
                $location = $frame['file'];
                if (isset($frame['line'])) {
                    $location .= ":{$frame['line']}";
                }
            } else {
                $location = '???';
            }

            $line = $this->sprintfNamed($format, [
                'file' => isset($frame['file']) ? $frame['file'] : '',
                'line' => isset($frame['line']) ? $frame['line'] : '',
                'call' => $call,
                'id' => $id,
                'location' => $location,
            ]);

            if (isset($frame['wrap'])) {
                $line = $frame['wrap'][0] . $line . $frame['wrap'][1];
            }

            $out .= "$line\n";
        }


        return $out;
    }

    private function sprintfNamed($format, array $vars)
    {
        $replaces = [];

        foreach (array_keys($vars) as $id => $key) {
            $replaces['{' . $key . '}'] = ($id + 1) . '$';
        }

        return vsprintf(strtr($format, $replaces), array_values($vars));
    }
}
