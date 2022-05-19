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
use PrestaShop\PrestaShop\Adapter\Debug\DebugMode as DebugAdapter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EnvironmentDebug extends Command
{
    /**
     * @var array<int, string> commands
     */
    public const ALLOWED_COMMAND = ['status', 'enable', 'disable', 'toggle'];

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('fop:environment:debug')
            ->setAliases(['fop:debug-mode'])
            ->setDescription('Show, enable or disable debug mode.')
            ->setHelp('Get or change debug mode. Change _PS_MODE_DEV_ value.')
            ->addArgument(
                'action',
                InputArgument::OPTIONAL,
                'show, enable or disable debug mode ( possible values : ' . $this->getPossibleActions() . ') ',
                'status'
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->displayMessageIfDevModeEnvIsSet();

        $action = $input->getArgument('action');
        $debugMode = new DebugAdapter();
        $isDebugModEnabled = $debugMode->isDebugModeEnabled();

        switch ($action) {
            case 'status':
                $this->io->block('Current debug mode : ' . ($isDebugModEnabled ? '✅ enabled' : '❌ disabled'));

                return 0;
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
                $this->io->error("Action $action not allowed." . PHP_EOL . 'Possible actions : ' . $this->getPossibleActions());

                return 1;
        }

        if ($returnCode === DebugAdapter::DEBUG_MODE_SUCCEEDED) {
            $this->io->success('Debug mode changed : ' . ($debugMode->isDebugModeEnabled() ? '✅ enabled' : '❌ disabled') . '.');

            return 0;
        }

        $this->io->error('An error occurred while updating debug mode. ' . DebugAdapter::class . ' error code ' . $returnCode . ' .');

        return 1;
    }

    private function getPossibleActions(): string
    {
        return implode(',', self::ALLOWED_COMMAND);
    }

    /**
     * Display a message if PS_DEV_MODE environment variable is set.
     *
     * @see https://github.com/PrestaShop/docker/blob/master/base/config_files/defines_custom.inc.php
     *
     * @return void
     */
    private function displayMessageIfDevModeEnvIsSet(): void
    {
        $custom_defines_file_path = dirname(__DIR__, 5) . '/config/defines_custom.inc.php';
        $custom_defines_file_exists = file_exists($custom_defines_file_path);
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($custom_defines_file_path);
            opcache_invalidate(dirname(__DIR__, 5) . '/config/defines.inc.php');
        }

        if (false !== getenv('PS_DEV_MODE') || $custom_defines_file_exists) {
            $this->io->note('This command may show inaccurate state and changes may not work.' . PHP_EOL .
            'The environment variable PS_DEV_MODE is defined !' . PHP_EOL .
            'This can be the sign that the php constant _PS_MODE_DEV_ is handled at runtime by the environment variable PS_DEV_MODE.' . PHP_EOL .
            'You may change the env mode by changing it\'s value or rebuild a docker container.');

            !$custom_defines_file_exists
                ?: $this->io->note("'$custom_defines_file_path' exists, check it.");

            $evaluatedDevMode = (bool) getenv('PS_DEV_MODE');
            $this->io->note(
                'The current value of PS_DEV_MODE environment variable is "' . getenv('PS_DEV_MODE') . '"' . PHP_EOL
            . 'So the PS_DEV_MODE may be ' . ($evaluatedDevMode ? 'enabled' : 'disabled') . '.'
            );
        }
    }
}
