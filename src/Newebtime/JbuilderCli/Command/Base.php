<?php
/**
 * @package    JBuilderCli
 * @copyright  Copyright (c) 2003-2016 Frédéric Vandebeuque / Newebtime
 * @license    Mozilla Public License, version 2.0
 */

namespace Newebtime\JbuilderCli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
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

	/**
	 * Save a XML using DOMDocument to format output
	 *
	 * @param string $xml       The XML to save
	 * @param string $filePath  The path of the file
	 *
	 * @return bool
	 */
	protected function saveXML($xml, $filePath)
	{
		$domDocument = new \DOMDocument('1.0');
		$domDocument->loadXML($xml);
		$domDocument->preserveWhiteSpace = false;
		$domDocument->formatOutput = true;
		$xml = $domDocument->saveXML();

		if (!@file_put_contents($filePath, $xml))
		{
			$this->io->warning([
				'The XML file could not be created, please check',
				$filePath
			]);

			return false;
		}

		return true;
	}

	/**
	 * Detect if git is installed
	 * http://zurb.com/forrst/posts/Check_if_Git_is_installed_from_PHP-0E2
	 *
	 * @return bool|string
	 */
	protected function hasGit()
	{
		exec('which git', $output);

		$git = file_exists($line = trim(current($output))) ? $line : 'git';

		unset($output);

		exec($git . ' --version', $output);

		preg_match('#^(git version)#', current($output), $matches);

		return ! empty($matches[0]) ? $git : false;
	}
}
