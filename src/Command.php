<?php

namespace FOP\Console;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

/**
* Symfony Command class for PrestaShop allowed to rely on legacy classes
*/
abstract class Command extends ContainerAwareCommand
{
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $commandDefinition = $this->getDefinition();
        $commandDefinition->addOption(new InputOption('employee', '-em', InputOption::VALUE_REQUIRED, 'Specify employee context (id).', null));

        $container->get('fop.console.console_loader')->loadConsoleContext($input);

        return parent::initialize($input, $output);
    }
}
