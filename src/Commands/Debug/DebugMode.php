<?php

namespace FOP\Console\Commands\Debug;

use FOP\Console\Command;
use PrestaShop\PrestaShop\Adapter\Debug\DebugMode as DebugAdapter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DebugMode extends Command
{
    /**
     * @var array possible allowed dev mode passed in command
     */
    private $allowed_command_states = ['enable', 'disable', 'toggle'];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('fop:console:debug:mode')
            ->setDescription('Configure debug mode')
            ->setHelp('This command allows you to enable,disable or toggle debug mode')
            ->addArgument('action', InputArgument::OPTIONAL,
                'enable or disable debug mode ( possible values : ' . implode(',', $this->allowed_command_states) . ') ' . PHP_EOL,
                'toggle'
            );
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $action = $input->getArgument('action');
        $debugMode = new DebugAdapter();
        $isDebugModEnabled = $debugMode->isDebugModeEnabled();

        //check if action is allowed
        if (!in_array($action, $this->allowed_command_states)) {
            $io->error('Action not allowed');

            return false;
        }

        //Define Toggle action
        if ($action == 'toggle') {
            (true === $isDebugModEnabled) ? $action = 'disable' : $action = 'enable';
        }

        //Enable dev mode
        if ($action == 'enable') {
            $returnCode = $debugMode->enable();
        } //Disable dev mode
        else {
            $returnCode = $debugMode->disable();
        }

        if ($returnCode === DebugAdapter::DEBUG_MODE_SUCCEEDED) {
            $io->success('Debug mode updated with success');
        } else {
            $io->error('An error occured while updating debug mode');
        }
    }
}
