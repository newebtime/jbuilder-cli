# JBuilder CLI

JBuilder CLI is a command line tool created to simplify the creation and management of
Joomla/FOF components. It is designed to work on Linux and MacOS. Windows use should be able
to use it, but some command may not work.

> JBuilder CLI is a WIP, the code is a bit messy. :-(

## Installation

`$ composer global require newebtime/jbuilder-cli`

> If you have not installed composer yet... [just do it](https://getcomposer.org/).

## How to use?

### Create a project

A project is not only your component, but it is your component(s) and everything related.
For example you can have 1 component, 3 modules, and 2 libraries or 2 components and 1 plugins.

It is basicaly a Joomla package.

Init the project

```
$ jbuilder project:init path/of/project
```
Then install the requirements
```
$ cd path/of/project
$ jbuilder project:install
```

### Add a component and build it

```
$ jbuilder component:create com_todo
```

Your first component is now added, [go to the wiki page](https://github.com/newebtime/jbuilder-cli/wiki)
to have more documentation to start building it.

# TODO

- Ask more informations for the project
- Create a composer.json with the packagename as name
- Do some check and validation (package name, paths)
- Ask to add fof30 to .gitignore (it will be downloaded automaticaly on project:install)
- Refactorize a bit, it become messy
- Add help to each command

# Requirements

* [Composer](https://getcomposer.org/)
  * [Joomlatools Console](https://github.com/joomlatools/joomlatools-console/)
* Joomla 3.4.2+ (automaticaly installed)
  * FOF 3.0+ (automaticaly installed)

# License

JBuilder CLI is free and open-source software licensed under the [MPLv2 license](LICENSE.txt).
