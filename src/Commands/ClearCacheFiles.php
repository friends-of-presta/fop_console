<?php
/**
 * 2020-present Friends of Presta community
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License
 * that is bundled with this package in the file LICENSE
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/MIT
 *
 * @author    Friends of Presta community
 * @copyright 2020-present Friends of Presta community
 * @license   https://opensource.org/licenses/MIT MIT
 */

namespace FOP\Console\Commands;

use FOP\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * This command replace the cache directory with an empty one.
 */
final class ClearCacheFiles extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('fop:clear-cache')
            ->setDescription('Replace the cache directory with an empty one.')
            ->setHelp('This command allows you to quickly remove the cache files.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $this->createFuturCacheDirectory();
        $this->renameCurrentCacheDirectory();
        $this->renameFuturCacheDirectoryAsCurrent();

        $io->error('New Empty cache directory created.');
    }

    private function createFuturCacheDirectory()
    {
        throw new \Exception('Implement me'); // @todo Implement me
    }

    private function renameCurrentCacheDirectory()
    {
        throw new \Exception('Implement me'); // @todo Implement me
    }

    private function renameFuturCacheDirectoryAsCurrent()
    {
        throw new \Exception('Implement me'); // @todo Implement me
    }
}
