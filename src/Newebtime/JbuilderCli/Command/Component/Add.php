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
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;

use Newebtime\JbuilderCli\Command\Base as BaseCommand;

class Add extends BaseCommand
{
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
	 * @@inheritdoc
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);

		$this->io->title('Add component');

		$name = $input->getArgument('name');

		//TODO: Check name (com_ + string)

		$componentPath = $this->basePath
			. $this->config->paths->src
			. $this->config->paths->components
			. $name;

		if (isset($this->config->components)
			&& array_key_exists($name, $this->config->components))
		{
			//TODO: Warning message, component already exists

			return;
		}
		elseif (is_dir($componentPath))
		{
			//TODO: Warning message, path already exists

			return;
		}

		$paths = [
			'backend'   => 'admin',
			'frontend'  => 'site',
			'media'     => 'media',
			'languages' => 'languages'
		];

		if (!$this->io->confirm("Use the default structure?"))
		{
			$backend  = $this->io->ask('Define the backend directory', $paths['backend']);
			$backend  = rtrim($backend, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

			$frontend = $this->io->ask('Define the frontend directory', $paths['frontend']);
			$frontend = rtrim($frontend, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

			$media    = $this->io->ask('Define the media directory', $paths['media']);
			$media    = rtrim($media, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

			$languages = $this->io->ask('Define the languages directory', $paths['languages']);
			$languages = rtrim($languages, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

			$paths = [
				'backend'   => $backend,
				'frontend'  => $frontend,
				'media'     => $media,
				'languages' => $languages
			];

			//TODO: Check paths
		}

		$this->io->note('The builder is working, please wait');

		$this->io->listing([
			'Generate component directories',
			'Generate component files',
			'Update package XML file',
			'Update project file'
		]);

		mkdir($componentPath);

		foreach ($paths as $path)
		{
			mkdir($componentPath . $path);
		}

		//TODO: Generate component files
			//TODO: component.xml and admin/fof.xml (MagicFactory)
			//TODO: admin/access.xml and admin/config.xml
			//TODO: admin/component.php and site/component.php

		$srcBase = $this->basePath . $this->config->paths->src;

		//TODO: Update package XML file (pkg_name)
		//TODO: Update project file

		$this->io->success('New component added');

		//TODO: Ask to symlink and add the component to the demo
	}
}
