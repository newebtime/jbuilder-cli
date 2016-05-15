<?php
/**
 * @package    JBuilderCli
 * @copyright  Copyright (c) 2003-2016 Frédéric Vandebeuque / Newebtime
 * @license    Mozilla Public License, version 2.0
 */

namespace Newebtime\JbuilderCli\Command\Component;

use Joomlatools\Console\Joomla\Bootstrapper;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Newebtime\JbuilderCli\Command\Base as BaseCommand;

class AbstractComponent extends BaseCommand
{
	/** @var \stdClass */
	protected $component;

	/**
	 * @@inheritdoc
	 */
	protected function configure()
	{
		$this
			->addArgument(
				'component',
				InputArgument::REQUIRED,
				'The component name (e.g. com_todo)'
			);
	}

	/**
	 * @inheritdoc
	 */
	protected function initialize(InputInterface $input, OutputInterface $output)
	{
		$this->initIO($input, $output);

		$comName = $input->getArgument('component');

		//TODO: Component need to be optional if we have only 1 (except for component:add)
		//TODO: If we have many we need a way to define the current working one

		if (!$comName
			&& $input->isInteractive())
		{
			$comName = $this->io->ask('No component name given, please enter the component name (e.g. com_todo)');
		}

		$comName = strtolower($comName);

		if ('com_' !== substr($comName, 0, 4))
		{
			$this->io->warning('Action canceled, the name need to start by com_ (e.g. com_todo)');

			exit;
		};

		$name = substr($comName, 4);

		if (preg_replace('/[^A-Z_]/i', '', $name) != $name
			|| empty($name))
		{
			$this->io->warning('Action canceled, the component name is not correct, you can use only A-Z and _ (e.g. com_to_do)');

			exit;
		}

		$input->setArgument('component', $comName);

		if ('component:add' == $this->getName())
		{
			$this->component = (object) [
				'comName' => $comName,
				'name'    => $name,
				'paths'   => [
					'backend'  => 'admin/',
					'frontend' => 'site/',
					'media'    => 'media/',
					'language' => 'language/'
				]
			];
		}
		else
		{
			$this->initComponent($comName);
		}

		Bootstrapper::getApplication($this->basePath . $this->config->paths->demo);
	}

	public function initComponent($name)
	{
		if (!isset($this->config->components)
			|| !array_key_exists($name, $this->config->components))
		{
			$this->io->warning([
				'Action canceled, ' . $name . ' is not present in your project'
			]);

			$this->io->note([
				'You can use the following command to add this componant to your project',
			]);

			$this->io->text([
				'jbuilder component:add ' . $name,
			]);

			$this->io->newLine();

			exit;
		}

		$this->component = $this->config->components->{$name};

		return $this;
	}
}
