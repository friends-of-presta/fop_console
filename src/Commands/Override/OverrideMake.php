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

declare(strict_types=1);

namespace FOP\Console\Commands\Override;

use Exception;
use FOP\Console\Command;
use FOP\Console\Overriders\OverriderInterface;
use FOP\Console\Overriders\Provider;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class OverrideMake extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('fop:override:make')
            ->setDescription('Generate a file to make an override.')
            ->setHelp(
                'This command provides a quick way to generate on override.'
                . PHP_EOL . 'Just provide the path of the file to override.'

                . PHP_EOL . PHP_EOL . 'Examples : '
                . PHP_EOL . '* Override a legacy class or controller :'
                . PHP_EOL . '  <info>./bin/console fop:override:make classes/Link.php </info>'
                . PHP_EOL . '  It creates a file <comment>override/classes/Link.php</comment> with the right class name and parent <comment>class Link extends LinkCore {}</comment>'

                . PHP_EOL . PHP_EOL . '* Override a module :'
                . PHP_EOL . '  <info>./bin/console fop:override:make modules/ps_searchbar/ps_searchbar.php</info>'
                . PHP_EOL . '  It creates a file <comment>override/modules/ps_searchbar/ps_searchbar.php</comment> with <comment>class ps_searchbarOverride extends ps_searchbar {}</comment>'

                . PHP_EOL . PHP_EOL . '* Override a module template :'
                . PHP_EOL . '  <info>./bin/console fop:override:make modules/ps_searchbar/ps_searchbar.tpl </info>'
                . PHP_EOL . '  It creates a copy of source file in the current theme : <comment>themes/classic/modules/ps_searchbar/ps_searchbar.tpl</comment>'

                . PHP_EOL . PHP_EOL . ' need more ? need to override symfony services and objects ? Go contribute : https://github.com/friends-of-presta/fop_console/'
            )
            ->addArgument('path', InputArgument::REQUIRED, 'file to override.')
            ->addOption('force', null, InputOption::VALUE_NONE, 'overwrite files without confirmation');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = (string) $input->getArgument('path'); /* @-phpstan-ignore-line - annotation disabled - not an error at level 5*/

        try {
            // gather overriders
            $overriders = $this->getOverriders($path);
            if (empty($overriders)) {
                $this->io->comment("No Overrider for path '$path' fails");
                $this->io->comment("Looking for a demo ? try with 'classes/README.md' ...");

                return 0;
            }

            // run overriders
            $error_messages = $success_messages = [];
            foreach ($overriders as $overrider) {
                $dangerous_consequences = $overrider->getDangerousConsequences();
                $run_overrider = is_null($dangerous_consequences)
                    || $input->getOption('force')
                    || $this->io->confirm($overrider->getDangerousConsequences() . ' Process anyway ? ', false);

                if (!$run_overrider) {
                    $messages = ['Run aborted. Confirm actions or use --force to bypass.'];
                } else {
                    $messages = $overrider->run();
                }

                $overrider->isSuccessful()
                    ? $success_messages += $messages
                    : $error_messages += $messages;
            }

            // display results
            empty($success_messages) ?: $this->io->success($success_messages);
            empty($error_messages) ?: $this->io->warning($error_messages);

            return 0;
        } catch (Exception $exception) {
            $this->io->error(["Override for '$path' failed", $exception->getMessage()]);
            // Caught Exception get rethrown at high verbosity (-vvv)
            if (OutputInterface::VERBOSITY_DEBUG === $output->getVerbosity()) {
                // unhandled Exception, that's intended, for debugging.
                throw $exception;
            }

            return 1;
        }
    }

    /**
     * @param string $path
     *
     * @return OverriderInterface[]
     */
    private function getOverriders(string $path): array
    {
        /** @var Provider $override_provider */
        $override_provider = $this->getContainer()->get('fop.console.overrider_provider');

        return $override_provider->getOverriders($path);
    }
}
