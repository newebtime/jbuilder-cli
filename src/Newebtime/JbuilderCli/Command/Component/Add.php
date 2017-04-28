<?php
/**
 * @package    JBuilderCli
 * @copyright  Copyright (c) 2003-2016 Frédéric Vandebeuque / Newebtime
 * @license    Mozilla Public License, version 2.0
 */

namespace Newebtime\JbuilderCli\Command\Component;

use Joomlatools\Console\Command\Extension\Install as ExtensionInstall;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Add extends AbstractComponent
{
    protected $installDemo;

    /**
     * @@inheritdoc
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('component:add')
            ->setDescription('Add a new component is the sources')
            ->addOption(
                'infos',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Setup the selected info (e.g. --info "email:email@domain.tld")'
            )->addOption(
                'paths',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Setup the selected path'
            )->addOption(
                'no-link',
                null,
                InputOption::VALUE_NONE,
                'Install the component on the demo'
            );
    }

    /**
     * @inheritdoc
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        \JLoader::import('joomla.filesystem.folder');

        $this->io->title('Add component');

        $this->installDemo = true;

        $path = $this->basePath
            . $this->config->paths->src
            . $this->config->paths->components
            . $this->component->comName
            . DIRECTORY_SEPARATOR;

        if (isset($this->config->components)
            && array_key_exists($this->component->name, $this->config->components)
        ) {
            $this->io->warning('Action canceled, a component using the same name already exists');

            exit;
        } elseif (is_dir($path)) {
            $this->io->warning('Action canceled, a directory for this component already exists');

            exit;
        }

        $this->io->comment([
            'ComName    ' . $this->component->comName,
            'Name       ' . $this->component->name,
            'Directory  ' . $path
        ]);

        $this->component->path = $path;

        if ($infos = $input->getOption('infos')) {
            foreach ($infos as $info) {
                list($name, $value) = explode(':', $info, 2);

                if (!isset($value) || !isset($this->config->infos->$name)) {
                    continue;
                }

                $this->config->infos->$name = $value;
            }
        }

        if ($paths = $input->getOption('paths')) {
            foreach ($paths as $path) {
                list($name, $value) = explode(':', $path, 2);

                if (!isset($value) || !isset($this->component->paths->$name)) {
                    continue;
                }

                $this->component->paths->$name = $value;
            }
        }

        if ($noLink = $input->getOption('no-link')) {
            $this->installDemo = false;
        }
    }

    /**
     * @@inheritdoc
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (!$this->io->confirm('Use the default structure?')) {
            $backend  = $this->io->ask('Define the backend directory', $this->component->paths['backend']);
            $backend  = rtrim($backend, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

            $frontend = $this->io->ask('Define the frontend directory', $this->component->paths['frontend']);
            $frontend = rtrim($frontend, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

            $media    = $this->io->ask('Define the media directory', $this->component->paths['media']);
            $media    = rtrim($media, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

            $language = $this->io->ask('Define the language directory', $this->component->paths['language']);
            $language = rtrim($language, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

            $paths = [
                'backend'  => $backend,
                'frontend' => $frontend,
                'media'    => $media,
                'language' => $language
            ];

            $this->component->paths = $paths;
        }

        if (!$this->io->confirm('Use the default informations (author, copyright, etc)?')) {
            $author      = $this->io->ask('Define the author?', $this->config->infos->author);
            $email       = $this->io->ask('Define the email?', $this->config->infos->email);
            $url         = $this->io->ask('Define the website URL', $this->config->infos->url);
            $copyright   = $this->io->ask('Define the copyright', $this->config->infos->copyright);
            $license     = $this->io->ask('Define the license', $this->config->infos->license);
            $version     = $this->io->ask('Define the version', $this->config->infos->version);

            $this->config->infos = [
                'author'      => $author,
                'email'       => $email,
                'url'         => $url,
                'copyright'   => $copyright,
                'license'     => $license,
                'version'     => $version
            ];
        }

        if (!$noLink = $input->getOption('no-link')) {
            $this->installDemo = $this->io->confirm('Install the component on the demo?');
        }

    }

    /**
     * @@inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io->note('The builder is working, please wait');

        $this->io->listing([
            'Update project file',
            'Generate component directories',
            'Generate component files',
            'Update package XML file'
        ]);

        if (!@mkdir($this->component->path)) {
            $this->io->error([
                'Component directory creation failed',
                'It is not possible to create the component directory, please check your access level'
            ]);

            exit;
        }

        foreach ($this->component->paths as $path) {
            if (!@mkdir($this->component->path . $path)) {
                $this->io->warning([
                    'Error creating the directory',
                    $this->component->path . $path
                ]);
            }
        }

        if (!@mkdir($this->component->path . $this->component->paths['language'] . 'frontend/en-GB', 0777, true)
            || !@mkdir($this->component->path . $this->component->paths['language'] . 'backend/en-GB', 0777, true)
        ) {
            $this->io->warning([
                'Error creating the language files'
            ]);
        }

        $this->updateProject();

        $this->buildFiles();

        $this->generateXml();

        $this->updatePackageXml();

        $this->io->success('New component added');

        if ($input->isInteractive()
            && $this->installDemo
        ) {
            $lns = [
                [
                    'from' => $this->component->path . $this->component->paths['backend'],
                    'to'   => $this->basePath . $this->config->paths->demo . 'administrator/components/' . $this->component->comName
                ],
                [
                    'from' => $this->component->path . $this->component->paths['frontend'],
                    'to'   => $this->basePath . $this->config->paths->demo . 'components/' . $this->component->comName
                ],
                [
                    'from' => $this->component->path . $this->component->paths['media'],
                    'to'   => $this->basePath . $this->config->paths->demo . 'media/' . $this->component->comName
                ],
                [
                    'from' => $this->component->path . $this->component->paths['language'] . 'frontend/en-GB/en-GB.' . $this->component->comName . '.ini',
                    'to'   => $this->basePath . $this->config->paths->demo . 'administrator/language/en-GB/en-GB.' . $this->component->comName . '.ini'
                ],
                [
                    'from' => $this->component->path . $this->component->paths['language'] . 'backend/en-GB/en-GB.' . $this->component->comName . '.ini',
                    'to'   => $this->basePath . $this->config->paths->demo . 'language/en-GB/en-GB.' . $this->component->comName . '.ini'
                ],
                [
                    'from' => $this->component->path . $this->component->name . '.xml',
                    'to'   => $this->basePath . $this->config->paths->demo . 'administrator/components/' . $this->component->comName . '/' . $this->component->name . '.xml'
                ]
            ];

            foreach ($lns as $ln) {
                $from = $ln['from'];
                $to   = $ln['to'];

                if (@!symlink($from, $to)) {
                    $this->io->warning('Section [demo] aborted, impossible to link the library directory');

                    return;
                }
            }

            $arguments = new ArrayInput([
                'extension:install',
                'site'      => $this->config->paths->demo,
                'extension' => $this->component->comName,
                '--www'     => $this->basePath
            ]);

            $command = new ExtensionInstall();
            $command->run($arguments, $output);
        }
    }

    public function updateProject()
    {
        $config = clone $this->component;
        unset($config->path);

        if (!isset($this->config->components)) {
            $this->config->components = (object) [];
        }

        $this->config->components->{$this->component->comName} = $config;

        if (!@file_put_contents($this->basePath . '.jbuilder', json_encode($this->config, JSON_PRETTY_PRINT))) {
            //TODO: Check
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

        foreach ($actions as $action) {
            $actionXml = $section->addChild('action');

            $actionXml->addAttribute('name', $action['name']);
            $actionXml->addAttribute('title', $action['title']);
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

        foreach ($xmls as $xml) {
            $file = $xml->file;
            $xml  = $xml->xml->asXML();

            $this->saveXML($xml, $file);
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


        if (!@file_put_contents($this->component->path . $this->component->paths['backend'] . $this->component->name . '.php', $php)) {
            // TODO: Error
        }
        if (!@file_put_contents($this->component->path . $this->component->paths['frontend'] . $this->component->name . '.php', $php)) {
            // TODO: Error
        }

        $langFiles = [
            $this->component->path . $this->component->paths['language'] . 'frontend/en-GB/en-GB.' . $this->component->comName . '.ini',
            $this->component->path . $this->component->paths['language'] . 'backend/en-GB/en-GB.' . $this->component->comName . '.ini'
        ];

        //TODO: Save component description if set
        foreach ($langFiles as $file) {
            if (!@touch($file)) {
                //TODO: Error
            }
        }
    }

    public function generateXml()
    {
        $path = $this->component->path;

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><extension></extension>');

        $xml->addAttribute('version', '3.3');
        $xml->addAttribute('type', 'component');
        $xml->addAttribute('method', 'upgrade');

        $xml->addChild('name', $this->component->comName);
        $xml->addChild('creationDate', date('Y-m-d'));
        $xml->addChild('author', $this->config->infos->author);
        $xml->addChild('authorEmail', $this->config->infos->email);
        $xml->addChild('authorUrl', $this->config->infos->url);
        $xml->addChild('copyright', $this->config->infos->copyright);
        $xml->addChild('license', $this->config->infos->license);
        $xml->addChild('version', $this->config->infos->version);
        $xml->addChild('description', strtoupper($this->component->comName) . '_XML_DESCRIPTION');

        if (file_exists($path . 'script.' . $this->component->comName . '.php')) {
            $xml->addChild('scriptfile', 'script.' . $this->component->comName . '.php');
        } elseif (file_exists($path . 'script.' . $this->component->name . '.php')) {
            $xml->addChild('scriptfile', 'script.' . $this->component->name . '.php');
        }

        $files = $xml->addChild('files');
        $files->addAttribute('folder', $this->component->paths['frontend']);

        if ($filesFiles = \JFolder::folders($path . $this->component->paths['frontend'])) {
            foreach ($filesFiles as $file) {
                $files->addChild('folder', $file);
            }
        }
        if ($filesFiles = \JFolder::files($path . $this->component->paths['frontend'])) {
            foreach ($filesFiles as $file) {
                $files->addChild('file', $file);
            }
        }

        if ($langsFiles = \JFolder::folders($path . $this->component->paths['language'] . 'backend')) {
            $langs = $xml->addChild('languages');
            $langs->addAttribute('folder', $this->component->paths['language'] . 'frontend');

            foreach ($langsFiles as $file) {
                //TODO: Better parse folder to find .ini file
                $langs
                    ->addChild('language', $this->component->paths['language'] . 'frontend' . $file . '/' . $file . '.' . $this->component->comName . '.ini')
                    ->addAttribute('tag', $file);
            }
        }

        //TODO: install & update : If exists / We need to setup the folder default: sql
        //$xml->addChild('install');
        //$xml->addChild('update');

        $media = $xml->addChild('media');
        $media->addAttribute('destination', $this->component->comName);

        if ($mediaFiles = \JFolder::folders($path . $this->component->paths['media'])) {
            foreach ($mediaFiles as $file) {
                $media->addChild('folder', $file);
            }
        }
        if ($mediaFiles = \JFolder::files($path . $this->component->paths['media'])) {
            foreach ($mediaFiles as $file) {
                $media->addChild('file', $file);
            }
        }

        $admin = $xml->addChild('administration');

        $adminMenu = $admin->addChild('menu', $this->component->comName);
        $adminMenu->addAttribute('link', 'option=' . $this->component->comName);

        $adminFiles = $admin->addChild('files');
        $adminFiles->addAttribute('folder', $this->component->paths['backend']);

        if ($adminFilesFiles = \JFolder::folders($path . $this->component->paths['backend'])) {
            foreach ($adminFilesFiles as $file) {
                $adminFiles->addChild('folder', $file);
            }
        }
        if ($adminFilesFiles = \JFolder::files($path . $this->component->paths['backend'])) {
            foreach ($adminFilesFiles as $file) {
                $adminFiles->addChild('file', $file);
            }
        }

        if ($adminLangsFiles = \JFolder::folders($path . $this->component->paths['language'] . 'backend')) {
            $adminLangs = $admin->addChild('languages');
            $adminLangs->addAttribute('folder', $this->component->paths['language'] . 'backend');

            foreach ($adminLangsFiles as $file) {
                //TODO: Better parse folder to find .ini file
                $adminLangs
                    ->addChild('language', $file . '.' . $this->component->comName . '.ini')
                    ->addAttribute('tag', $file);
            }
        }

        //TODO: updateservers

        $this->saveXML($xml->asXML(), $this->component->path . $this->component->name . '.xml');
    }

    public function updatePackageXml()
    {
        $srcBase = $this->basePath . $this->config->paths->src;

        $xml = new \SimpleXMLElement($srcBase.'pkg_'.$this->config->name.'.xml', 0, true);

        $files = $xml->xpath('/extension/files')[0];

        $folder = $files->addChild('folder', $this->config->paths->components.$this->component->comName);
        $folder->addAttribute('type', 'component');
        $folder->addAttribute('id', $this->component->comName);

        $this->saveXML($xml->asXML(), $srcBase .'pkg_'. $this->component->name . '.xml');
    }
}
