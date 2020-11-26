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
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    Friends of Presta <infos@friendsofpresta.org>
 * @copyright since 2020 Friends of Presta
 * @license   https://opensource.org/licenses/AFL-3.0  Academic Free License ("AFL") v. 3.0
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

        $io->error('An error occured while updating debug mode. ' . DebugAdapter::class . ' error code ' . $returnCode . ' .');

        return 1;
    }

    private function getPossibleActions(): string
    {
        return implode(',', self::ALLOWED_COMMAND);
    }
}
