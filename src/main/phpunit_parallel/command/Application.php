<?php

namespace phpunit_parallel\command;

use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Input\InputInterface;

class Application extends SymfonyApplication
{
    protected function getCommandName(InputInterface $input)
    {
        return 'phpunit-parallel';
    }

    protected function getDefaultCommands()
    {
        $defaultCommands = parent::getDefaultCommands();

        $defaultCommands[] = new PhpunitParallel();

        return $defaultCommands;
    }

    public function getDefinition()
    {
        $inputDefinition = parent::getDefinition();
        // clear out the normal first argument, which is the command name
        $inputDefinition->setArguments();

        return $inputDefinition;
    }
}
