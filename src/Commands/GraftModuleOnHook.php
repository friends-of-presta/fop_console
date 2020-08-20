<?php
/**
 * 2019-present Friends of Presta community
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License
 * that is bundled with this package in the file LICENSE
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/MIT
 *
 * @author Friends of Presta community
 * @copyright 2019-present Friends of Presta community
 * @license https://opensource.org/licenses/MIT MIT
 */

namespace FOP\Console\Commands;

use FOP\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

class GraftModuleOnHook extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('fop:graft-module-on-hook')
            ->setDescription('Attach one module on specific hook')
            ->setHelp('This command allows you to attach a module on one hook');
        
       $this->addOption('module', null, InputOption::VALUE_OPTIONAL);
       $this->addOption('hook', null, InputOption::VALUE_OPTIONAL);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return bool
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $helper = $this->getHelper('question');

        $moduleName = $input->getOption('module') ?? $helper->ask($input, $output, new Question('<question>Wich module you want graft ?(name)</question>'));

        $moduleInst = \Module::getInstanceByName($moduleName);
        if (!($moduleInst instanceof \Module)) {
            $io->getErrorStyle()->error('This module not exist, please give the name of the module directory.');

            return false;
        }

        $hookName = $input->getOption('hook') ?? $helper->ask($input, $output, new Question('<question>On wich hook you want graft ' . $moduleName . ' ?(name)</question>'));
        $hookId = (int) \Hook::getIdByName($hookName);

        if (is_int($hookId) && $hookId > 0) {
            $moduleInst->registerHook($hookName);
            $io->getErrorStyle()->success('Your module ' . $moduleName . ' has been graft on hook ' . $hookName);

            return true;
        } else {
            $io->getErrorStyle()->error('This hook not exist, please check if this hook exist. Or create it !');
        }
    }
}
