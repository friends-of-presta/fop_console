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

namespace FOP\Console\Overriders;

use Exception;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\FileGenerator;

class ModuleOverrider extends AbstractOverrider implements OverriderInterface
{
    public function run(): array
    {
        $override_class = ClassGenerator::fromArray(
            ['name' => $this->getModuleOverrideClassName(),
             'extendedclass' => $this->getModuleClassName(),
                ]
        );

        $fileGenerator = new FileGenerator();
        $fileGenerator->setClass($override_class);
        $this->fs->dumpFile($this->getTargetPath(), $fileGenerator->generate());

        $this->setSuccessful();

        return [sprintf("File '%s' created.", $this->getTargetPath())];
    }

    public function handle(): bool
    {
        return fnmatch('modules' . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '*.php', $this->getPath());
    }

    public function getDangerousConsequences(): ?string
    {
        return $this->fs->exists($this->getTargetPath()) ? sprintf('File "%s" already exists.', $this->getTargetPath()) : null;
    }

    private function getTargetPath(): string
    {
        // after 'modules/' (included) - probably ready to handle absolute paths
        // relative path, relative to Prestashop root folder.
        $relative_path_start = strrpos($this->getPath(), 'modules' . DIRECTORY_SEPARATOR);
        $relative_path_start = false !== $relative_path_start
            ? $relative_path_start
            : strrpos($this->getPath(), 'modules' . DIRECTORY_SEPARATOR);
        if (false === $relative_path_start) {
            throw new Exception(sprintf('"modules/" not found in path "%s"', $this->getPath()));
        }

        $file_and_folder = substr($this->getPath(), (int) $relative_path_start);

        return sprintf('override%s%s', DIRECTORY_SEPARATOR, $file_and_folder);
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
