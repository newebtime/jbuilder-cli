# JBuilder

JBuilder CLI is a command line tool created to simplify the creation and management of
Joomla/FOF component. It is designed to work on Linux and MacOS
(it use symlink which are not working on Windows).

> JBuilder CLI is a WIP, the code is a bit messy. :-(

# Installation

1. Install using Composer:

`$ composer global require newebtime/jbuilder-cli`

> If you have not installed composer yet... just do it.
> https://getcomposer.org/

# How to use?

## Create a project

A project is not only your component, but it is your component(s) and everything related.
For example you can have 1 component, 3 modules, and 2 libraries or 2 components and 1 plugins.

It is basicaly a Joomla package.

1. Create the new project inside an existing directory

`$ php jbuilder project:init path/of/project`

It will ask you some question about the structure you want for the project.
Then it will create the structure tree in the project directory.

2. Install the demo website and FOF

This step will download the last version of Joomla, install it then download FOF and install it.
After that it will copy FOF inside your project libraries.

For now you cannot use an existing website, it is a todo for later :)

`$ cd path/of/project`

`$ php jbuilder project:install`

3. [WIP] Create a component inside the project.

You can create a component using:

`$ php jbuilder component:create com_todo`

It will ask you some questions about the structure and informations. Then it will generate
the base files (PHP and XML) and update the package XML.

4. [TODO] Start building your component

--

# TODO

- Ask more informations for the project
- Create a composer.json with the packagename as name
- Do some check and validation (package name, paths)
- Add message during the Install (e.g. "Downloading Joomla, etc")
- Create the command to link to fofcli command (if possible)
- Ask to add fof30 to .gitignore (it will be downloaded automaticaly on project:install)
- Refactorize a bit, it become messy

# Requirements

* [Composer](https://getcomposer.org/)
  * [Joomlatools Console](https://github.com/joomlatools/joomlatools-console/)
* Joomla 3.3+ and FOF 3.0+ (automaticaly installed)

# License

JBuilder CLI is free and open-source software licensed under the [MPLv2 license](LICENSE.txt).
