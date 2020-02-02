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
use PrestaShop\PrestaShop\Adapter\Debug\DebugMode as DebugAdapter;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Process\Process;

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

        try {
            // first : disable event dispatcher otherwise errors will happen
            $this->getApplication()->setDispatcher(new EventDispatcher());
            $this->processChecks();
            $this->deleteOldCacheDirectory(); // may exist if this command failed before
            $this->renameCurrentCacheDirectory();
            $this->createNewCacheDirectory(); // probably not needed
            $io->success('New Empty cache directory created. Old cache directory deleted.');

            $this->deleteOldCacheDirectory();
            $io->text('Old directory deleted');

            return 0;
        } catch (RuntimeException $exception) {
            $io->error("Error processing {$this->getName()}:\u{a0}" . $exception->getMessage());

            return 1;
        }
    }

    private function processChecks(): void
    {
        $cache_directory = $this->getCacheDirectoryBasePath();
        if (!is_writable($cache_directory)) {
            throw new RuntimeException("Cache directory not writable : [$cache_directory]");
        }
    }

    private function renameCurrentCacheDirectory()
    {
        $process = new Process(['mv', $this->getCacheDirectoryBasePath(), $this->getCacheDirectoryOldPath()]);
        $process->run();
        $this->handleUnsucessfullProcess(__FUNCTION__, $process);
    }

    private function deleteOldCacheDirectory()
    {
        if (file_exists($this->getCacheDirectoryOldPath())) {
            $process = new Process(['rm', '-rf', $this->getCacheDirectoryOldPath()/*.'/'*/]); // final slash needed
            $process->run();
            $this->handleUnsucessfullProcess(__FUNCTION__, $process);
        }
    }

    private function createNewCacheDirectory()
    {
        $cache_dir = $this->getCacheDirectoryBasePath() . ((new DebugAdapter())->isDebugModeEnabled() ? 'dev' : 'prod');
        $process = new Process(['mkdir', $cache_dir]);
        $process->run();
        $this->handleUnsucessfullProcess(__FUNCTION__, $process);
    }

    /**
     * @return string Cache directory path without env final directory (eg. dev|prod) without trailing slash
     *
     * @throws RuntimeException
     */
    private function getCacheDirectoryBasePath(): string
    {
        if (!defined('_PS_CACHE_DIR_')) {
            throw new RuntimeException('Cache directory path not defined in _PS_CACHE_DIR_');
        }

        return preg_replace('!\\' . DIRECTORY_SEPARATOR . '(prod|dev)\\' . DIRECTORY_SEPARATOR . '$!', '', _PS_CACHE_DIR_);
    }

    private function getCacheDirectoryOldPath(): string
    {
        return $this->getCacheDirectoryBasePath() . '_old';
    }

    private function handleUnsucessfullProcess(string $__FUNCTION__, Process $process)
    {
        if (!$process->isSuccessful()) {
            throw new RuntimeException("Error doing $__FUNCTION__ : " . PHP_EOL . ' : ' . $process->getErrorOutput());
        }
    }
}
