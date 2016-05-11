<?php
/**
 * @package    JBuilder
 * @copyright  Copyright (c) 2003-2016 Frédéric Vandebeuque / Newebtime
 * @license    Mozilla Public License, version 2.0
 */

namespace Newebtime\JbuilderCli\Command\Component;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Newebtime\JbuilderCli\Command\Base as BaseCommand;

class Add extends BaseCommand
{
	/** @var \stdClass */
	protected $component;

	/**
	 * @@inheritdoc
	 */
	protected function configure()
	{
		$this
			->setName('component:add')
			->setDescription('Add a new component is the sources')
			->addArgument(
				'name',
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

		$this->io->title('Add component');

		$comName = $input->getArgument('name');
		$comName = strtolower($comName);

		if ('com_' !== substr($comName, 0, 4))
		{
			$this->io->warning('Action canceled, the name need to start by com_ (e.g. com_todo)');

			exit;
		};

		$name = substr($comName, 4);

		if (preg_replace('/[^A-Z_]/i', '', $name) != $name)
		{
			$this->io->warning('Action canceled, the name is not correct, you can use only A-Z and _ (e.g. com_to_do)');

			exit;
		}

		$path = $this->basePath
			. $this->config->paths->src
			. $this->config->paths->components
			. $comName
			. DIRECTORY_SEPARATOR;

		if (isset($this->config->components)
			&& array_key_exists($name, $this->config->components))
		{
			$this->io->warning('Action canceled, a component using the same name already exists');

			return;
		}
		elseif (is_dir($path))
		{
			$this->io->warning('Action canceled, a directory for this component already exists');

			return;
		}

		$this->io->comment([
			'ComName    ' . $comName,
			'Name       ' . $name,
			'Directory  ' . $path
		]);

		$this->component = (object) [
			'comName' => $comName,
			'name'    => $name,
			'path'    => $path,
			'paths'   => [
				'backend'   => 'admin/',
				'frontend'  => 'site/',
				'media'     => 'media/',
				'languages' => 'languages/'
			]
		];
	}

	/**
	 * @@inheritdoc
	 */
	protected function interact(InputInterface $input, OutputInterface $output)
	{
		$this->initIO($input, $output);

		if (!$this->io->confirm('Use the default structure?'))
		{
			$backend  = $this->io->ask('Define the backend directory', $this->component->paths['backend']);
			$backend  = rtrim($backend, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

			$frontend = $this->io->ask('Define the frontend directory', $this->component->paths['frontend']);
			$frontend = rtrim($frontend, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

			$media    = $this->io->ask('Define the media directory', $this->component->paths['media']);
			$media    = rtrim($media, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

			$languages = $this->io->ask('Define the languages directory', $this->component->paths['languages']);
			$languages = rtrim($languages, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

			$paths = [
				'backend'   => $backend,
				'frontend'  => $frontend,
				'media'     => $media,
				'languages' => $languages
			];

			//TODO: Check paths

			$this->component->paths = $paths;
		}
	}

	/**
	 * @@inheritdoc
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->initIO($input, $output);

		$this->io->note('The builder is working, please wait');

		$this->io->listing([
			'Generate component directories',
			'Generate component files',
			'Update package XML file',
			'Update project file'
		]);

		if (!mkdir($this->component->path))
		{
			$this->io->error([
				'Component directory creation failed',
				'It is not possible to create the component directory, please check your access level'
			]);

			return;
		}

		foreach ($this->component->paths as $path)
		{
			mkdir($this->component->path . $path);
		}

		$this->buildBackend();
		$this->buildFrontend();

		//TODO: component.xml with only the current generated xml and php files
		touch($this->component->path . $this->component->name . '.xml');

		$srcBase = $this->basePath . $this->config->paths->src;

		//TODO: Update package XML file (pkg_name)
		//TODO: Update project file

		$this->io->success('New component added');

		if (!$this->io->confirm('Install the component on the demo?'))
		{
			//TODO: Symlink and add the component to the demo
		}
	}

	public function buildBackend()
	{
		//TODO: fof.xml with factoryClass to MagicFactory
		touch($this->component->path . $this->component->paths['backend'] . 'fof.xml');
		//TODO: access.xml with basic component section
		touch($this->component->path . $this->component->paths['backend'] . 'access.xml');
		//TODO: config.xml with permissions fieldset
		touch($this->component->path . $this->component->paths['backend'] . 'config.xml');
		//TODO: <?php + include fof30 + check constante + call the container and dispath
		touch($this->component->path . $this->component->paths['backend'] . $this->component->name . '.php');
	}

	public function buildFrontend()
	{
		//TODO: <?php + include fof30 + check constante + call the container and dispath
		touch($this->component->path . $this->component->paths['frontend'] . $this->component->name . '.php');
	}
}
