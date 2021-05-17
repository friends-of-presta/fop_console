<?php

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
        return fnmatch('*classes/**.php', $this->getPath()) || fnmatch('*controllers/**.php', $this->getPath());
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
        $file_and_folder = substr($this->getPath(), (int) strrpos($this->getPath(), 'classes/'));

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
