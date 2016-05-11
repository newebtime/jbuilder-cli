<?php
/**
 * @package    JBuilder
 * @copyright  Copyright (c) 2003-2016 Frédéric Vandebeuque / Newebtime
 * @license    Mozilla Public License, version 2.0
 */

namespace Newebtime\JbuilderCli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Base extends Command
{
	/**
	 * Reference to the SymfonyStyle object
	 *
	 * @var SymfonyStyle
	 */
	protected $io;

	protected $config;

	protected $basePath;

	/**
	 * @inheritdoc
	 */
	public function __construct($name = null)
	{
		parent::__construct($name);

		$path = getcwd();
		$path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

		$this->basePath = $path;

		$this->initConfig();
	}

	public function initConfig()
	{
		$configPath = $this->basePath . '.jbuilder';

		if (file_exists($configPath))
		{
			$this->config = json_decode(file_get_contents($configPath));
		}

		return $this;
	}

	protected function initIO(InputInterface $input, OutputInterface $output)
	{
		if (!isset($this->io))
		{
			$this->io = new SymfonyStyle($input, $output);
		}
	}
}
