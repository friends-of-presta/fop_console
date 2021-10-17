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

use Context;

final class ModuleTemplateOverrider extends AbstractOverrider implements OverriderInterface
{
    /**
     * @return array<int, string>
     */
    public function run(): array
    {
        $targetPath = $this->getTargetPath();
        $this->fs->copy($this->getPath(), $targetPath, true);
        $this->setSuccessful();

        return ["File $targetPath created"];
    }

    public function handle(): bool
    {
        return fnmatch('modules' . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '*.tpl', $this->getPath());
    }

    /**
     * @return string
     */
    private function getThemePath(): string
    {
        // @todo Maybe it's better to rely on the directory property
        return Context::getContext()->shop->theme->getName();
    }

    public function getDangerousConsequences(): ?string
    {
        if ($this->fs->exists($this->getTargetPath())) {
            return "File {$this->getTargetPath()} will be overwritten.";
        }

        return null;
    }

    private function getTargetPath(): string
    {
        return sprintf('themes/%s/%s', $this->getThemePath(), $this->getPath());
    }
}
