# Friends of Presta Console

Fop console is a module which provides a set a commands to extend PrestaShop 1.7 commands.

Since version 1.7.5.0 [Prestashop provides some terminal commands](https://devdocs.prestashop.com/1.7/modules/concepts/commands/) using the [Symfony console tool](https://symfony.com/doc/3.4/console.html).

This repository provides a base Command with better support for PrestaShop legacy classes and useful commands to easy the development on Prestashop or manage a shop.
These commands are mainly for developers, just some basic knowledge of command line processing is needed.

## Fop Console commands

* fop:add-hook                                 Create hook in database
* fop:check-container                          Health check of the Service Container
* fop:clear-cache                              Replace the cache directory with an empty one.
* fop:configuration:export                     Export configuration values (from ps_configuration table).
* fop:configuration:import                     Import configuration values
* fop:customer-groups                          Customer groups
* fop:debug-mode                               Enable or Disable debug mode.
* fop:export                                   Allows to export data in XML
* fop:generate:htaccess                        Generate the .htaccess file
* fop:generate:robots                          Generate the robots.txt file
* fop:hook-module                              Attach one module on specific hook
* fop:images:generate:categories               Regenerate categories thumbnails
* fop:images:generate:manufacturers            Regenerate manufacturers thumbnails
* fop:images:generate:products                 Regenerate products thumbnails
* fop:images:generate:stores                   Regenerate stores thumbnails
* fop:images:generate:suppliers                Regenerate suppliers thumbnails
* fop:latest-products                          Displays the latest products
* fop:maintenance                              Configure maintenance mode
* fop:module:hooks                             Get modules list
* fop:shop-status                              Display shops statuses
* fop:theme-reset                              Reset current theme layout
* fop:unhook-module                            Detach module from hoo

## Install from release (recommended)

Donwload the zip release and install it like any other module.

## Install from sources

If you want to contribute or use the dev branch, you can install from github

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
./bin/console fop:debug-mode toggle
```

To get help about a command :
```shell
./bin/console help fop:debug-mode
```

You are ready to go !

## Contribute

Any contributions are very welcome :)
See [Contributing](/CONTRIBUTING.md) for details.

[Current contributors](https://github.com/friends-of-presta/fop_console/graphs/contributors) or [contributors](/CONTRIBUTORS.md).

## Compatibility

| Prestashop Version | Compatible |
| ------------------ | -----------|
| 1.7.4.x and below | :x: |
| 1.7.5.x | :heavy_check_mark: |
| 1.7.6.x | :heavy_check_mark: |
| 1.7.7.x | :heavy_check_mark: |

| Php Version | Compatible |
| ------ | -----------|
| 7.1 and below | :x: |
| 7.2 | :heavy_check_mark: |
| 7.3| :heavy_check_mark: |
| 7.4 | :interrobang: Not yet tested |
| 8.0 | :interrobang: Not yet tested |

## License

This module is released under AFL license.
See [License](/docs/licenses/LICENSE.txt) for details.
