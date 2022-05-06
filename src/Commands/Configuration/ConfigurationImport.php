<?php
/**
 * Copyright (c) Since 2020 Friends of Presta
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file docs/licenses/LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to infos@friendsofpresta.org so we can send you a copy immediately.
 *
 * @author    Friends of Presta <infos@friendsofpresta.org>
 * @copyright since 2020 Friends of Presta
 * @license   https://opensource.org/licenses/AFL-3.0  Academic Free License ("AFL") v. 3.0
 *
 */

declare(strict_types=1);

namespace FOP\Console\Commands\Configuration;

use Exception;
use FOP\Console\Command;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class ConfigurationImport extends Command
{
    private const PS_CONFIGURATIONS_FILE = 'ps_configurations.json';

    protected function configure(): void
    {
        $this->setName('fop:configuration:import')
            ->setDescription('Import configuration values')
            ->setHelp(
                'Import configurations into ps_configuration table from a json file.'
            . PHP_EOL . ' To generate an json file use the fop:configuration:export command'
            )
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'file to import from', self::PS_CONFIGURATIONS_FILE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
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
                $this->io->writeln('Configuration imported : ');
                $rows = [];
                foreach ($configurations as $k => $v) {
                    $rows[] = [$k, $v];
                }
                $this->io->table(['Configuration name', 'value'], $rows);
            }
            $configuration_service->add($configurations);

            $this->io->success('Configurations imported');

            return 0;
        } catch (Exception $exception) {
            $this->io->error('Command ' . $this->getName() . ' aborted : ' . $exception->getMessage());

            return 1;
        }
    }
}
