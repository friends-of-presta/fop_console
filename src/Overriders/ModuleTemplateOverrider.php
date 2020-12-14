<?php

namespace FOP\Console\Overriders;

final class ModuleTemplateOverrider implements OverriderInterface
{
    public function run(string $path): string
    {
        return '';
    }

    public function handle(string $path): bool
    {
        return false;
    }
}
