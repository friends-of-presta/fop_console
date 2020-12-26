<?php

namespace FOP\Console\Overriders;

use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\FileGenerator;
use Symfony\Component\Filesystem\Filesystem;

class ClassOverrider extends AbstractOverrider implements OverriderInterface
{
    /**
     * {@inheritDoc}
     */
    public function run(): array
    {
//        $s = ClassGenerator::fromReflection(new ClassReflection($this->getCoreClassName()));

//        $meth = $s->getMethod('cleanPositions');
//        $db = new \Laminas\Code\Generator\DocBlockGenerator('@inheritDoc');
//        $meth->setDocBlock($db);
//
//        $o = new ClassGenerator();
//        $o->setName('Feature');
//        $o->setExtendedClass('FeatureCore');
//        $o->addMethodFromGenerator($meth);

        $generated_class = new ClassGenerator();
        $generated_class->setName($this->getClassName())
            ->setExtendedClass($this->getCoreClassName());

        $fileGenerator = new FileGenerator();
        $fileGenerator->setClass($generated_class);

        $fs = new Filesystem();
        $fs->dumpFile($this->getTargetPath(), $fileGenerator->generate());
        $this->setSuccessful();

        return ["File {$this->getTargetPath()} created"];
    }

    /**
     * {@inheritDoc}
     */
    public function handle(): bool
    {
        return fnmatch('classes/**.php', $this->getPath());
    }

    /**
     * {@inheritDoc}
     */
    public function getDangerousConsequences(): ?string
    {
        $fs = new Filesystem();
        if ($fs->exists($this->getTargetPath())) {
            return "File {$this->getTargetPath()} will be overwritten.";
        }

        return null;
    }

    private function getTargetPath(): string
    {
        // after 'classes/' included - probably ready to handle absolute paths
        $file_and_folder = substr($this->getPath(), (int) strrpos('classes/', $this->getPath()));

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
