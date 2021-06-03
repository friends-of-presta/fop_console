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

namespace FOP\Console\Commands\Modules;

use FOP\Console\Command;
use Module;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

class UselessModules extends Command
{
    /**
     * @var array possible allowed command
     */
    const ALLOWED_COMMAND = ['status', 'uninstall', 'install', 'modulestats'];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('fop:modules')
            ->setDescription('Uninstall useless mdoules.')
            ->setHelp('This command Uninstall useless modules')
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
            );
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $helper = $this->getHelper('question');
        $returnCode = null;
        $action = $input->getArgument('action');
        $modulesInfos = [];
        $modulesStatsInfos = [];

        $uselessModules = ['gamification', 'ps_checkout', 'ps_eventbus', 'ps_metrics', 'psaddonsconnect', 'statsvisits', 'welcome'];

        $statsModules = ['statsbestcategories', 'statsbestcustomers', 'statsbestmanufacturers', 'statsbestproducts',
            'statsbestsuppliers', 'statsbestvouchers', 'statscarrier', 'statscatalog', 'statscheckup', 'statsdata',
            'statsequipment', 'statsforecast', 'statslive', 'statsnewsletter', 'statsorigin', 'statspersonalinfos',
            'statsproduct', 'statsregistrations', 'statssales', 'statssearch', 'statsstock', 'statsvisits', ];

        switch ($action) {
            case 'status':
                $io->text('<info>Useless Modules Informations</info>');

                foreach ($uselessModules as $key => $uselessModule) {
                    $modulesInfos[] = ['id' => $key, 'name' => $uselessModule, 'installed' => Module::isInstalled($uselessModule) ? 'yes' : 'no'];
                }

                $io->table(['ID', 'Name', 'Installed?'], $modulesInfos);
                $io->text('You can'
                    . PHP_EOL . '    - uninstall all modules by running  : `./bin/console fop:modules uninstall`'
                    . PHP_EOL . '    - uninstall modules by runing     : `./bin/console fop:modules uninstall --idsmodule x,y,z`'
                    . PHP_EOL . '    - uninstall one module by runing     : `./bin/console fop:modules uninstall --modulename ModuleName`'
                    . PHP_EOL . '    - install this modules by running    : `./bin/console fop:modules install`'
                    . PHP_EOL . '    - install modules by runing     : `./bin/console fop:modules install --idsmodule x,y,z`'
                    . PHP_EOL . '    - install one module by running      : `./bin/console fop:modules install --modulename ModuleName`');

                return 0;
                break;
            case 'uninstall':
                $moduleToUninstall = $input->getOption('modulename') ?? $helper->ask($input, $output, new Question('<question>Name of module to uninstall Press ENTER for all</question>'));
                if ($moduleToUninstall) {
                    if (Module::isInstalled($moduleToUninstall)) {
                        $arguments = [
                            'action' => 'uninstall',
                            'module name' => $moduleToUninstall,
                        ];

                        $command = $this->getApplication()->find('prestashop:module');
                        $greetInput = new ArrayInput($arguments);
                        $returnCode = $command->run($greetInput, $output);
                    } else {
                        $io->error('Module ' . $moduleToUninstall . ' is not installed');
                    }
                } else {
                    foreach ($uselessModules as $uselessModule) {
                        if (Module::isInstalled($uselessModule)) {
                            $arguments = [
                                'action' => 'uninstall',
                                'module name' => $uselessModule,
                            ];

                            $command = $this->getApplication()->find('prestashop:module');
                            $greetInput = new ArrayInput($arguments);
                            $returnCode = $command->run($greetInput, $output);
                        }
                    }
                }

                return 0;

                break;
            case 'install':
                $moduleToInstall = $input->getOption('modulename') ?? $helper->ask($input, $output, new Question('<question>Name of module to install Press ENTER for all</question>'));

                if ($moduleToInstall) {
                    if (!Module::isInstalled($moduleToInstall)) {
                        $arguments = [
                            'action' => 'install',
                            'module name' => $moduleToInstall,
                        ];

                        $command = $this->getApplication()->find('prestashop:module');
                        $greetInput = new ArrayInput($arguments);
                        $returnCode = $command->run($greetInput, $output);
                    } else {
                        $io->error('Module ' . $moduleToInstall . ' is not installed');
                    }
                } else {
                    foreach ($uselessModules as $uselessModule) {
                        if (!Module::isInstalled($uselessModule)) {
                            $arguments = [
                                'action' => 'install',
                                'module name' => $uselessModule,
                            ];

                            $command = $this->getApplication()->find('prestashop:module');
                            $greetInput = new ArrayInput($arguments);
                            $returnCode = $command->run($greetInput, $output);
                        }
                    }
                }

                return 0;

                break;
            case 'modulestats':
                foreach ($statsModules as $statsModule) {
                    $modulesStatsInfos[] = ['name' => $statsModule, 'installed' => Module::isInstalled($statsModule) ? 'yes' : 'no'];
                }

                $io->table(['Name', 'Installed?'], $modulesStatsInfos);

                break;
            case 'uninstallstats':
                foreach ($statsModules as $statsModule) {
                    if (Module::isInstalled($statsModule)) {
                        $arguments = [
                            'action' => 'uninstall',
                            'module name' => $statsModule,
                        ];

                        $command = $this->getApplication()->find('prestashop:module');
                        $greetInput = new ArrayInput($arguments);
                        $returnCode = $command->run($greetInput, $output);
                    }
                }

                return 0;

                break;
            default:
                $io->error("Action $action not allowed." . PHP_EOL . 'Possible actions : ' . $this->getPossibleActions());

                return 1;
        }
    }

    /*
    private function createCommand($string $action, string $module ) {

        $arguments = [
            'action' => $action,
            'module name' => $module,
        ];

        $command = $this->getApplication()->find('prestashop:module');
        $greetInput = new ArrayInput($arguments);
        return $command->run($greetInput, $output);

    }
    */

    private function getPossibleActions(): string
    {
        return implode(',', self::ALLOWED_COMMAND);
    }
}
