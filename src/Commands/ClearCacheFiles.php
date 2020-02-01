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
use http\Exception\RuntimeException;
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

        $this->processChecks();
        $this->renameCurrentCacheDirectory();
        $this->createNewCacheDirectory();
        $this->deleteOldCacheDirectory();

        $io->success('New Empty cache directory created. Old cache directory deleted.');
    }

    private function processChecks(): void
    {
        $cache_directory = $this->getCacheDirectoryPath();
        if (!is_writable($cache_directory)) {
            throw new RuntimeException("Cache directory not writable : [$cache_directory]");
        }
    }

    private function renameCurrentCacheDirectory()
    {
        throw new \Exception('Implement me'); // @todo Implement me
    }

    private function deleteOldCacheDirectory()
    {
        throw new \Exception('Implement me'); // @todo Implement me
    }

    private function createNewCacheDirectory()
    {
        throw new \Exception('Implement me'); // @todo Implement me
    }
}
