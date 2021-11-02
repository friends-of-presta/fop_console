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

namespace FOP\Console\Commands\Generate;

use FOP\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tools;

final class GenerateRobots extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('fop:generate:robots')
            ->setDescription('Generate the robots.txt file')
            ->addOption(
                'executeHook',
                null,
                InputOption::VALUE_OPTIONAL,
                'Generate actionAdminMetaBeforeWriteRobotsFile hook ?'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $input->getOption('executeHook') ? $executeHook = true : $executeHook = false;

        if (true !== Tools::generateRobotsFile($executeHook)) {
            $this->io->error('An error occurs while generating robots.txt file');

            return 1;
        }

        $this->io->success('robots.txt file generated with success');

        return 0;
    }
}
