# JBuilder

JBuilder is a CLI tools that simplifies the creation of Joomla component.
It is designed to work on Linux and MacOS.

> It is temporaly instructions. I will need to create the composer project later.

## Installation

1. Clone the repository

`$ git clone https://`

2. Install the dependancies

`$ cd jbuilder-cli`

`$ composer install`

> If you have not installed composer yet... just do it.

## How to use?

After you finished to install the builder.

1. You will need first to create a new project

`$ php bin/jbuilder project:init path/of/project`

It will ask you some question about the structure you want for the project.
Then it will create the structure tree in the project directory.

2. You will need after to install the bases (Joomla and FOF)

`$ cd path/of/project`

`$ php path/of/jbuilder/bin/jbuilder project:install`

It will download and install the last version of Joomla and FOF in your project directory.

3. [WIP] Create a component inside the project.

JBuilder is created to manage Joomla package, the FOF30 libraries and 1 or many component.
For now you will need to create at least one component.

`$ php path/of/jbuilder/bin/jbuilder component:create com_todo`

## TODO

- Do some check and validation (package name, paths)
- Add message during the Install (e.g. "Downloading Joomla, etc")
- Create the command to create a new FOF component, we will need fofcli in some points
	- Create the tree using fofcli
	- Create component.xml and admin/fof.xml (MagicFactory)
	- Maybe create the admin/access.xml and admin/config.xml
	- Create the admin/component.php and site/component.php
	- Automatic install in the Joomla demo
	- Update the pkg_name
- Create the command to link to fofcli command
