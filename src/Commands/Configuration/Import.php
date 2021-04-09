<?php

declare(strict_types=1);

namespace FOP\Console\Commands\Configuration;

use Exception;
use FOP\Console\Command;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class Import extends Command
{
    private const PS_CONFIGURATIONS_FILE = 'ps_configurations.json';

    protected function configure(): void
    {
        $this->setName('fop:configuration:import')
            ->setDescription('Import configuration values')
            ->setHelp('Import configurations into ps_configuration table from a json file.'
            . PHP_EOL . ' To generate an json file use the fop:configuration:export command'
            )
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'file to import from', self::PS_CONFIGURATIONS_FILE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        try {
            /** @var \PrestaShop\PrestaShop\Adapter\Configuration $configuration_service */
            $configuration_service = $this->getContainer()->get('prestashop.adapter.legacy.configuration');

            $source_file = $input->getOption('file');
            if (!file_exists($source_file)) {
                throw new RuntimeException("File $source_file not found.");
            }
            $configurations = json_decode(file_get_contents($source_file), true);
            if (false === $configurations) {
                throw new Exception('Failed to decode json !');
            }

            if ($output->isVerbose()) {
                $io->writeln('Configuration imported : ');
                $rows = [];
                foreach ($configurations as $k => $v) {
                    $rows[] = [$k, $v];
                }
                $io->table(['Configuration name', 'value'], $rows);
            }
            $configuration_service->add($configurations);

            $io->success('Configurations imported');

            return 0;
        } catch (Exception $exception) {
            $io->error('Command ' . $this->getName() . ' aborted : ' . $exception->getMessage());

            return 1;
        }
    }
}
