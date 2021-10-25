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

namespace FOP\Console;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Symfony Command with legacy support.
 */
abstract class Command extends ContainerAwareCommand
{
    /** @var \Symfony\Component\Console\Style\SymfonyStyle */
    protected $io;

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $container = $this->getContainer();
        $commandDefinition = $this->getDefinition();
        $commandDefinition->addOption(new InputOption('employee', '-em', InputOption::VALUE_REQUIRED, 'Specify employee context (id).', null));

        $container->get('fop.console.console_loader')->loadConsoleContext($input);

        $this->io = new SymfonyStyle($input, $output);

        if (isset($_SERVER['argv']) && count($_SERVER['argv']) > 1
            && in_array($_SERVER['argv'][1], $this->getAliases())
        ) {
            $this->io->warning("This command has a new name : {$this->getName()}. The alias you entered is deprecated and will be deleted in version 2.");
        }

        parent::initialize($input, $output);
    }
}
