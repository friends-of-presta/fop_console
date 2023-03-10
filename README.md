[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.2-8892BF.svg?style=flat-square)](https://php.net/)
[![PHP tests](https://github.com/friends-of-presta/fop_console/actions/workflows/phpstan.yml/badge.svg)](https://github.com/friends-of-presta/fop_console/blob/dev/.github/workflows/phpstan.yml)
[![GitHub release](https://img.shields.io/github/v/release/friends-of-presta/fop_console)](https://github.com/friends-of-presta/fop_console/releases)
[![Slack chat](https://img.shields.io/badge/Chat-on%20Slack-red)](https://github.com/friends-of-presta/who-we-are#what-we-do)

# Friends of Presta Console

Fop console is a module which provides a set a commands to extend PrestaShop 1.7 commands.

Since version 1.7.5.0 [Prestashop provides some terminal commands](https://devdocs.prestashop.com/1.7/modules/concepts/commands/) using the [Symfony console tool](https://symfony.com/doc/3.4/console.html).

This repository provides a base Command with better support for PrestaShop legacy classes and useful commands to easy the development on Prestashop or manage a shop.
These commands are mainly for developers, just some basic knowledge of command line processing is needed.

## Install from release (recommended)

[Donwload a zip release](https://github.com/friends-of-presta/fop_console/releases) and install it like any other module.

Alternatively, run this in a shell :

```bash
#!/bin/bash
wget https://git.io/JMF3q --output-document /tmp/fop_console.zip && unzip /tmp/fop_console.zip -d modules && ./bin/console pr:mo install fop_console
```

## Install from sources

If you want use the dev branch, you can install from github.
If you want to contribute, first create a fork and follow the same steps using your forked repository url instead of the original one.

```
cd modules 
git clone https://github.com/friends-of-presta/fop_console.git
cd fop_console
composer install
```
Install the module in the backoffice or in command line like this :
```
cd ../../
php bin/console pr:mo install fop_console
```

## Current commands

* `fop:about:version`                  Display the Fop Console version (on disk, on database, latest available release)
* `fop:cache:clear`                    Replace the cache directory with an empty one.
* `fop:category:clean`                 Manage empty categories
* `fop:category:products-count`        Get the number of products for category and its children
* `fop:configuration:export`           Export configuration values (from ps_configuration table)
* `fop:configuration:import`           Import configuration values
* `fop:container:check`                Health check of the Service Container
* `fop:customer-groups`                Customer groups
* `fop:employee:list`                  List registered employees
* `fop:employee:change-password`       Change employee password
* `fop:environment:debug-mode`         Enable or Disable debug mode.
* `fop:environment:get-parameters`     Display information about the installation (db name, etc)
* `fop:environment:setup-dev`          Install your project for local developement
* `fop:export:data`                    Allows to export data in XML
* `fop:generate:htaccess`              Generate the .htaccess file
* `fop:generate:robots`                Generate the robots.txt file
* `fop:group:transfer-customers`       Transfers or add customers from a group to an other
* `fop:hook:add`                       Create hook in database
* `fop:image:generate:categories`      Regenerate categories thumbnails
* `fop:image:generate:manufacturers`   Regenerate manufacturers thumbnails
* `fop:image:generate:products`        Regenerate products thumbnails
* `fop:image:generate:stores`          Regenerate stores thumbnails
* `fop:image:generate:suppliers`       Regenerate suppliers thumbnails
* `fop:module:generate`                Scaffold new PrestaShop module
* `fop:module:hook`                    Attach one module on specific hook
* `fop:module:hooks`                   Get modules list
* `fop:module:non-essential`           Manage non essential modules
* `fop:module:rename`                  Rename a module
* `fop:module:unhook`                  Detach module from hook
* `fop:override:make`                  Generate a file to make an override
* `fop:product:latest`                 Displays the latest products
* `fop:shop:maintenance`               Configure maintenance mode
* `fop:shop:status`                    Display shops statuses
* `fop:theme:reset-layout`             Reset current (or selected) theme

## Create your owns Commands

The official documentation from PrestaShop and Symfony Core teams are still right, but you needs
to extends our class.

```php
<?php

// psr-4 autoloader

namespace FOP\Console\Commands\Domain; // e.g. namespace FOP\Console\Commands\Configuration

use FOP\Console\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class DomainAction extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('fop:domain') // e.g 'fop:export'
            // or
            ->setName('fop:domain:action') // e.g 'fop:configuration:export' 
            ->setDescription('Describe the command on a user perspective.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io->text('Hello friends of PrestaShop!');

        return 0; // return 0 on success or 1 on failure.
    }
}
```

## Getting started

In a shell (call it shell, console or terminal), at the root of a Prestashop installation, type this command to list all available commands.
You'll see commands provided by Symfony, Prestashop and installed modules.

```shell
./bin/console list
```

To list only fop commands :
```shell
./bin/console list fop
```

To toggle the debug-mode (_PS_DEV_MODE_) run :
```shell
./bin/console fop:environment:debug toggle
```

To get help about a command :
```shell
./bin/console help fop:environment:debug
```

You are ready to go !

## Contribute

Any contributions are very welcome :)
First [install from sources](/README.md#install-from-sources) and see [Contributing](/CONTRIBUTING.md) for details.

[Current contributors](https://github.com/friends-of-presta/fop_console/graphs/contributors) or [contributors](/CONTRIBUTORS.md).

## Compatibility

| Prestashop Version | Compatible |
| ------------------ | -----------|
| 1.7.4.x and below | :x: |
| 1.7.5.x | :heavy_check_mark: |
| 1.7.6.x | :heavy_check_mark: |
| 1.7.7.x | :heavy_check_mark: |
| 1.7.8.x | :heavy_check_mark: |

| Php Version | Compatible |
| ------ | -----------|
| 7.1 and below | :x: |
| 7.2 | :heavy_check_mark: |
| 7.3| :heavy_check_mark: |
| 7.4 | :heavy_check_mark: |
| 8.0 | :interrobang: Not yet tested |

## License

This module is released under AFL license.
See [License](/docs/licenses/LICENSE.txt) for details.
