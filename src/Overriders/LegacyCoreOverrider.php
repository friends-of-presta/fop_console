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

namespace FOP\Console\Overriders;

use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\FileGenerator;
use Symfony\Component\Filesystem\Filesystem;
use Tools;

class LegacyCoreOverrider extends AbstractOverrider implements OverriderInterface
{
    /**
     * {@inheritDoc}
     */
    public function run(): array
    {
        $generated_class = new ClassGenerator();
        $generated_class->setName($this->getClassName())
            ->setExtendedClass($this->getCoreClassName());

        $fileGenerator = new FileGenerator();
        $fileGenerator->setClass($generated_class);

        $fs = new Filesystem();
        $fs->dumpFile($this->getTargetPath(), $fileGenerator->generate());
        $this->setSuccessful();

        Tools::generateIndex();

        return ["{$this->getTargetPath()} created."];
    }

    /**
     * {@inheritDoc}
     */
    public function handle(): bool
    {
        return fnmatch('*classes' . DIRECTORY_SEPARATOR . '**.php', $this->getPath()) || fnmatch('*controllers' . DIRECTORY_SEPARATOR . '**.php', $this->getPath());
    }

    /**
     * {@inheritDoc}
     */
    public function getDangerousConsequences(): ?string
    {
        return $this->fs->exists($this->getTargetPath()) ? "File {$this->getTargetPath()} already exists" : null;
    }

    private function getTargetPath(): string
    {
        // after 'classes/' included - probably ready to handle absolute paths
        $file_and_folder = substr($this->getPath(), (int) strrpos($this->getPath(), 'classes' . DIRECTORY_SEPARATOR));

        return sprintf('override/%s', $file_and_folder);
    }

    private function getClassName(): string
    {
        return basename($this->getPath(), '.php');
    }

    private function getCoreClassName(): string
    {
        return basename($this->getPath(), '.php') . 'Core';
    }
}
