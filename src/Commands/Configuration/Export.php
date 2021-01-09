<?php

namespace FOP\Console\Commands\Configuration;

use FOP\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Export extends Command
{
    protected function configure()
    {
        $this->setName('fop:configuration:export')
            ->setDescription('Export configuration values')
            ->addArgument('keys', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'configuration values to export')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Hello !');
    }
}
