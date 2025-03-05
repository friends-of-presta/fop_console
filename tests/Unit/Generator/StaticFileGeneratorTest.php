<?php

namespace FOP\Console\Tests\Unit\Generator;

include __DIR__ . '/../../../../../vendor/autoload.php';

use FOP\Console\Generator\ContentFileDTO;
use FOP\Console\Generator\MainModuleFileGenerator;
use FOP\Console\Generator\StaticFileGenerator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;

class StaticFileGeneratorTest extends TestCase
{
    public function testGenerate()
    {
        $twigValues = new ContentFileDTO();
        $twigValues->moduleName = '';
        $twigValues->className = '';
        $twigValues->fileNameModule = '';

        $twig = $this->createMock(Environment::class);
        $twig->method('render')->with('@Modules/composer.json.twig')->willReturn('test');
        $g = new StaticFileGenerator($twig);
        $g->setTemplatesBaseFolder('');
        $g->setTwigValues($twigValues);

        $g->setModuleName('modulename');
        $g->setTemplateName('composer.json.twig');
        //this is the file of class name
        $g->setFileNameModule('composer.json');
        // insert the file in module root
        $g->generate();

        $this->assertTrue(file_exists(_PS_MODULE_DIR_ . '/modulename/main.php'));

        (new Filesystem())->remove(_PS_MODULE_DIR_);
    }
}
