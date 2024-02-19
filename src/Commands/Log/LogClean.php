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

namespace FOP\Console\Commands\Log;

use FOP\Console\Command;
use FOP\Console\Core\Domain\Log\Command\CleanLogCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class LogClean extends Command
{
    protected function configure(): void
    {
        $this->setName('fop:log:clean')
            ->setDescription('Clears the PrestaShop (legacy) logs.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $otto = $this->getContainer()->get('prestashop.core.query_bus');
            $cleanQuery = new CleanLogCommand();
            $otto->handle($cleanQuery);

            $this->io->warning('Not implemented.');
//            $this->io->success('Logs cleaned.');

            return 0;
        } catch (\Throwable $throwable) {
            if (_PS_MODE_DEV_ || $output->isVerbose()) {
                throw $throwable;
            }
            $this->io->error(sprintf('%s failed : %s', $this->getName(), $throwable->getMessage()));

            return 1;
        }
    }
}
