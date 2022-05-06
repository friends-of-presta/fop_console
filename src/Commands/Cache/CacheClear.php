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

namespace FOP\Console\Commands\Cache;

use FOP\Console\Command;
use PrestaShop\PrestaShop\Adapter\Debug\DebugMode as DebugAdapter;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Process\Process;

/**
 * This command replace the cache directory with an empty one.
 */
final class CacheClear extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('fop:cache:clear')
            ->setAliases(['fop:clear-cache'])
            ->setDescription('Replace the cache directory with an empty one.')
            ->setHelp('This command allows you to quickly remove the cache files.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            // first : disable event dispatcher otherwise errors will happen
            $this->getApplication()->setDispatcher(new EventDispatcher());
            $this->processChecks();
            $this->deleteOldCacheDirectory(); // may exist if this command failed before
            $this->renameCurrentCacheDirectory();
            $this->createNewCacheDirectory(); // probably not needed
            $this->io->success('Cache cleared.');

            $this->deleteOldCacheDirectory();

            return 0;
        } catch (RuntimeException $exception) {
            $this->io->error("Error processing {$this->getName()}:\u{a0}" . $exception->getMessage());

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
        if ($this->isWindows()) {
            //sleep(1);
            if (!@rename($this->getCacheDirectoryBasePath(), $this->getCacheDirectoryOldPath())) {
                // be carefull on widows, if cache folder is open in windows explorer, you will have an access denied error 5
                throw new RuntimeException('Error renaming cache dir to cache_old, check that cache dir or cache file are not open.');
            }
        } else {
            $process = new Process(['mv', $this->getCacheDirectoryBasePath(), $this->getCacheDirectoryOldPath()]);
            $process->run();
            $this->handleUnsucessfullProcess(__FUNCTION__, $process);
        }
    }

    private function deleteOldCacheDirectory()
    {
        if (file_exists($this->getCacheDirectoryOldPath())) {
            if ($this->isWindows()) {
                $output = [];
                $return = 0;
                $returnLine = exec('rmdir /S /Q ' . $this->getCacheDirectoryOldPath(), $output, $return);
                if ($return !== 0) {
                    throw new RuntimeException('Error doing ' . __FUNCTION__ . ' : ' . PHP_EOL . ' : ' . print_r($output, true));
                }
            } else {
                $process = new Process(['rm', '-rf', $this->getCacheDirectoryOldPath()/*.'/'*/]); // final slash needed
                $process->run();
                $this->handleUnsucessfullProcess(__FUNCTION__, $process);
            }
        }
    }

    private function createNewCacheDirectory()
    {
        $cache_dir = $this->getCacheDirectoryBasePath() . DIRECTORY_SEPARATOR . ((new DebugAdapter())->isDebugModeEnabled() ? 'dev' : 'prod');
        if ($this->isWindows()) {
            $process = new Process(['mkdir', $cache_dir]);
        } else {
            $process = new Process(['mkdir', $cache_dir, '-p']);
        }
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
        $path = _PS_CACHE_DIR_;
        if ($this->isWindows()) {
            $path = str_replace('/', '\\', $path);
        }

        return preg_replace('!\\' . DIRECTORY_SEPARATOR . '(prod|dev)\\' . DIRECTORY_SEPARATOR . '$!', '', $path);
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

    private function isWindows(): bool
    {
        return 'WIN' === strtoupper(substr(PHP_OS, 0, 3));
    }
}
