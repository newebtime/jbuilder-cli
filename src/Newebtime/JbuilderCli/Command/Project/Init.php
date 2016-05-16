<?php
/**
 * @package    JBuilderCli
 * @copyright  Copyright (c) 2003-2016 Frédéric Vandebeuque / Newebtime
 * @license    Mozilla Public License, version 2.0
 */

namespace Newebtime\JbuilderCli\Command\Project;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Newebtime\JbuilderCli\Command\Base as BaseCommand;
use Newebtime\JbuilderCli\Exception\OutputException;

class Init extends BaseCommand
{
	protected $ignoreDemo;

	/**
	 * @@inheritdoc
	 */
	protected function configure()
	{
		$this
			->setName('project:init')
			->setDescription('Init a new development project in the directory')
			->addArgument(
				'path',
				InputArgument::OPTIONAL,
				'The path of the directory'
			);
	}

	/**
	 * @inheritdoc
	 */
	protected function initialize(InputInterface $input, OutputInterface $output)
	{
		try
		{
			$this->initIO($input, $output);

			$this->io->title('Init project');

			$path = $input->getArgument('path');

			if (!$path)
			{
				$path = $this->basePath;
			}

			$path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

			if (!is_dir($path))
			{
				throw new OutputException([
					'This directory does not exists, please check',
					$path
				], 'error');
			}

			if (file_exists($path . '.jbuilder'))
			{
				throw new OutputException([
					'This directory has already been init',
					$path
				], 'warning');
			}

			$this->basePath = $path;
		}
		catch (OutputException $e)
		{
			$type = $e->getType();

			$this->io->$type($e->getMessages());

			exit;
		}
		catch (\Exception $e)
		{
			$this->io->error($e->getMessage());

			exit;
		}
	}

	/**
	 * @@inheritdoc
	 */
	protected function interact(InputInterface $input, OutputInterface $output)
	{
		$this->io->section('Project configuration');

		$name = $this->io->ask('What is the package name?', 'myproject');

		//TODO: Rework
		$src = 'src/';
		$srcComponents = 'components/';
		$srcLibraries = 'libraries/';
		$srcDemo = 'demo/';

		if (!$this->io->confirm('Use the default structure?'))
		{
			$src = $this->io->ask('Define the sources directory', 'src');
			$src = rtrim($src, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

			$srcComponents = $this->io->ask('Define the components directory (relative to the sources directory)', 'components');
			$srcComponents = rtrim($srcComponents, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

			$srcLibraries = $this->io->ask('Define the libraries directory (relative to the sources directory)', 'libraries');
			$srcLibraries = rtrim($srcLibraries, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

			$srcDemo = $this->io->ask('Define the Joomla website directory', 'demo');
			$srcDemo = rtrim($srcDemo, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		}

		$this->ignoreDemo = $this->io->confirm('Add the demo in .gitignore?');

		$this->io->comment([
			'Sources    ' . $src,
			'Components ' . $src . $srcComponents,
			'Libraries  ' . $src . $srcLibraries,
			'Demo       ' . $srcDemo
		]);

		$this->config = (object) [
			'name'  => $name,
			'paths' => [
				'src'        => $src,
				'components' => $srcComponents,
				'libraries'  => $srcLibraries,
				'demo'       => $srcDemo
			]
		];
	}

	/**
	 * @@inheritdoc
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		try
		{
			$this->io->section('Project creation');

			$path = $this->basePath;

			$mkPaths = [
				$path . $this->config->paths->src,
				$path . $this->config->paths->src . $this->config->paths->components,
				$path . $this->config->paths->src . $this->config->paths->libraries,
				$path . $this->config->paths->demo
			];

			foreach ($mkPaths as $mkPath)
			{
				if (is_dir($mkPath))
				{
					$this->io->note([
						'Skip directory creation, this directory already exists',
						$mkPath
					]);
				}
				elseif (!@mkdir($mkPath))
				{
					throw new OutputException([
						'Something wrong happened during the creation po the directory',
						$mkPath
					], 'error');
				}
			}

			if (!@touch($path . 'README.md'))
			{
				$this->io->warning([
					'The README.md could not be created',
					$path . 'README.md'
				]);
			}

			if ($this->ignoreDemo
				&& !@file_put_contents($path . '.gitignore', $this->config->paths->demo))
			{
				$this->io->warning([
					'The .gitignore could not be created',
					$path . 'README.md'
				]);
			}

			if (!@file_put_contents($path . '.jbuilder', json_encode($this->config, JSON_PRETTY_PRINT)))
			{
				throw new OutputException([
					'Action canceled, the builder file cannot be created, please check.',
					$path . '.jbuilder'
				], 'error');
			}

			$this->createPackageXml();
		}
		catch (OutputException $e)
		{
			$type = $e->getType();

			$this->io->$type($e->getMessages());

			exit;
		}
		catch (\Exception $e)
		{
			$this->io->error($e->getMessage());

			exit;
		}
	}

	public function createPackageXml()
	{
		$xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><extension></extension>');

		$xml->addAttribute('type', 'package');
		$xml->addAttribute('version', '3.3.6');
		$xml->addAttribute('method', 'upgrade');

		$xml->addChild('name', $this->config->name);
		$xml->addChild('creationDate', date('Y-m-d'));
		$xml->addChild('packagename', $this->config->name);
		$xml->addChild('version', '0.0.1');

		$fof = $xml
			->addChild('files')
				->addChild('folder', $this->config->paths->libraries . 'fof');

		$fof->addAttribute('type', 'library');
		$fof->addAttribute('id', 'fof30');

		$this->saveXML($xml->asXML(), $this->basePath . $this->config->paths->src . 'pkg_' . $this->config->name . '.xml');
	}
}
