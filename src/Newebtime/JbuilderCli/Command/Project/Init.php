<?php
/**
 * @package    JBuilder
 * @copyright  Copyright (c) 2003-2016 Frédéric Vandebeuque / Newebtime
 * @license    Mozilla Public License, version 2.0
 */

namespace Newebtime\JbuilderCli\Command\Project;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class Init extends Command
{
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
	 * @@inheritdoc
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$path = $input->getArgument('path');

		if (!$path)
		{
			$path = getcwd();
		}

		$path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

		if (!is_dir($path))
		{
			$output->writeln("\n<error>This directory does not exists, please check</error>\n<comment>$path</comment>\n");

			return;
		}

		if (file_exists($path . '.jbuilder'))
		{
			$output->writeln("\n<error>This directory has already been init</error>\n<comment>$path</comment>\n");

			return;
		}

		$output->writeln("\n<info>Init a project inside</info>\n$path\n");

		$helper = $this->getHelper('question');

		$question = new Question("<question>Define the sources directory</question> [src]\n", 'src');
		$src      = $helper->ask($input, $output, $question);
		$src      = rtrim($src, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

		$question      = new Question("<question>Define the components directory</question> [${src}components]\n", $src . 'components');
		$srcComponents = $helper->ask($input, $output, $question);
		$srcComponents = rtrim($srcComponents, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

		$question     = new Question("<question>Define the libraries directory</question> [${src}libraries]\n", $src . 'libraries');
		$srcLibraries = $helper->ask($input, $output, $question);
		$srcLibraries = rtrim($srcLibraries, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

		$question = new Question("<question>Define the Joomla website directory</question> [demo]\n", 'demo');
		$srcDemo  = $helper->ask($input, $output, $question);
		$srcDemo  = rtrim($srcDemo, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

		$question = new ConfirmationQuestion("<question>Add the demo in .gitignore?</question> [y]\n");

		$ignoreDemo = $helper->ask($input, $output, $question);

		$output->writeln("<info>Create directories</info>");

		mkdir($path . $src);
		mkdir($path . $srcComponents);
		mkdir($path . $srcLibraries);

		if (is_dir($path . $srcDemo))
		{
			$output->writeln("<comment>Demo directory already exists, skip</comment> [$path . $srcDemo]");
		}
		else
		{
			mkdir($path . $srcDemo);
		}

		touch($path . 'README.md');

		if ($ignoreDemo)
		{
			file_put_contents($path . '.gitignore', $srcDemo);
		}

		$jbuilder = (object) [
			'paths' => [
				'src'        => $src,
				'components' => $srcComponents,
				'libraries'  => $srcLibraries,
				'demo'       => $srcDemo
			]
		];

		file_put_contents($path . '.jbuilder', json_encode($jbuilder));
	}
}
