# Console for PrestaShop 1.7

This repository provides a Command that better supports PrestaShop legacy classes using the current PrestaShop Console and a list
of useful commands that you can use and reuse for learning purposes.

This module is released under MIT license.

## Install

```
cd modules 
git clone https://github.com/friends-of-presta/fop_console.git
cd fop_console
composer install
cd ../../
php bin/console pr:mo install fop_console
```

## Current commands

* `fop:console:debug:mode` change debug mode
* `fop:console:maintenance` get status or change maintenance mode, list or add maintenance ip address
* `fop:console:shop-status` Display shop(s) status(es)
* `fop:console:export`: Exports object models in XML;
* `fop:console:latest-products`: Displays the latest products;
* `fop:console:check-container`: List the services we can't use in Symfony commands (for now);

## Current Console arguments

These arguments are available to all commands that extends `FOP\Console\Command` class.

* `employee`: the current Employee ID, __null__ by default;
* `id_shop`: the selected Shop ID, set to __1__ by default;
* `id_shop_group`: the selected Group Shop ID, set to __null__ by default;

## Create your owns Commands

The official documentation from PrestaShop and Symfony Core teams are still right, but you needs
to extends our class.

```php
<?php

namespace Your\Own\Namespace;

use FOP\Console\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * This command is a working exemple.
 */
final class Export extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('hello:world')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->text('Hello friends of PrestaShop!');
    }
}
```

## What's next?

The current strategy is to configure a Context before the execution of the Command.
This works well but we like to make it more configurable from the Console arguments.

## Contribute

Feel free to add more commands, post some issues or new PR : contributions are very welcome.

