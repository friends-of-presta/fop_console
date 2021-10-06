

## Create your owns Commands

The official documentation from PrestaShop and Symfony Core teams are still right, but you needs
to extends our class.

```php
<?php

// psr-4 autoloader

// if the command is located at src/Commands
namespace FOP\Console\Commands; 
// or if command is located in a subfolder
namespace FOP\Console\Commands\Domain; // e.g. namespace FOP\Console\Commands\Configuration

use FOP\Console\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class MyCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('fop:mycommand') // e.g 'fop:shop-status'
            // or
            ->setName('fop:domain:mycommand') // e.g 'fop:configuration:export' 
            ->setDescription('Describe the command on a user perspective.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->text('Hello friends of PrestaShop!');

        return 0; // return 0 on success or 1 on failure.        
    }
}
```
