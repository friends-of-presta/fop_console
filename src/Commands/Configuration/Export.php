<?php

declare(strict_types=1);

namespace FOP\Console\Commands\Configuration;

use FOP\Console\Command;
use RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

final class Export extends Command
{
    private const PS_CONFIGURATIONS_FILE = 'ps_configurations.json';

    protected function configure(): void
    {
        $this->setName('fop:configuration:export')
            ->setDescription('Export configuration values')
            ->setHelp('Dump configuration to a json file.')
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'file to dump to', self::PS_CONFIGURATIONS_FILE)
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'overwrite existing file')
            ->addArgument('keys', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'configuration values to export');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $configuration_keys = (array) $input->getArgument('keys');
        $output_file = $input->getOption('file');
        $force_mode = $input->getOption('force');
        $fs = new Filesystem();

        if ($fs->exists($output_file)
            && !$force_mode
            && !$io->confirm('Overwrite ' . self::PS_CONFIGURATIONS_FILE . ' ?', false)
        ) {
            $io->comment($this->getName() . ' command aborted, ' . self::PS_CONFIGURATIONS_FILE . ' not touched.');

            return 0;
        }

        /** @var \PrestaShop\PrestaShop\Adapter\Configuration $configuration_service */
        $configuration_service = $this->getContainer()->get('prestashop.adapter.legacy.configuration');

        // @todo what to do if configuration key not found ?
        // for the moment let's emit a simple warning. (throw an error is better ?)
        // or event create an empty configuration value ?
        $to_export = [];
        foreach ($configuration_keys as $key) {
            if (!$configuration_service->has($key)) {
                $io->warning(sprintf("Configuration key not found '%s' : ignored.", $key));
                continue;
            }

            $to_export[$key] = $configuration_service->get($key);
        }

        $json_export = json_encode($to_export, JSON_PRETTY_PRINT);
        if (false === $json_export) { // useless ?
            if ($io->isVeryVerbose()) {
                $io->writeln('$to_export:');
                dump($to_export);
            }
            throw new RuntimeException('Failed to json encode configuration');
        }

        // @todo : dump to stdout if on file provided or dump to a default file ?
        // dump to a default file and add std output option
        $fs = new Filesystem();
        $fs->dumpFile($output_file, $json_export);

        // @todo list dumped configurations if verbose mode
        $io->success("configuration(s) dumped to file '{$output_file}'");

        return 1;
    }
}
