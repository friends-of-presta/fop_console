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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UninstallUselessModules extends Command
{
    /**
     * @var array possible allowed dev mode passed in command
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
            );
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $returnCode = null;
        $action = $input->getArgument('action');
        $modulesInfos = [];
        $modulesStatsInfos = [];

        $uselessModules = ['gamification', 'ps_eventbus', 'ps_metrics', 'psaddonsconnect', 'welcome'];

        $statsModules = ['statsbestcategories', 'statsbestcustomers', 'statsbestmanufacturers', 'statsbestproducts',
            'statsbestsuppliers', 'statsbestvouchers', 'statscarrier', 'statscatalog', 'statscheckup', 'statsdata',
            'statsequipment', 'statsforecast', 'statslive', 'statsnewsletter', 'statsorigin', 'statspersonalinfos',
            'statsproduct', 'statsregistrations', 'statssales', 'statssearch', 'statsstock', 'statsvisits', ];

        switch ($action) {
            case 'status':
                $io->text('<info>Useless Modules Informations</info>');

                foreach ($uselessModules as $uselessModule) {
                    $modulesInfos[] = ['name' => $uselessModule, 'installed' => Module::isInstalled($uselessModule) ? 'yes' : 'no'];
                }

                $io->table(['Name', 'Installed?'], $modulesInfos);
                $io->text('You can uninstall this modules by running : `./bin/console fop:modules uninstall`');
                $io->text('You can install this modules by running : `./bin/console fop:modules install`');

                return 0;
                break;
            case 'uninstall':
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

                return 0;

                break;
            case 'install':
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

                return 0;

                break;
            case 'modulestats':
                foreach ($statsModules as $statsModule) {
                    $modulesStatsInfos[] = ['name' => $statsModule, 'installed' => Module::isInstalled($statsModule) ? 'yes' : 'no'];
                }

                $io->table(['Name', 'Installed?'], $modulesStatsInfos);

                break;
            default:
                $io->error("Action $action not allowed." . PHP_EOL . 'Possible actions : ' . $this->getPossibleActions());

                return 1;
        }
    }

    private function getPossibleActions(): string
    {
        return implode(',', self::ALLOWED_COMMAND);
    }
}
