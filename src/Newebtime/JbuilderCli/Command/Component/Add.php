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

			exit;
		}
		elseif (is_dir($path))
		{
			$this->io->warning('Action canceled, a directory for this component already exists');

			exit;
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

		$this->buildFiles();

		//TODO: component.xml with only the current generated xml and php files
		touch($this->component->path . $this->component->name . '.xml');

		$srcBase = $this->basePath . $this->config->paths->src;

		//TODO: Update package XML file (pkg_name)
		//TODO: Update project file

		$this->io->success('New component added');

		if ($input->isInteractive()
			&& $this->io->confirm('Install the component on the demo?'))
		{
			//TODO: Symlink and add the component to the demo
		}
	}

	public function buildFiles()
	{
		$xmls = [];

		// fof.xml
		$xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><fof></fof>');

		$option = $xml
			->addChild('common')
				->addChild('container')
					->addChild('option', 'FOF30\Factory\MagicFactory');

		$option->addAttribute('name', 'factoryClass');

		$xmls[] = (object) [
			'xml'  => $xml,
			'file' => $this->component->path . $this->component->paths['backend'] . 'fof.xml'
		];

		// access.xml
		$xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><access></access>');
		$xml->addAttribute('component', $this->component->comName);

		$section = $xml
			->addChild('section');
		$section->addAttribute('name', 'component');

		$actions = [
			[
				'name'        => 'core.admin',
				'title'       => 'JACTION_ADMIN',
				'description' => 'JACTION_ADMIN_COMPONENT_DESC'
			],
			[
				'name'        => 'core.manage',
				'title'       => 'JACTION_MANAGE',
				'description' => 'JACTION_MANAGE_COMPONENT_DESC'
			],
			[
				'name'        => 'core.create',
				'title'       => 'JACTION_CREATE',
				'description' => 'JACTION_CREATE_COMPONENT_DESC'
			],
			[
				'name'        => 'core.delete',
				'title'       => 'JACTION_DELETE',
				'description' => 'JACTION_DELETE_COMPONENT_DESC'
			],
			[
				'name'        => 'core.edit',
				'title'       => 'JACTION_EDIT',
				'description' => 'JACTION_EDIT_COMPONENT_DESC'
			],
			[
				'name'        => 'core.edit.state',
				'title'       => 'JACTION_EDITSTATE',
				'description' => 'JACTION_EDITSTATE_COMPONENT_DESC'
			]
		];

		foreach ($actions as $action)
		{
			$actionXml = $section->addChild('action');

			$actionXml->addAttribute('name',        $action['name']);
			$actionXml->addAttribute('title',       $action['title']);
			$actionXml->addAttribute('description', $action['description']);
		}

		$xmls[] = (object) [
			'xml'  => $xml,
			'file' => $this->component->path . $this->component->paths['backend'] . 'access.xml'
		];

		// config.xml
		$xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
<config>
	<fieldset
		name="permissions"
		label="JCONFIG_PERMISSIONS_LABEL"
		description="JCONFIG_PERMISSIONS_DESC"
	>
		<field
			name="rules"
			type="rules"
			label="JCONFIG_PERMISSIONS_LABEL"
			class="inputbox"
			filter="rules"
			component="'. $this->component->comName . '"
			section="component" />
	</fieldset>
</config>');

		$xmls[] = (object) [
			'xml'  => $xml,
			'file' => $this->component->path . $this->component->paths['backend'] . 'config.xml'
		];

		foreach ($xmls as $xml)
		{
			$file = $xml->file;
			$xml  = $xml->xml->asXML();

			$domDocument = new \DOMDocument('1.0');
			$domDocument->loadXML($xml);
			$domDocument->preserveWhiteSpace = false;
			$domDocument->formatOutput = true;
			$xml = $domDocument->saveXML();

			file_put_contents($file, $xml);
		}

		$php = '<?php' . PHP_EOL;
		$php .= PHP_EOL;
		$php .= 'defined(\'_JEXEC\') or die();' . PHP_EOL;
		$php .= PHP_EOL;
		$php .= 'if (!defined(\'FOF30_INCLUDED\') && !@include_once(JPATH_LIBRARIES . \'/fof30/include.php\'))' . PHP_EOL;
		$php .=  '{'. PHP_EOL;
		$php .= '	throw new RuntimeException(\'FOF 3.0 is not installed\', 500);' . PHP_EOL;
		$php .=  '}'. PHP_EOL;
		$php .= PHP_EOL;
		$php .=  'FOF30\Container\Container::getInstance(\'' . $this->component->comName . '\')->dispatcher->dispatch();'. PHP_EOL;

		file_put_contents($this->component->path . $this->component->paths['backend'] . $this->component->name . '.php', $php);
		file_put_contents($this->component->path . $this->component->paths['frontend'] . $this->component->name . '.php', $php);
	}
}
