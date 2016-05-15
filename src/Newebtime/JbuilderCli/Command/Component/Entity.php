<?php
/**
 * @package    JBuilderCli
 * @copyright  Copyright (c) 2003-2016 Frédéric Vandebeuque / Newebtime
 * @license    Mozilla Public License, version 2.0
 */

namespace Newebtime\JbuilderCli\Command\Component;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Entity extends AbstractComponent
{
	protected $entity;

	/**
	 * @@inheritdoc
	 */
	protected function configure()
	{
		parent::configure();

		$this
			->setName('component:entity')
			->setDescription('Generate a new component entity')
			->addOption(
				'name',
				null,
				InputOption::VALUE_REQUIRED,
				'The new entity name'
			)
			->addOption(
				'frontend',
				null,
				InputOption::VALUE_NONE,
				'Generate only the frontend'
			)
			->addOption(
				'backend',
				null,
				InputOption::VALUE_NONE,
				'Generate only the backend'
			);
	}

	/**
	 * @inheritdoc
	 */
	protected function initialize(InputInterface $input, OutputInterface $output)
	{
		parent::initialize($input, $output);

		//TODO: Check
		include_once JPATH_PLATFORM . '/fof30/include.php';

		$this->io->title('Generate new entity');

		$name  = $input->getOption('name');

		if (!$name
			&& $input->isInteractive())
		{
			$name = $this->io->ask('No entity name given, please enter the entity name (e.g. todos)');
		}

		if (preg_replace('/[^A-Z_]/i', '', $name) != $name
			|| empty($name))
		{
			$this->io->warning('Action canceled, the name is not correct, you can use only A-Z and _ (e.g. todos)');

			exit;
		}

		$this->entity = $name;
	}

	/**
	 * @@inheritdoc
	 */
	protected function interact(InputInterface $input, OutputInterface $output)
	{
		//
	}

	/**
	 * @@inheritdoc
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$sections = ['admin', 'site'];

		if ($input->getOption('frontend'))
		{
			$sections = ['site'];
		}
		elseif ($input->getOption('backend'))
		{
			$sections = ['admin'];
		}

		$this->generateController($sections);

		$this->generateLayouts($sections);

		$this->generateModel($sections);

		$this->generateView($sections);

		//TODO: Allow to create Non database aware controller and model (not possible using FOF scaffolding)
		//TODO: Simple database table creation? Or import SQL file ? Uhm
	}

	protected function generateController($sections)
	{
		$view = $this->entity;

		foreach ($sections as $section)
		{
			// Let's force the use of the Magic Factory
			$config = ['factoryClass' => 'FOF30\\Factory\\MagicFactory'];

			$container = \FOF30\Container\Container::getInstance($this->component->comName, $config);
			$container->factory->setSaveScaffolding(true);

			// plural / singular
			$view = $container->inflector->singularize($view);

			$classname = $container->getNamespacePrefix($section) . 'Controller\\' . ucfirst($view);

			$scaffolding = new \FOF30\Factory\Scaffolding\Controller\Builder($container);
			$scaffolding->setSection($section);

			if(!$scaffolding->make($classname, $view))
			{
				throw new \RuntimeException("An error occurred while creating the Controller class");
			}
		}
	}

	protected function generateLayouts($sections)
	{
		//TODO: Generate Layouts
	}

	protected function generateModel($sections)
	{
		$view = $this->entity;

		foreach ($sections as $section)
		{
			// Let's force the use of the Magic Factory
			$config = ['factoryClass' => 'FOF30\\Factory\\MagicFactory'];

			// Let's force the use of the Magic Factory
			$container = \FOF30\Container\Container::getInstance($this->component->comName, $config);
			$container->factory->setSaveScaffolding(true);

			// plural / singular
			$view = $container->inflector->pluralize($view);

			$classname = $container->getNamespacePrefix($section) . 'Model\\' . ucfirst($view);

			$scaffolding = new \FOF30\Factory\Scaffolding\Model\Builder($container);
			$scaffolding->setSection($section);

			if(!$scaffolding->make($classname, $view))
			{
				throw new \RuntimeException("An error occurred while creating the Model class");
			}
		}
	}

	protected function generateView($sections)
	{
		$view = $this->entity;

		foreach ($sections as $section)
		{
			// Let's force the use of the Magic Factory
			$config = ['factoryClass' => 'FOF30\\Factory\\MagicFactory'];

			// Let's force the use of the Magic Factory
			$container = \FOF30\Container\Container::getInstance($this->component->comName, $config);
			$container->factory->setSaveScaffolding(true);

			// plural / singular
			$view = $container->inflector->pluralize($view);

			$classname = $container->getNamespacePrefix($section) . 'View\\' . ucfirst($view) . '\\Html';

			$scaffolding = new \FOF30\Factory\Scaffolding\View\Builder($container);
			$scaffolding->setSection($section);

			if(!$scaffolding->make($classname, $view, 'html'))
			{
				throw new \RuntimeException("An error occurred while creating the Model class");
			}
		}
	}
}
