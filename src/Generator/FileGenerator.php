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
 */

namespace FOP\Console\Generator;

use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;

abstract class FileGenerator
{
    public static string $PSMODULEDIR = _PS_MODULE_DIR_;
    protected string $moduleName;
    protected string $templatesBaseFolder;
    protected Environment $twig;
    protected ContentFileDTO $twigValues;
    protected string $templateName;
    protected Filesystem $filesystem;
    protected string $fileNameModule;
    protected string $fileNameSeparator = '_';

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
        $this->filesystem = new Filesystem();
    }

    public function setFileNameSeparator(string $fileNameSeparator): void
    {
        $this->fileNameSeparator = $fileNameSeparator;
    }

    public function generate(): void
    {
        $code = $this->twig
            ->render(
                '@Modules'
                . $this->templatesBaseFolder
                . DIRECTORY_SEPARATOR
                . $this->templateName,
                (array) $this->twigValues
            );

        $this->filesystem->dumpFile(
            $this->getModuleFolder() . $this->getFileNameModule(),
            $code
        );
    }

    abstract protected function getModuleFolder(): string;

    public function getFileNameModule(): string
    {
        return $this->fileNameModule;
    }

    public function setFileNameModule(string $fileNameModule): void
    {
        $this->fileNameModule = $fileNameModule;
    }

    /**
     * @param mixed $moduleName
     */
    public function setModuleName($moduleName): self
    {
        $this->moduleName = $moduleName;

        return $this;
    }

    public function setTemplateName(string $templateName): self
    {
        $this->templateName = $templateName;

        return $this;
    }

    public function setTemplatesBaseFolder(string $templatesBaseFolder): void
    {
        $this->templatesBaseFolder = $templatesBaseFolder;
    }

    public function setTwigValues(ContentFileDTO $twigValues): void
    {
        $this->twigValues = $twigValues;
    }

    protected function getModuleDirectory(): string
    {
        return FileGenerator::$PSMODULEDIR . $this->moduleName;
    }
}
