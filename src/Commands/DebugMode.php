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
    const ALLOWED_COMMAND = ['status', 'enable', 'disable', 'toggle'];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('fop:debug')
            ->setDescription('Configure debug mode')
            ->setHelp('This command allows you to get or change debug mode')
            ->addArgument(
                'action',
                InputArgument::OPTIONAL,
                'enable or disable debug mode ( possible values : ' . implode(',', self::ALLOWED_COMMAND) . ') ',
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
        $debugMode = new DebugAdapter();
        $isDebugModEnabled = $debugMode->isDebugModeEnabled();

        if (!in_array($action, self::ALLOWED_COMMAND)) {
            $io->error('Action not allowed');

            return 1;
        }

        //Status
        if ($action == 'status') {
            $io->text('Current debug mode : ' . ((true === $isDebugModEnabled) ? 'enabled' : 'disabled'));

            return 0;
        }

        //Toggle Action
        if ($action == 'toggle') {
            (true === $isDebugModEnabled) ? $action = 'disable' : $action = 'enable';
        }

        //Enable action
        if ($action == 'enable') {
            $returnCode = $debugMode->enable();
        }
        //Disable action
        else {
            $returnCode = $debugMode->disable();
        }

        if ($returnCode === DebugAdapter::DEBUG_MODE_SUCCEEDED) {
            $io->success('Debug mode ' . $action . 'd with success');
        } else {
            $io->error('An error occured while updating debug mode');
        }

        return 0;
    }
}
