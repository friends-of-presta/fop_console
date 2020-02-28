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
            ->setName('fop:debug-mode')
            ->setDescription('Enable or Disable debug mode.')
            ->setHelp('Get or change debug mode. Change _PS_MODE_DEV_ value.')
            ->addArgument(
                'action',
                InputArgument::OPTIONAL,
                'enable or disable debug mode ( possible values : ' . $this->getPossibleActions() . ') ',
                'status'
            );
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $returnCode = null;
        $action = $input->getArgument('action');
        $debugMode = new DebugAdapter();
        $isDebugModEnabled = $debugMode->isDebugModeEnabled();

        switch ($action) {
            case 'status':
                $io->text('Current debug mode : ' . ($isDebugModEnabled ? 'enabled' : 'disabled'));
                return 0;
                break;
            case 'toggle':
                $returnCode = $isDebugModEnabled
                    ? $debugMode->disable()
                    : $debugMode->enable();
                break;
            case 'enable':
                $returnCode = $debugMode->enable();
                break;
            case 'disable':
                $returnCode = $debugMode->disable();
                break;
            default:
                $io->error("Action $action not allowed." . PHP_EOL . 'Possible actions : ' . $this->getPossibleActions());

                return 1;
        }

        if ($returnCode === DebugAdapter::DEBUG_MODE_SUCCEEDED) {
            $io->success('Debug mode changed : ' . ($debugMode->isDebugModeEnabled() ? 'enabled' : 'disabled') . '.');

            return 0;
        }

        $io->error('An error occured while updating debug mode. '.DebugAdapter::class.' error code '.$returnCode.' .');

        return 1;
    }

    private function getPossibleActions(): string
    {
        return implode(',', self::ALLOWED_COMMAND);
    }
}
