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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tools;

class GenerateRobots extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $input->getOption('executeHook') ? $executeHook = true : $executeHook = false;
        $io = new SymfonyStyle($input, $output);

        if (true === Tools::generateRobotsFile($executeHook)) {
            $io->success('robots.txt file generated with success');
        } else {
            $io->error('An error occurs while generating robots.txt file');

            return 1;
        }
    }
}
