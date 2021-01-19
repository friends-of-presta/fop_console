<?php

namespace FOP\Console\Overriders;

use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\FileGenerator;

class ModuleOverrider extends AbstractOverrider implements OverriderInterface
{
    public function run(): array
    {
        $override_class = ClassGenerator::fromArray(
            ['name' => $this->getModuleOverrideClassName(),
             'extendedclass' => $this->getModuleClassName(),
                ]);

        $fileGenerator = new FileGenerator();
        $fileGenerator->setClass($override_class);
        $this->fs->dumpFile($this->getTargetPath(), $fileGenerator->generate());

        $this->setSuccessful();

        return [sprintf("File '%s' created.", $this->getTargetPath())];
    }

    public function handle(): bool
    {
        return fnmatch('modules/*/*.php', $this->getPath());
    }

    public function getDangerousConsequences(): ?string
    {
        return $this->fs->exists($this->getTargetPath()) ? sprintf('File "%s" already exists.', $this->getTargetPath()) : null;
    }

    private function getTargetPath(): string
    {
        // after 'modules/' (included) - probably ready to handle absolute paths
        // relative path, relative to Prestashop root folder.
        $relative_path_start = strrpos($this->getPath(), 'modules/');
        $relative_path_start = false !== $relative_path_start
            ? $relative_path_start
            : strrpos($this->getPath(), 'modules/');
        if (false === $relative_path_start) {
            throw new \Exception(sprintf('"modules/" not found in path "%s"', $this->getPath()));
        }

        $file_and_folder = substr($this->getPath(), (int) $relative_path_start);

        return sprintf('override/%s', $file_and_folder);
    }

    /**
     * It's guessed from the filename
     * Some modules have a classname different of the filename.
     * It will fail for this modules.
     *
     * @return string classname of the module to override
     *
     * @throws \Exception
     *
     * @todo find actual classname using Laminas class reflexion, see LegacyCoreOverrider.
     */
    private function getModuleClassName(): string
    {
        return basename($this->getTargetPath(), '.php');
    }

    private function getModuleOverrideClassName(): string
    {
        return $this->getModuleClassName() . 'Override';
    }
}
