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

use Db;
use DbQuery;
use FOP\Console\Command;
use RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;

final class ConfigurationExport extends Command
{
    /** @var string Default export filename */
    private const PS_CONFIGURATIONS_FILE = 'ps_configurations.json';

    /** @var int When output to standart output, some truncation is needed for a correct display */
    public const STDOUT_VALUE_MAX_LENGTH = 100;

    /** @var string How null value is rendered on standard output */
    public const NULL_REPRESENTATION = '<null>';

    /** @var array<int, string> Configuration keys to dump, provided by command line. */
    private $configuration_keys;

    /** @var bool */
    private $overwrite_existing_file = false;

    protected function configure(): void
    {
        $this->setName('fop:configuration:export')
            ->setDescription('Export configuration values (from ps_configuration table).')
            ->setHelp(
                'Dump configuration(s) to a json file or to standard output (--stdout).'
                . PHP_EOL . 'Exported file can later be used to import values, using <note>fop:configuration:import</note> command.'
                . PHP_EOL . '<keys> are configuration names, "PS_LANG_DEFAULT" for example, multiple keys can be provided.'
                . PHP_EOL . '<keys> can also be a mysql like expression : "PSGDPR_%" to export all configuration starting with "PSGDPR_" for example.'
                . PHP_EOL . 'To print output instead of writing to file use the --stdout option'
                . PHP_EOL . PHP_EOL . 'This command is not multishop compliant, neither multilang. (Contributions are welcome)'
                . PHP_EOL . PHP_EOL . 'Examples :'
                . PHP_EOL . 'dump one value : <info>./bin/console fop:configuration:export PS_COUNTRY_DEFAULT</info>'
                . PHP_EOL . 'dump multiples values : <info>./bin/console fop:configuration:export PS_COMBINATION_FEATURE_ACTIVE PS_CUSTOMIZATION_FEATURE_ACTIVE PS_FEATURE_FEATURE_ACTIVE
</info>'
                . PHP_EOL . 'dump multiples values using mysql "like" syntax : <info>./bin/console fop:configuration:export --file configuration_blocksocial.json BLOCKSOCIAL_%</info>'
                . PHP_EOL . 'dump value to standard output : <info>./bin/console fop:configuration:export --stdout BLOCKSOCIAL_%</info>'
            )
            ->addOption('file', null, InputOption::VALUE_OPTIONAL, 'file to dump to', self::PS_CONFIGURATIONS_FILE)
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'overwrite existing file')
            ->addOption('stdout', null, InputOption::VALUE_NONE, 'write to standard output instead of file')
            ->addArgument('keys', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'configuration values to export');
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $output_file = $input->getOption('file') ? (string) $input->getOption('file') : null;
        $force_mode = $input->getOption('force');
        $stdout = $input->getOption('stdout');
        $fs = new Filesystem();

        if (!$stdout
            && $fs->exists($output_file)
            && !$force_mode
            && $this->io->confirm(sprintf('Overwrite %s ? ', $output_file), false)
        ) {
            $this->overwrite_existing_file = true;
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->configuration_keys = (array) $input->getArgument('keys');
        $output_file = $input->getOption('file') ? (string) $input->getOption('file') : null;
        $this->overwrite_existing_file = $this->overwrite_existing_file || $input->getOption('force');
        $stdout = (bool) $input->getOption('stdout');

        try {
            $configuration_values = $this->getConfigurationValues();
            $stdout
                ? $this->printToStandardOutput($configuration_values)
                : $this->writeToFile($configuration_values, $output_file);

            return 0;
        } catch (Throwable $exception) {
            $this->io->error("{$this->getName()} : {$exception->getMessage()}");

            return 1;
        }
    }

    /**
     * @return array<string, mixed>
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    private function getConfigurationValues(): array
    {
        /** @var \PrestaShop\PrestaShop\Adapter\Configuration<string> $configuration_service */
        $configuration_service = $this->getContainer()->get('prestashop.adapter.legacy.configuration');

        $to_export = [];
        foreach ($this->configuration_keys as $key) {
            // keys to query with a 'like' syntaxe from db
            if (false !== strpos($key, '%')) {
                $to_export = array_merge($to_export, $this->queryConfigurationsLike($key));
                continue;
            }

            if (!$configuration_service->has($key)) {
                $this->io->warning(sprintf("Configuration key not found '%s' : ignored.", $key));
                continue;
            }

            $to_export[$key] = $configuration_service->get($key);
        }

        return $to_export;
    }

    /**
     * @param array<string, mixed> $configuration_values
     * @param string $output_file
     *
     * @return void
     */
    private function writeToFile(array $configuration_values, string $output_file): void
    {
        $json_export = json_encode($configuration_values, JSON_PRETTY_PRINT);
        if (false === $json_export) {
            throw new RuntimeException('Failed to json encode configuration');
        }

        $fs = new Filesystem();
        if (false === $this->overwrite_existing_file && $fs->exists($output_file)) {
            throw new RuntimeException('Output file exists, command aborted.');
        }
        $fs->dumpFile($output_file, $json_export);

        $this->io->success("configuration(s) dumped to file '$output_file'");
    }

    /**
     * @param array<string, mixed> $configuration_values
     *
     * @return void
     */
    private function printToStandardOutput(array $configuration_values): void
    {
        $truncate = static function (string $value) {
            $tr_mark = strlen($value) < self::STDOUT_VALUE_MAX_LENGTH ? '' : ' (...)';

            return substr($value, -self::STDOUT_VALUE_MAX_LENGTH) . $tr_mark;
        };

        $configurations_with_keys_as_first_value = array_map(
            static function ($value, $key) use ($truncate) {
                $value = is_string($value)
                    ? $truncate($value)
                    : $value ?? '<info>' . self::NULL_REPRESENTATION . '</info>'; // not a string, probably null, but let's be careful.

                return [$key, $value];
            },
            $configuration_values,
            array_keys($configuration_values)
        );

        $this->io->table(['Configuration name', 'Value'], $configurations_with_keys_as_first_value);
    }

    /**
     * @param string $key_like_term
     *
     * @return array<string, string> [name => value, ...]
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    private function queryConfigurationsLike(string $key_like_term): array
    {
        $query = new DbQuery();
        $query->select('name, value')
            ->from('configuration')
            ->where(sprintf('name LIKE "%s"', $key_like_term));

//        $db = $this->getContainer()->get('prestashop.adapter.legacy_db'); // not on ps 1.7.5
        $db = Db::getInstance();
        $results = $db->executeS($query);
        if (!is_array($results)) {
            dump($query->build(), $db->getMsgError());
            throw new RuntimeException('sql query error : see dump above.');
        }

        return array_column($results, 'value', 'name');
    }
}
