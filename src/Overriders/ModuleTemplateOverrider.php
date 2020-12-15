<?php

namespace FOP\Console\Overriders;

use Symfony\Component\Filesystem\Filesystem;

final class ModuleTemplateOverrider implements OverriderInterface
{
    public function run(string $path): string
    {
        $final_path = 'themes/classic/' . $path;
        $fs = new Filesystem();
        $fs->copy($path, $final_path);

        return "File $final_path created";
    }

    public function handle(string $path): bool
    {
        return fnmatch('modules/*/*.tpl', $path);
    }
}
