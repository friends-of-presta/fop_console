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

namespace FOP\Console\Commands\Environment;

use FOP\Console\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EnvironmentGetParameters extends Command
{
    private $environmentKeys = [
        'database_host',
        'database_port',
        'database_name',
        'database_user',
        'database_password',
        'database_prefix',
        'database_engine',
        'mailer_transport',
        'mailer_host',
        'mailer_user',
        'mailer_password',
        'secret',
        'ps_caching',
        'ps_cache_enable',
        'ps_creation_date',
        'locale',
        'use_debug_toolbar',
        'cookie_key',
        'cookie_iv',
        'new_cookie_key',
    ];

    protected function configure(): void
    {
        $this->setName('fop:environment:get-parameters')
            ->setDescription('Get your current configured parameters.')
            ->setHelp(
                '<info>This command is made to get current prestashop install parameters values.' . PHP_EOL .
                'Concerned parameters : ' . PHP_EOL .
                ' - ' . implode(PHP_EOL . ' - ', $this->environmentKeys) . '</info>'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ps_params = [];

        foreach ($this->environmentKeys as $environmentKey) {
            if ($this->getContainer()->hasParameter($environmentKey)) {
                $environmentValue = $this->getContainer()->getParameter($environmentKey);
                if ($environmentValue === null) {
                    $ps_params[] = [$environmentKey, '<info>NULL</info>'];
                } elseif ($environmentValue === true) {
                    $ps_params[] = [$environmentKey, '<info>true</info>'];
                } elseif ($environmentValue === false) {
                    $ps_params[] = [$environmentKey, '<info>false</info>'];
                } elseif (is_string($environmentValue)) {
                    $ps_params[] = [$environmentKey, '"' . $environmentValue . '"'];
                } elseif (is_numeric($environmentValue)) {
                    // please notice that $environmentValue isn't a string at this step (tested at previous if)
                    $ps_params[] = [$environmentKey, '<question>' . $environmentValue . '</question>'];
                } else {
                    $ps_params[] = [$environmentKey, $environmentValue];
                }
            } else {
                $ps_params[] = [$environmentKey, '<error>undefined</error>'];
            }
        }

        $table = new Table($output);
        $table
            ->setHeaders(['Parameter key', 'Value'])
            ->setRows($ps_params)
        ;
        $table->render();

        return 0;
    }
}
