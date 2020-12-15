<?php

namespace FOP\Console\Overriders;

use Symfony\Component\Filesystem\Filesystem;

final class ModuleTemplateOverrider implements OverriderInterface
{
    public function run(string $path): string
    {
        try {
            $final_path = sprintf('themes/%s/%s', $this->getThemePath(), $path);
            $fs = new Filesystem();
            $fs->copy($path, $final_path, true);

            return "File $final_path created";
        } catch (\Exception $exception) {
            return 'An error occurred : ' . $exception->getMessage();
        }
    }

    public function handle(string $path): bool
    {
        return fnmatch('modules/*/*.tpl', $path);
    }

    /**
     * @return string
     */
    private function getThemePath(): string
    {
        // @todo Maybe it's better to rely on the directory property
        return \Context::getContext()->shop->theme->getName();
    }
}
