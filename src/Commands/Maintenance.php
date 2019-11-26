<?php

namespace FOP\Console\Commands;

use FOP\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Configuration;

/**
 * This command display and change maintenance status.
 */
final class Maintenance extends Command
{

    /**
     * @var array possible allowed maintenance mode passed in command
     */
    private $allowed_command_states = ['enable', 'disable', 'toggle', 'status', 'ips', 'addip', 'addmyip'];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('fop:console:maintenance')
            ->setDescription('Configure maintenance mode')
            ->setHelp('This command allows you to get status or change maintenance mode')
            ->addArgument(
                'action',
                InputArgument::OPTIONAL,
                'get status or change maintenance mode ( possible values : ' . implode(",", $this->allowed_command_states) . ') ' . PHP_EOL,
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
        if (!in_array($action, $this->allowed_command_states)) {
            $io->error('Action not allowed');
            return false;
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
        // list ips
        if ($action == 'ips') {
            $ips = Configuration::get('PS_MAINTENANCE_IP');
            if (!$ips) {
                $io->text('No maintenance ip for now.');
            } else {
                $io->text($ips);
            }
        }

        // add ip
        if ($action == 'addip') {
            $ips = Configuration::get('PS_MAINTENANCE_IP');
            if (!$ipaddress) {
                $ipaddress = $io->ask('IP address to add?');
            }
            if (!$ipaddress || !filter_var($ipaddress, FILTER_VALIDATE_IP)) {
                $io->error('Missing or incorrect ip address.');
            } else {
                Configuration::updateValue('PS_MAINTENANCE_IP', $ips.','.$ipaddress);
                $io->success('Ip address '.$ipaddress.' added');
            }
        }
        // add my ip
        if ($action == 'addmyip') {
            $ips = Configuration::get('PS_MAINTENANCE_IP');
            // try to guess ssh client ip address
            //$ipaddress = shell_exec('echo "${SSH_CLIENT%% *}"');
            if(!filter_var($ipaddress,FILTER_VALIDATE_IP)){
                $ipaddress = null;
            }
            if (!$ipaddress) {
                $ipaddress = gethostbyname(gethostname());
            }
            if (!$ipaddress || !filter_var($ipaddress,FILTER_VALIDATE_IP)) {
                $io->error('Unable to guess your Ip address. Please use addip command.');
            } else {
                Configuration::updateValue('PS_MAINTENANCE_IP', $ips.','.$ipaddress);
                $io->success('Ip address '.$ipaddress.' added');
            }
        }
        
    }
}
