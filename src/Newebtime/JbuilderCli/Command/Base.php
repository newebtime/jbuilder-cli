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

class Base extends Command
{
	/**
	 * @@inheritdoc
	 */
	protected function configure()
	{
		$this
			->setName('optionName')
			->setDescription('Option description')
			->addOption(
				'option-1',
				null,
				InputOption::VALUE_NONE,
				'Description option-1'
			)
			->addOption(
				'option-2',
				null,
				InputOption::VALUE_NONE,
				'Description option-2'
			);
	}

	/**
	 * @@inheritdoc
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		if ($input->getOption('option-1')) {
			$this->option1Method();
		}

		if ($input->getOption('option-2')) {
			$this->option2Method();
		}
	}

	public function option1Method()
	{
		//
	}

	public function option2Method()
	{
		//
	}
}