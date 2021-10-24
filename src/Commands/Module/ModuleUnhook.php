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

namespace FOP\Console\Commands\Module;

use FOP\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

final class ModuleUnhook extends Command
{
    protected function configure(): void
    {
        $this->setName('fop:module:unhook')
            ->setAliases(['fop:modules:unhook'])
            ->setDescription('Detach module from hook')
            ->setHelp('This command allows you to detach a module from one hook');
        $this->addUsage('--module=[modulename]');
        $this->addUsage('--hook=[hookname]');
        $this->addOption('module', null, InputOption::VALUE_OPTIONAL);
        $this->addOption('hook', null, InputOption::VALUE_OPTIONAL);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');

        $moduleName = $input->getOption('module') ?? $helper->ask($input, $output, new Question('<question>Wich module you want ungraft ?(name)</question>'));
        $moduleInst = \Module::getInstanceByName($moduleName);
        if (!($moduleInst instanceof \Module)) {
            $this->io->getErrorStyle()->error('This module doesn\'t exist, please give the name of the module directory.');

            return 1;
        }

        $hookName = $input->getOption('hook') ?? $helper->ask($input, $output, new Question('<question>On wich hook you want ungraft ' . $moduleName . ' ?(name)</question>'));
        $hookId = (int) \Hook::getIdByName($hookName);

        if (is_int($hookId) && $hookId > 0) {
            $moduleInst->unregisterHook($hookName);
            $this->io->getErrorStyle()->success('Your module ' . $moduleName . ' has been ungraft on hook ' . $hookName);

            return 0;
        } else {
            $this->io->getErrorStyle()->error('This hook doesn\'t exist, please check if this hook exist. Or create it !');

            return 1;
        }
    }
}
