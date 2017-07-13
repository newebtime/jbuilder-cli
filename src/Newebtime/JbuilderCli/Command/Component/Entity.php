<?php
/**
 * @package    JBuilderCli
 * @copyright  Copyright (c) 2003-2017 Frédéric Vandebeuque / Newebtime
 * @license    Mozilla Public License, version 2.0
 */

namespace Newebtime\JbuilderCli\Command\Component;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Entity extends AbstractComponent
{
    protected $entity;

    protected $type;

    protected $tableName;

    /** @var bool */
    protected $hasTable = false;

    /** @var bool */
    protected $deleteTable = false;

    protected $builderFields = [];

    /** @var  \FOF30\Container\Container */
    protected $container;

    /**
     * @@inheritdoc
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('component:entity')
            ->setDescription('Generate a new component entity (e.g. todos)')
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
            )->addOption(
                'use',
                null,
                InputOption::VALUE_OPTIONAL,
                'What table generator do you want to use for this entity? ([\'default\', \'builder\', \'none\'])'
            );
    }

    /**
     * @inheritdoc
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        if (!@include_once JPATH_PLATFORM . '/fof30/include.php') {
            $this->io->warning('Action canceled, FOF could not be loaded');

            exit;
        }

        $this->io->title('Generate new entity');

        // [Name]
        $name = $input->getOption('name');

        if (!$name
            && $input->isInteractive()
        ) {
            $name = $this->io->ask('No entity name given, please enter the entity name (e.g. todos)');
        }

        if (preg_replace('/[^A-Z_]/i', '', $name) != $name
            || empty($name)
        ) {
            $this->io->warning('Action canceled, the name is not correct, you can use only A-Z and _ (e.g. todos)');

            exit;
        }

        //TODO: check plurial

        $this->entity = $name;
        // [/Name]

        // [Type]
        $this->type = $input->getOption('use');

        if ($this->type && !in_array($this->type, ['default', 'builder', 'none'])) {
            $this->io->warning('Action canceled, --use only accept [\'default\', \'builder\', \'none\']');

            exit;
        }
        // [/Type]

        // [DB Table]
        $inflector = new \FOF30\Inflector\Inflector();

        $this->tableName = '#__' . $this->component->name . '_' . $inflector->pluralize($this->entity);

        try {
            \JFactory::getDbo()->getTableCreate($this->tableName);

            $this->io->note('A database table already exist for this entity');

            $this->hasTable = true;
        } catch (\RuntimeException $e) {
            $this->io->note('No table found for this entity in the database');
        }
        // [/DB Table]
    }

    /**
     * @@inheritdoc
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->io->section('Database table');

        if ($this->hasTable) {
            if ('delete' == $this->io->choice('Do you want to use this table or delete it?', ['use', 'delete'], 'use')) {
                $this->deleteTable = true;
                $this->hasTable    = false;
            }
        }

        if (!$this->hasTable) {
            if (!$this->type) {
                $this->io->table(['type', 'description'], [
                    ['default', 'Fields: id, title, created_on, created_by, modified_on, modified_by'],
                    ['builder', 'Create the table using the CLI builder'],
                    ['none', 'No table, Model and Layouts will not be generated']
                ]);

                $this->type = $this->io->choice(
                    'What table generator do you want to use for this entity?',
                    ['default', 'builder', 'none'],
                    'default'
                );
            }

            if ('builder' == $this->type) {
                $fields = [];

                while ('Yes' == $this->io->choice('Do you want to create a new special field?', ['Yes', 'No'], 'Yes')) {
                    $fieldType = $this->io->choice(
                        'Please select the field type',
                        ['Title & Slug', 'Published', 'Order', 'Author & Editor', 'Checkout', 'Asset', 'Access Level'],
                        'Title & Slug'
                    );

                    if ($fieldType == 'Title & Slug') {
                        $fields['title'] = [
                            'Field' => 'title',
                            'Type'  => 'VARCHAR(255)',
                            'Null'  => 'NO'
                        ];

                        $fields['slug'] = [
                            'Field' => 'slug',
                            'Type'  => 'VARCHAR(255)',
                            'Null'  => 'YES'
                        ];
                    } elseif ($fieldType == 'Published') {
                        $fields['enabled'] = [
                            'Field' => 'enabled',
                            'Type'  => 'TINYINT(1)',
                            'Null'  => 'No'
                        ];
                    } elseif ($fieldType == 'Order') {
                        $fields['ordering'] = [
                            'Field' => 'ordering',
                            'Type'  => 'INT(11)',
                            'Null'  => 'No'
                        ];
                    } elseif ($fieldType == 'Author & Editor') {
                        $fields['created_on'] = [
                            'Field' => 'created_on',
                            'Type'  => 'DATE',
                            'Null'  => 'NO'
                        ];

                        $fields['created_by'] = [
                            'Field' => 'created_by',
                            'Type'  => 'INT(11)',
                            'Null'  => 'NO'
                        ];

                        $fields['modified_on'] = [
                            'Field' => 'modified_on',
                            'Type'  => 'DATE',
                            'Null'  => 'YES'
                        ];

                        $fields['modified_by'] = [
                            'Field' => 'modified_by',
                            'Type'  => 'INT(11)',
                            'Null'  => 'YES'
                        ];
                    } elseif ($fieldType == 'Checkout') {
                        $fields['locked_by'] = [
                            'Field' => 'locked_by',
                            'Type'  => 'INT(11)',
                            'Null'  => 'YES'
                        ];

                        $fields['locked_on'] = [
                            'Field' => 'locked_on',
                            'Type'  => 'DATE',
                            'Null'  => 'YES'
                        ];
                    } elseif ($fieldType == 'Asset') {
                        $fields['asset_id'] = [
                            'Field' => 'asset_id',
                            'Type'  => 'INT(11)',
                            'Null'  => 'No'
                        ];
                    } else {
                        $fields['access'] = [
                            'Field' => 'access',
                            'Type'  => 'INT(11)',
                            'Null'  => 'No'
                        ];
                    }
                }

                while ('Yes' == $this->io->choice('Do you want to create a new field?', ['Yes', 'No'], 'Yes')) {
                    $name = $this->io->ask('Please enter the field name');
                    $type = $this->io->choice(
                        'Please select the field name',
                        ['INT(11)', 'VARCHAR(255)', 'TEXT', 'DATE', 'OTHER'],
                        'VARCHAR(255)'
                    );

                    if ($type == 'OTHER') {
                        $type = $this->io->ask('Please enter the field type');
                    }

                    $isNull = $this->io->choice('Field Null?', ['Yes', 'No'], 'No');

                    $fields[$name] = [
                        'Field' => $name,
                        'Type'  => $type,
                        'Null'  => $isNull
                    ];
                }

                $this->builderFields = $fields;
            }
        }
    }

    /**
     * @@inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sections = ['admin', 'site'];

        if ($input->getOption('frontend')) {
            $sections = ['site'];
        } elseif ($input->getOption('backend')) {
            $sections = ['admin'];
        }

        $inflector = new \FOF30\Inflector\Inflector();

        $viewSingular = $inflector->singularize($this->entity);

        if ($this->deleteTable) {
            \JFactory::getDbo()->dropTable($this->tableName);
        }

        if (!$this->hasTable) {
            if (in_array($this->type, ['default', 'builder'])) {
                $idFieldName = $this->component->name . '_' . $viewSingular . '_id';

                $fields = [
                    $idFieldName => [
                        'Field' => $idFieldName,
                        'Type'  => 'INT(11) UNSIGNED',
                        'Null'  => 'NO',
                        'Extra' => 'AUTO_INCREMENT',
                    ]
                ];

                if ('default' == $this->type) {
                    $fields += [
                        'title' => [
                            'Field' => 'title',
                            'Type'  => 'VARCHAR(255)',
                            'Null'  => 'NO',
                        ],
                        'created_on' => [
                            'Field' => 'created_on',
                            'Type'  => 'DATE',
                            'Null'  => 'NO',
                        ],
                        'created_by' => [
                            'Field' => 'created_by',
                            'Type'  => 'INT(11) UNSIGNED',
                            'Null'  => 'NO',
                        ],
                        'modified_on' => [
                            'Field' => 'modified_on',
                            'Type'  => 'DATE',
                            'Null'  => 'NO',
                        ],
                        'modified_by' => [
                            'Field' => 'modified_by',
                            'Type'  => 'INT(11) UNSIGNED',
                            'Null'  => 'NO',
                        ]
                    ];
                } else {
                    $fields += $this->builderFields;
                }

                $xml = new \SimpleXMLElement('<xml></xml>');

                $database = $xml->addChild('database');

                $table = $database->addChild('table_structure');
                $table->addAttribute('name', $this->tableName);

                foreach ($fields as $buildField) {
                    $field = $table->addChild('field');

                    foreach ($buildField as $name => $value) {
                        $field->addAttribute($name, $value);
                    }
                }

                $key = $table->addChild('key');
                $key->addAttribute('Key_name', 'PRIMARY');
                $key->addAttribute('Column_name', $idFieldName);

                // Force to MySQLi, PDO has no xmlToCreate()
                // Joomla Bug: https://github.com/joomla/joomla-cms/issues/10527
                $importer = new \JDatabaseImporterMysqli();
                $importer->setDbo(\JFactory::getDbo());

                $importer->from($xml)->mergeStructure();

                //TODO: Save the XML?
                //TODO: Create a .sql file?
            } else {
                $this->io->caution([
                    'No database table found or created. Model and Layouts will not be created'
                ]);

                //TODO: Disable Model and Layouts
            }
        }

        $this->container = \FOF30\Container\Container::getInstance($this->component->comName, [
            'tempInstance' => true,
            'factoryClass' => 'FOF30\\Factory\\MagicFactory'
        ]);
        $this->container->factory->setSaveScaffolding(true);

        $this->generateController($sections);
        $this->generateLayouts($sections);
        $this->generateModel($sections);
        $this->generateView($sections);

        //TODO: Allow to create Non database aware controller and model (not possible using FOF scaffolding)
    }

    protected function generateController($sections)
    {
        $this->io->section('Controller');

        $view = $this->entity;

        // plural / singular
        $view = $this->container->inflector->singularize($view);

        foreach ($sections as $section) {
            $classname = $this->container->getNamespacePrefix($section) . 'Controller\\' . ucfirst($view);

            $scaffolding = new \FOF30\Factory\Scaffolding\Controller\Builder($this->container);
            $scaffolding->setSection($section);

            if (!$scaffolding->make($classname, $view)) {
                $this->io->error('An error occurred while creating the Controller class');

                exit;
            }
        }
    }

    protected function generateLayouts($sections)
    {
        $this->io->section('Layouts');

        $view = ucfirst($this->entity);

        $types = [
            'admin' => ['default', 'form'],
            'site'  => ['default', 'item']
        ];

        $originalFrontendPath = $this->container->frontEndPath;
        $originalBackendPath  = $this->container->backEndPath;

        foreach ($sections as $section) {
            foreach ($types[$section] as $type) {
                // plural / singular
                if ($type != 'default') {
                    $view = $this->container->inflector->singularize($view);
                } else {
                    $view = $this->container->inflector->pluralize($view);
                }

                $this->container->frontEndPath = ($section == 'admin') ? $this->container->backEndPath : $this->container->frontEndPath;

                $scaffolding = new \FOF30\Factory\Scaffolding\Layout\Builder($this->container);

                if (!$scaffolding->make('form.' . $type, $view)) {
                    $this->io->error('An error occurred while creating the Controller class');

                    exit;
                }

                // And switch them back!
                $this->container->frontEndPath = $originalFrontendPath;
                $this->container->backEndPath  = $originalBackendPath;
            }
        }
    }

    protected function generateModel($sections)
    {
        $this->io->section('Model');

        $view = $this->entity;

        // plural / singular
        $view = $this->container->inflector->pluralize($view);

        foreach ($sections as $section) {
            $classname = $this->container->getNamespacePrefix($section) . 'Model\\' . ucfirst($view);

            $scaffolding = new \FOF30\Factory\Scaffolding\Model\Builder($this->container);
            $scaffolding->setSection($section);

            if (!$scaffolding->make($classname, $view)) {
                $this->io->error('An error occurred while creating the Model class');

                exit;
            }
        }
    }

    protected function generateView($sections)
    {
        $this->io->section('View');

        $view = $this->entity;

        // plural / singular
        $view = $this->container->inflector->pluralize($view);

        foreach ($sections as $section) {
            $classname = $this->container->getNamespacePrefix($section) . 'View\\' . ucfirst($view) . '\\Html';

            $scaffolding = new \FOF30\Factory\Scaffolding\View\Builder($this->container);
            $scaffolding->setSection($section);

            if (!$scaffolding->make($classname, $view, 'html')) {
                $this->io->error('An error occurred while creating the View class');

                exit;
            }
        }
    }
}
