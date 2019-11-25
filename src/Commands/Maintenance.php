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
    private $allowed_command_states = ['enable', 'disable', 'toggle', 'status'];

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
            );
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $action = $input->getArgument('action');
        $isMaintenanceModeEnabled = !(bool) Configuration::get('PS_SHOP_ENABLE');
        
        //check if action is allowed
        if (!in_array($action, $this->allowed_command_states)) {
            $io->error('Action not allowed');
            return false;
        }

        if ($action == 'status') {
            if ($isMaintenanceModeEnabled) {
                $io->title('Maintenance mode is on');
            } else {
                $io->title('Maintenance mode is off');
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
    }
}
