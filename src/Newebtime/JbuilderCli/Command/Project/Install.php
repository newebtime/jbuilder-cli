<?php
/**
 * @package    JBuilder
 * @copyright  Copyright (c) 2003-2016 Frédéric Vandebeuque / Newebtime
 * @license    Mozilla Public License, version 2.0
 */

namespace Newebtime\JbuilderCli\Command\Project;

use Joomlatools\Console\Command\Extension\Install as ExtensionInstall;
use Joomlatools\Console\Command\Site\Download as SiteDownload;
use Joomlatools\Console\Command\Site\Install as SiteInstall;
use Joomlatools\Console\Command\Versions as Versions;
use Joomlatools\Console\Joomla\Bootstrapper;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Install extends Command
{
	protected $config;

	/**
	 * @@inheritdoc
	 */
	protected function configure()
	{
		$this
			->setName('project:install')
			->setDescription('Download and install the dependency for the project (Joomla, FOF)');
	}

	/**
	 * @@inheritdoc
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$path = getcwd();
		$path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

		$this->config = json_decode(file_get_contents($path . '.jbuilder'));

		$this->installJoomla($input, $output);
		$this->installFof($input, $output);

		$this->installPackage($input, $output);
	}

	public function installJoomla(InputInterface $input, OutputInterface $output)
	{
		$arguments = [
			'site:download',
			'site'      => $this->config->paths->demo,
			'--refresh' => true,
			'--www'     => getcwd()
		];

		$command = new SiteDownload();
		$command->run(new ArrayInput($arguments), $output);

		$arguments = [
			'site:install',
			'site'          => $this->config->paths->demo,
			'--www'         => getcwd(),
			'--sample-data' => 'default',
			'--interactive' => true
		];

		$command = new SiteInstall();
		$command->setApplication($this->getApplication());
		$command->run(new ArrayInput($arguments), $output);
	}

	public function installFof(InputInterface $input, OutputInterface $output)
	{
		$app = Bootstrapper::getApplication(getcwd() . '/' . $this->config->paths->demo);

		// Output buffer is used as a guard against Joomla including ._ files when searching for adapters
		// See: http://kadin.sdf-us.org/weblog/technology/software/deleting-dot-underscore-files.html
		ob_start();

		$versions = new Versions();

		$versions->setRepository('https://github.com/akeeba/fof.git');
		$versions->refresh();

		$version = str_replace('.', '-', $versions->getLatestRelease());
		$package = str_replace('{VERSION}', $version, 'https://www.akeebabackup.com/download/fof3/{VERSION}/lib_fof30-{VERSION}-zip.zip');

		if (!$name = \JInstallerHelper::downloadPackage($package))
		{
			//TODO: Error message

			return;
		}

		$tmpPath = $app->get('tmp_path');
		$pkgPath = $tmpPath . $name;

		if (!$result = \JInstallerHelper::unpack($pkgPath))
		{
			//TODO: Error message

			return;
		}

		$resultPath = $result['dir'];
		$destPath   = getcwd() . '/' . $this->config->paths->libraries . 'fof30';

		if (!\JFolder::copy($resultPath, $destPath))
		{
			//TODO: Error message

			return;
		}

		if (!\JInstallerHelper::cleanupInstall($pkgPath, $resultPath))
		{
			//TODO: Warning message
		}

		$linkPath   = $destPath . '/fof';
		$linkTarget = getcwd() . '/' . $this->config->paths->demo . 'libraries/fof30';

		`ln -sf $linkPath $linkTarget`;

		$linkXMLPath   = $destPath . '/fof/lib_fof30.xml';
		$linkXMLTarget = getcwd() . '/' . $this->config->paths->demo . 'administrator/manifests/libraries/lib_fof30.xml';

		`ln -sf $linkXMLPath $linkXMLTarget`;

		$arguments = new ArrayInput(array(
			'extension:install',
			'site'      => $this->config->paths->demo,
			'extension' => 'lib_fof30',
			'--www'     => getcwd()
		));

		$command = new ExtensionInstall();
		$command->run($arguments, $output);

		ob_end_clean();
	}

	public function installPackage(InputInterface $input, OutputInterface $output)
	{
		//
	}
}
