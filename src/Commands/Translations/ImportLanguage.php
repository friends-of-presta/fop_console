<?php

namespace FOP\Console\Commands\Translations;

use FOP\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ImportLanguage extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('fop:translations:import')
            ->setDescription('Import a translation by iso code')
            ->setHelp('This command will import or update a translation package')
            ->addUsage('./bin/console fop:translations:import fr import fr translation package ')
            ->addArgument(
                'iso',
                InputOption::VALUE_REQUIRED,
                'iso of language to import ex: fr',
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $iso = $input->getArgument('iso');
		$languagePackImporter = $this->getContainer()->get('prestashop.adapter.language.pack.importer');
        $errors =  $languagePackImporter->import($iso);
        if (empty($errors)) {
            $this->io->text('lang '.$iso.' added !');
        }

        return Command::SUCCESS;
    }
}