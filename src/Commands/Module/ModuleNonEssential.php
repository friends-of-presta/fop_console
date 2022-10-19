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

namespace FOP\Console\Commands\Module;

use FOP\Console\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ModuleNonEssential extends Command
{
    /**
     * @var array possible allowed command
     */
    private const ALLOWED_COMMAND = ['status', 'uninstall', 'install'];

    private const NON_ESSENTIAL_MODULES = [
        'emarketing',
        'gamification',
        'ps_accounts',
        'ps_checkout',
        'ps_eventbus',
        'ps_facebook',
        'ps_metrics',
        'psaddonsconnect',
        'psxmarketingwithgoogle',
        'statsvisits',
        'welcome',
    ];

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('fop:module:non-essential')
            ->setAliases(['fop:modules:non-essential'])
            ->setDescription('Manage non-essential modules.')
            ->setHelp('This command Uninstall or Install non-essential modules.')
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
        $moduleManager = $this->getContainer()->get('prestashop.module.manager');
        $action = $input->getArgument('action');
        $update = false;
        $modulesInfos = [];

        switch ($action) {
            case 'status':
                $this->io->text('<info>Non-essential Modules Informations</info>');

                foreach (self::NON_ESSENTIAL_MODULES as $key => $uselessModule) {
                    $modulesInfos[] = ['id' => $key, 'name' => $uselessModule, 'present' => $this->moduleExists($uselessModule) ? 'yes' : 'no', 'installed' => $moduleManager->isInstalled($uselessModule) ? 'yes' : 'no'];
                }

                $this->io->table(['ID', 'Name', 'Present?', 'Installed?'], $modulesInfos);
                $this->io->text('You can'
                    . PHP_EOL . '    - uninstall all modules by running  : `./bin/console fop:modules:non-essential uninstall`'
                    . PHP_EOL . '    - uninstall modules by running       : `./bin/console fop:modules:non-essential uninstall --idsmodule x,y,z`'
                    . PHP_EOL . '    - uninstall one module by running    : `./bin/console fop:modules:non-essential uninstall --modulename ModuleName`'
                    . PHP_EOL . '    - install all modules by running    : `./bin/console fop:modules:non-essential install`'
                    . PHP_EOL . '    - install modules by running         : `./bin/console fop:modules:non-essential install --idsmodule x,y,z`'
                    . PHP_EOL . '    - install one module by running     : `./bin/console fop:modules:non-essential install --modulename ModuleName`');

                return 0;
            case 'uninstall':
                $moduleToUninstall = $input->getOption('modulename');
                $idsModule = $input->getOption('idsmodule');

                if ($idsModule) {
                    $idsModule = explode(',', $idsModule);
                    foreach ($idsModule as $idModule) {
                        $command = $this->getApplication()->find('prestashop:module');
                        if ($moduleManager->isInstalled(self::NON_ESSENTIAL_MODULES[$idModule])) {
                            $returnCode = $command->run($this->createArguments('uninstall', self::NON_ESSENTIAL_MODULES[$idModule]), $output);
                            $update = true;
                        } else {
                            $this->io->error('Module ' . self::NON_ESSENTIAL_MODULES[$idModule] . ' is not installed');

                            return 1;
                        }
                    }
                } elseif ($moduleToUninstall) {
                    if ($moduleManager->isInstalled($moduleToUninstall)) {
                        $command = $this->getApplication()->find('prestashop:module');
                        $returnCode = $command->run($this->createArguments('uninstall', $moduleToUninstall), $output);
                        $update = true;
                    } else {
                        $this->io->error('Module ' . $moduleToUninstall . ' is not installed');

                        return 1;
                    }
                } else {
                    foreach (self::NON_ESSENTIAL_MODULES as $uselessModule) {
                        $command = $this->getApplication()->find('prestashop:module');
                        if ($moduleManager->isInstalled($uselessModule)) {
                            $returnCode = $command->run($this->createArguments('uninstall', $uselessModule), $output);
                            $update = true;
                        }
                    }
                }

                if ($update == false) {
                    $this->io->text('<info>No module to uninstall</info>');
                }

                return 0;

            case 'install':
                $moduleToInstall = $input->getOption('modulename');
                $idsModule = $input->getOption('idsmodule');

                if ($idsModule) {
                    $idsModule = explode(',', $idsModule);
                    foreach ($idsModule as $idModule) {
                        $command = $this->getApplication()->find('prestashop:module');
                        if (!$moduleManager->isInstalled(self::NON_ESSENTIAL_MODULES[$idModule])) {
                            $returnCode = $command->run($this->createArguments('install', self::NON_ESSENTIAL_MODULES[$idModule]), $output);
                            $update = true;
                        } else {
                            $this->io->error('Module ' . self::NON_ESSENTIAL_MODULES[$idModule] . ' is not installed');

                            return 1;
                        }
                    }
                } elseif ($moduleToInstall) {
                    if (!$moduleManager->isInstalled($moduleToInstall)) {
                        $command = $this->getApplication()->find('prestashop:module');
                        $returnCode = $command->run($this->createArguments('install', $moduleToInstall), $output);
                        $update = true;
                    } else {
                        $this->io->error('Module ' . $moduleToInstall . ' is not installed');

                        return 1;
                    }
                } else {
                    foreach (self::NON_ESSENTIAL_MODULES as $uselessModule) {
                        $command = $this->getApplication()->find('prestashop:module');
                        if (!$moduleManager->isInstalled($uselessModule) && $this->moduleExists($uselessModule)) {
                            $returnCode = $command->run($this->createArguments('install', $uselessModule), $output);
                            $update = true;
                        }
                    }
                }

                if ($update == false) {
                    $this->io->text('<info>No module to install</info>');
                }

                return 0;

            default:
                $this->io->error("Action $action not allowed." . PHP_EOL . 'Possible actions : ' . $this->getPossibleActions());

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

    private function moduleExists($name): bool
    {
        return file_exists(_PS_MODULE_DIR_ . $name . '/' . $name . '.php');
    }
}
