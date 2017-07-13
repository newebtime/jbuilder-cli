<?php
/**
 * @package    JBuilderCli
 * @copyright  Copyright (c) 2003-2017 Frédéric Vandebeuque / Newebtime
 * @license    Mozilla Public License, version 2.0
 */

namespace Newebtime\JbuilderCli;

use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;

class Application extends \Symfony\Component\Console\Application
{
    /**
     * newebtime/jbuilder-cli version
     *
     * @var string
     */
    const VERSION = '1.1.0';

    /**
     * Application name
     *
     * @var string
     */
    const NAME = 'Jbuilder Console tools';

    /**
     * @inheritdoc
     */
    public function __construct($name = 'UNKNOWN', $version = 'UNKNOWN')
    {
        parent::__construct(self::NAME, self::VERSION);
    }

    /**
     * @inheritdoc
     */
    public function run(Input\InputInterface $input = null, Output\OutputInterface $output = null)
    {
        parent::run($input, $output);
    }

    /**
     * @inheritdoc
     */
    protected function getDefaultCommands()
    {
        $commands = parent::getDefaultCommands();

        $commands = array_merge($commands, [
            new Command\Project\Init(),
            new Command\Project\Install(),
            new Command\Component\Add(),
            new Command\Component\Entity(),
        ]);

        return $commands;
    }
}
