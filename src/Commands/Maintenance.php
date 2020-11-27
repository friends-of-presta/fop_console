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

namespace FOP\Console\Commands;

use Configuration;
use FOP\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * This command display and change maintenance status.
 */
final class Maintenance extends Command
{
    /**
     * @var array possible allowed maintenance mode passed in command
     */
    const ALLOWED_COMMAND = ['enable', 'disable', 'toggle', 'status', 'ips', 'addip', 'addmyip'];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('fop:maintenance')
            ->setDescription('Configure maintenance mode')
            ->setHelp('This command allows you to get status or change maintenance mode')
            ->addArgument(
                'action',
                InputArgument::OPTIONAL,
                'get status or change maintenance mode ( possible values : ' . implode(',', self::ALLOWED_COMMAND) . ') ',
                'status'
            )
            ->addArgument('ipaddress', InputArgument::OPTIONAL, 'ip address to add');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $action = $input->getArgument('action');
        $ipaddress = trim($input->getArgument('ipaddress'));
        $isMaintenanceModeEnabled = !(bool) Configuration::get('PS_SHOP_ENABLE');

        //check if action is allowed
        if (!in_array($action, self::ALLOWED_COMMAND)) {
            $io->error('Action not allowed');

            return 1;
        }

        if ($action == 'status') {
            if ($isMaintenanceModeEnabled) {
                $io->text('Maintenance mode is on');
            } else {
                $io->text('Maintenance mode is off');
            }
        }

        //Define Toggle action
        if ($action == 'toggle') {
            (true === $isMaintenanceModeEnabled) ? $action = 'disable' : $action = 'enable';
        }

        //Enable maintenance mode
        if ($action == 'enable') {
            Configuration::updateValue('PS_SHOP_ENABLE', 0);
            $io->success('Maintenance mode enabled');
        }
        //Disable maintenance mode
        if ($action == 'disable') {
            Configuration::updateValue('PS_SHOP_ENABLE', 1);
            $io->success('Maintenance mode disabled');
        }

        // maintenance ip managment
        if ($action == 'addip' || $action == 'addmyip' || $action == 'ips') {
            $ips = explode(',', str_replace(' ', '', Configuration::get('PS_MAINTENANCE_IP')));
        }

        // list ips
        if ($action == 'ips') {
            if (!$ips) {
                $io->text('No maintenance ip for now.');
            } else {
                $io->listing($ips);
            }
        }

        // add ip
        if ($action == 'addip') {
            if (!$ipaddress) {
                $ipaddress = $io->ask('IP address to add');
            }
            if (!$ipaddress || !filter_var($ipaddress, FILTER_VALIDATE_IP)) {
                $io->error('Incorrect ip address.');
            } elseif (in_array($ipaddress, $ips)) {
                $io->error('Ip address ' . $ipaddress . ' already there');
            } else {
                // all good, add ip to the list
                $ips[] = $ipaddress;
                Configuration::updateValue('PS_MAINTENANCE_IP', implode(',', $ips));
                $io->success('Ip address ' . $ipaddress . ' added');
            }
        }

        // add my ip
        if ($action == 'addmyip') {
            // try to guess ssh client ip address
            $ipaddress = strtok(getenv('SSH_CLIENT'), ' ');
            if (!filter_var($ipaddress, FILTER_VALIDATE_IP)) {
                $ipaddress = null;
            }
            if (!$ipaddress) {
                $ipaddress = gethostbyname(gethostname());
            }
            if (!$ipaddress || !filter_var($ipaddress, FILTER_VALIDATE_IP)) {
                $io->error('Unable to guess your Ip address. Please use addip command.');
            } elseif (in_array($ipaddress, $ips)) {
                $io->warning('Ip address ' . $ipaddress . ' already there');
            } else {
                // all good, add ip to the list
                $ips[] = $ipaddress;
                Configuration::updateValue('PS_MAINTENANCE_IP', implode(',', $ips));
                $io->success('Ip address ' . $ipaddress . ' added');
            }
        }

        return 0;
    }
}
