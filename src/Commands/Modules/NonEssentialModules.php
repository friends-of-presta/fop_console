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
 */

declare(strict_types=1);

namespace FOP\Console\Commands\Modules;

use FOP\Console\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class NonEssentialModules extends Command
{
    /**
     * @var array possible allowed command
     */
    private const ALLOWED_COMMAND = ['status', 'uninstall', 'install'];

    private const NON_ESSENTIAL_MODULES = ['emarketing', 'gamification', 'ps_checkout', 'ps_eventbus', 'ps_metrics', 'psaddonsconnect', 'statsvisits', 'welcome'];

    private const STATS_MODULES = ['statsbestcategories', 'statsbestcustomers', 'statsbestmanufacturers', 'statsbestproducts',
        'statsbestsuppliers', 'statsbestvouchers', 'statscarrier', 'statscatalog', 'statscheckup', 'statsdata',
        'statsequipment', 'statsforecast', 'statslive', 'statsnewsletter', 'statsorigin', 'statspersonalinfos',
        'statsproduct', 'statsregistrations', 'statssales', 'statssearch', 'statsstock', 'statsvisits', ];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('fop:modules')
            ->setDescription('Manage non-essential and stats modules.')
            ->setHelp('This command Uninstall or Install non-essential and stats modules.')
            ->addArgument(
                'action',
                InputArgument::OPTIONAL,
                '( possible values : ' . $this->getPossibleActions() . ') ',
                'status'
            )
            ->addOption(
                'modulename',
                null,
                InputOption::VALUE_OPTIONAL,
                'Module to Install / Uninstall'
            )
            ->addOption(
                'idsmodule',
                null,
                InputOption::VALUE_OPTIONAL,
                'IDs module to Install or Uninstall separate by coma'
            );
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $moduleManager = $this->getContainer()->get('prestashop.module.manager');
        $action = $input->getArgument('action');
        $modulesInfos = [];
        $modulesStatsInfos = [];

        switch ($action) {
            case 'status':
                $io->text('<info>Non-essential Modules Informations</info>');

                foreach (self::NON_ESSENTIAL_MODULES as $key => $uselessModule) {
                    $modulesInfos[] = ['id' => $key, 'name' => $uselessModule, 'installed' => $moduleManager->isInstalled($uselessModule) ? 'yes' : 'no'];
                }

                $io->table(['ID', 'Name', 'Installed?'], $modulesInfos);
                $io->text('You can'
                    . PHP_EOL . '    - uninstall all modules by running  : `./bin/console fop:modules uninstall`'
                    . PHP_EOL . '    - uninstall modules by runing       : `./bin/console fop:modules uninstall --idsmodule x,y,z`'
                    . PHP_EOL . '    - uninstall one module by runing    : `./bin/console fop:modules uninstall --modulename ModuleName`'
                    . PHP_EOL . '    - install all modules by running    : `./bin/console fop:modules install`'
                    . PHP_EOL . '    - install modules by runing         : `./bin/console fop:modules install --idsmodule x,y,z`'
                    . PHP_EOL . '    - install one module by running     : `./bin/console fop:modules install --modulename ModuleName`');

                return 0;
                break;
            case 'uninstall':
                $moduleToUninstall = $input->getOption('modulename');
                $idsModule = $input->getOption('idsmodule');

                if ($idsModule) {
                    $idsModule = explode(',', $idsModule);
                    foreach ($idsModule as $idModule) {
                        if ($moduleManager->isInstalled(self::NON_ESSENTIAL_MODULES[$idModule])) {
                            $command = $this->getApplication()->find('prestashop:module');
                            $returnCode = $command->run($this->createArguments('uninstall', self::NON_ESSENTIAL_MODULES[$idModule]), $output);
                        } else {
                            $io->error('Module ' . self::NON_ESSENTIAL_MODULES[$idModule] . ' is not installed');
                        }
                    }
                } elseif ($moduleToUninstall) {
                    if ($moduleManager->isInstalled($moduleToUninstall)) {
                        $command = $this->getApplication()->find('prestashop:module');
                        $returnCode = $command->run($this->createArguments('uninstall', $moduleToUninstall), $output);
                    } else {
                        $io->error('Module ' . $moduleToUninstall . ' is not installed');
                    }
                } else {
                    foreach (self::NON_ESSENTIAL_MODULES as $uselessModule) {
                        if ($moduleManager->isInstalled($uselessModule)) {
                            $command = $this->getApplication()->find('prestashop:module');
                            $returnCode = $command->run($this->createArguments('uninstall', $uselessModule), $output);
                        }
                    }
                }

                return 0;

                break;
            case 'install':
                $moduleToInstall = $input->getOption('modulename');
                $idsModule = $input->getOption('idsmodule');

                if ($idsModule) {
                    $idsModule = explode(',', $idsModule);
                    foreach ($idsModule as $idModule) {
                        if (!$moduleManager->isInstalled(self::NON_ESSENTIAL_MODULES[$idModule])) {
                            $command = $this->getApplication()->find('prestashop:module');
                            $returnCode = $command->run($this->createArguments('install', self::NON_ESSENTIAL_MODULES[$idModule]), $output);
                        } else {
                            $io->error('Module ' . self::NON_ESSENTIAL_MODULES[$idModule] . ' is not installed');
                        }
                    }
                } elseif ($moduleToInstall) {
                    if (!$moduleManager->isInstalled($moduleToInstall)) {
                        $command = $this->getApplication()->find('prestashop:module');
                        $returnCode = $command->run($this->createArguments('install', $moduleToInstall), $output);
                    } else {
                        $io->error('Module ' . $moduleToInstall . ' is not installed');
                    }
                } else {
                    foreach (self::NON_ESSENTIAL_MODULES as $uselessModule) {
                        if (!$moduleManager->isInstalled($uselessModule)) {
                            $command = $this->getApplication()->find('prestashop:module');
                            $returnCode = $command->run($this->createArguments('install', $uselessModule), $output);
                        }
                    }
                }

                return 0;

                break;
            case 'modulestats':
                $io->text('<info>Stats Modules Informations</info>');
                foreach (self::STATS_MODULES as $statsModule) {
                    $modulesStatsInfos[] = ['name' => $statsModule, 'installed' => $moduleManager->isInstalled($statsModule) ? 'yes' : 'no'];
                }

                $io->table(['Name', 'Installed?'], $modulesStatsInfos);

                return 0;

                break;
            case 'uninstallstats':
                foreach (self::STATS_MODULES as $statsModule) {
                    if ($moduleManager->isInstalled($statsModule)) {
                        $command = $this->getApplication()->find('prestashop:module');
                        $returnCode = $command->run($this->createArguments('uninstall', $statsModule), $output);
                    }
                }

                return 0;

                break;
            case 'installstats':
                foreach (self::STATS_MODULES as $statsModule) {
                    if ($moduleManager->isInstalled($statsModule)) {
                        $command = $this->getApplication()->find('prestashop:module');
                        $returnCode = $command->run($this->createArguments('install', $statsModule), $output);
                    }
                }

                return 0;

                break;
            default:
                $io->error("Action $action not allowed." . PHP_EOL . 'Possible actions : ' . $this->getPossibleActions());

                return 1;
        }
    }

    /**
     * @param string $action
     * @param string $module
     *
     * @return ArrayInput
     */
    private function createArguments(string $action, string $module): ArrayInput
    {
        $arguments = [
            'action' => $action,
            'module name' => $module,
        ];

        return new ArrayInput($arguments);
    }

    private function getPossibleActions(): string
    {
        return implode(',', self::ALLOWED_COMMAND);
    }
}
