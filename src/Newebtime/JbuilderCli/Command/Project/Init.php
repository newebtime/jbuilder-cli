<?php
/**
 * @package    JBuilderCli
 * @copyright  Copyright (c) 2003-2017 Frédéric Vandebeuque / Newebtime
 * @license    Mozilla Public License, version 2.0
 */

namespace Newebtime\JbuilderCli\Command\Project;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Newebtime\JbuilderCli\Command\Base as BaseCommand;
use Newebtime\JbuilderCli\Exception\OutputException;

class Init extends BaseCommand
{
    protected $ignoreDemo;

    protected $ignoreFof;

    protected $composer;

    protected $composerList;

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
            )->addOption(
                'name',
                null,
                InputOption::VALUE_OPTIONAL,
                'The project name'
            )->addOption(
                'paths',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Setup the selected path (e.g. --path "src:sources/")'
            )->addOption(
                'infos',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Setup the selected info (e.g. --info "email:email@domain.tld")'
            )->addOption(
                'git-demo',
                true,
                InputOption::VALUE_NONE,
                'Do not add demo website in .gitignore'
            );
    }

    /**
     * @inheritdoc
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        // [Default]
        $this->config = (object) [
            'paths' => (object) [
                'src'        => 'src/',
                'components' => 'components/',
                'libraries'  => 'libraries/',
                'demo'       => 'demo/'
            ],
            'infos' => (object) [
                'author'      => 'me',
                'email'       => 'me@domain.tld',
                'url'         => 'http://www.domain.tld',
                'copyright'   => 'Copyright (c) 2017 Me',
                'license'     => 'GNU General Public License version 2 or later',
                'description' => ''
            ]
        ];

        $this->ignoreDemo = true;
        $this->ignoreFof  = true;
        $this->composer   = true;
        // [/Default]

        try {
            $this->initIO($input, $output);

            $this->io->title('Init project');

            $path = $input->getArgument('path');

            if (!$path) {
                $path = $this->basePath;
            }

            $path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

            if (!is_dir($path)) {
                throw new OutputException([
                    'This directory does not exists, please check',
                    $path
                ], 'error');
            }

            if (file_exists($path . '.jbuilder')) {
                throw new OutputException([
                    'This directory has already been init',
                    $path
                ], 'warning');
            }

            $this->basePath = $path;

            if ($name = $input->getOption('name')) {
                $this->config->name = $name;
            }

            if ($paths = $input->getOption('paths')) {
                foreach ($paths as $path) {
                    list($name, $value) = explode(':', $path, 2);

                    if (!isset($value) || !isset($this->config->paths->$name)) {
                        continue;
                    }

                    $this->config->paths->$name = $value;
                }
            }

            if ($infos = $input->getOption('infos')) {
                foreach ($infos as $info) {
                    list($name, $value) = explode(':', $info, 2);

                    if (!isset($value) || !isset($this->config->infos->$name)) {
                        continue;
                    }

                    $this->config->infos->$name = $value;
                }
            }

            if ($input->getOption('git-demo')) {
                $this->ignoreDemo = false;
            }
        } catch (OutputException $e) {
            $type = $e->getType();

            $this->io->$type($e->getMessages());

            exit;
        } catch (\Exception $e) {
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

        $name = $this->io->ask('What is the package name?', isset($this->config->name) ? $this->config->name : 'myproject');

        $this->config->name = $name;

        if (!$this->io->confirm('Use the default structure?')) {
            $src = $this->io->ask('Define the sources directory', 'src');
            $src = rtrim($src, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

            $components = $this->io->ask(
                'Define the components directory (relative to the sources directory)',
                'components'
            );
            $components = rtrim($components, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

            $libraries = $this->io->ask(
                'Define the libraries directory (relative to the sources directory)',
                'libraries'
            );
            $libraries = rtrim($libraries, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

            $demo = $this->io->ask('Define the Joomla website directory', 'demo');
            $demo = rtrim($demo, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

            $this->io->comment([
                'Sources    ' . $src,
                'Components ' . $src . $components,
                'Libraries  ' . $src . $libraries,
                'Demo       ' . $demo
            ]);

            $this->config->paths = (object) [
                'src'        => $src,
                'components' => $components,
                'libraries'  => $libraries,
                'demo'       => $demo
            ];
        }

        if (!$this->io->confirm('Use the default informations (author, copyright, etc)?')) {
            $author      = $this->io->ask('Define the author?', $this->config->infos->author);
            $email       = $this->io->ask('Define the email?', $this->config->infos->email);
            $url         = $this->io->ask('Define the website URL', $this->config->infos->url);
            $copyright   = $this->io->ask('Define the copyright', $this->config->infos->copyright);
            $license     = $this->io->ask('Define the license', $this->config->infos->licence);
            $description = $this->io->ask('Define the description', $this->config->infos->description);

            $this->config->infos = [
                'author'      => $author,
                'email'       => $email,
                'url'         => $url,
                'copyright'   => $copyright,
                'license'     => $license,
                'description' => $description
            ];
        }

        if (!$input->getOption('git-demo')) {
            $this->ignoreDemo = $this->io->confirm('Add the demo in .gitignore?');
        }

        $this->ignoreFof = $this->io->confirm('Add the fof30 in .gitignore?');

        $this->composer = $this->io->confirm('Create the composer.json?');

        $this->composerList = (object) [
            'name'        => $this->config->name,
            'description' => $this->config->infos->description,
            'type'        => 'project',
            'license'     => $this->config->infos->licence,
            'authors'     => [
                (object) [
                'name'  => $this->config->infos->author,
                'email' => $this->config->infos->email,
            ]]
        ];
    }

    /**
     * @@inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->io->section('Project creation');

            if (empty($this->config->name)) {
                throw new OutputException([
                    'Action canceled, enter a name for this project'
                ], 'warning');
            }

            $path = $this->basePath;

            $mkPaths = [
                $path . $this->config->paths->src,
                $path . $this->config->paths->src . $this->config->paths->components,
                $path . $this->config->paths->src . $this->config->paths->libraries,
                $path . $this->config->paths->demo
            ];

            foreach ($mkPaths as $mkPath) {
                if (is_dir($mkPath)) {
                    $skips[] = $mkPath;
                } elseif (!@mkdir($mkPath)) {
                    throw new OutputException([
                        'Something wrong happened during the creation po the directory',
                        $mkPath
                    ], 'error');
                }
            }



            if (isset($skips)) {
                $this->io->note(
                    array_merge(['Skip directory creation, those directories already exists'], $skips)
                );
            }

            if (!@touch($path . 'README.md')) {
                $this->io->warning([
                    'The README.md could not be created',
                    $path . 'README.md'
                ]);
            }

            if ($this->ignoreDemo
                && !@file_put_contents($path . '.gitignore', $this->config->paths->demo.PHP_EOL, FILE_APPEND)) {
                $this->io->warning([
                    'The .gitignore could not be created',
                    $path . 'README.md'
                ]);
            }

            if ($this->ignoreFof
                && !@file_put_contents($path . '.gitignore', $this->config->paths->src . $this->config->paths->libraries . 'fof30', FILE_APPEND)) {
                $this->io->warning([
                    'The .gitignore could not be created',
                    $path . 'README.md'
                ]);
            }

            if ($this->composer
                && !@file_put_contents($path . 'composer.json', json_encode($this->composerList, JSON_PRETTY_PRINT))) {
                throw new OutputException([
                    'Action canceled, the composer file cannot be created, please check.',
                    $path . 'composer.json'
                ], 'error');
            }

            if (!@file_put_contents($path . '.jbuilder', json_encode($this->config, JSON_PRETTY_PRINT))) {
                throw new OutputException([
                    'Action canceled, the builder file cannot be created, please check.',
                    $path . '.jbuilder'
                ], 'error');
            }

            $this->createPackageXml();
        } catch (OutputException $e) {
            $type = $e->getType();

            $this->io->$type($e->getMessages());

            exit;
        } catch (\Exception $e) {
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
        $xml->addChild('author', $this->config->infos->author);
        $xml->addChild('creationDate', date('Y-m-d'));
        $xml->addChild('packagename', $this->config->name);
        $xml->addChild('version', '0.0.1');
        $xml->addChild('url', $this->config->infos->url);
        $xml->addChild('description', $this->config->infos->description);

        $fof = $xml
            ->addChild('files')
            ->addChild('folder', $this->config->paths->libraries . 'fof');

        $fof->addAttribute('type', 'library');
        $fof->addAttribute('id', 'fof30');

        $this->saveXML(
            $xml->asXML(),
            $this->basePath . $this->config->paths->src . 'pkg_' . $this->config->name . '.xml'
        );
    }
}
