<?php
/**
 * @package    JBuilder
 * @copyright  Copyright (c) 2003-2016 Frédéric Vandebeuque / Newebtime
 * @license    Mozilla Public License, version 2.0
 */

namespace Newebtime\JbuilderCli;

use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;

class Application extends \Symfony\Component\Console\Application
{
	/**
	 * newebtime/jbuilder version
	 *
	 * @var string
	 */
	const VERSION = '0.0.1';

	/**
	 * Application name
	 *
	 * @var string
	 */
	const NAME = 'Jbuilder Console tools';

	/**
	 * Reference to the Output\ConsoleOutput object
	 */
	protected $_output;

	/**
	 * Reference to the Input\ArgvInput object
	 */
	protected $_input;

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
		if (null === $input) {
			$input = new Input\ArgvInput();
		}

		if (null === $output) {
			$output = new Output\ConsoleOutput();
		}

		$this->_input  = $input;
		$this->_output = $output;

		$this->configureIO($this->_input, $this->_output);

		parent::run($this->_input, $this->_output);
	}

	/**
	 * Gets the default commands that should always be available.
	 *
	 * @return Command[] An array of default Command instances
	 */
	protected function getDefaultCommands()
	{
		$commands = parent::getDefaultCommands();

		$commands = array_merge($commands, [
			new Command\Project\Init(),
			new Command\Project\Install()
		]);

		return $commands;
	}
}
