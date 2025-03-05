<?php

namespace FOP\Console\Tests\Unit\Generator;

include __DIR__ . '/../../../../../vendor/autoload.php';

use FOP\Console\Generator\ContentFileDTO;
use FOP\Console\Generator\MainModuleFileGenerator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;

class MainModuleFileGeneratorTest extends TestCase
{
    public function testGenerate()
    {
        $twigValues = new ContentFileDTO();
        $twigValues->moduleName = '';
        $twigValues->className = '';
        $twigValues->fileNameModule = '';

        $twig = $this->createMock(Environment::class);
        $twig->method('render')->with('@Modules/main.php.twig')->willReturn('test');
        $g = new MainModuleFileGenerator($twig);
        $g->setTemplatesBaseFolder('');
        $g->setTwigValues($twigValues);

        $g->setModuleName('modulename');
        $g->setTemplateName('main.php.twig');
        //this is the file of class name
        $g->setFileNameModule('main.php');
        // insert the file in module root
        $g->setModuleFolder('/');
        $g->generate();

        $this->assertTrue(file_exists(_PS_MODULE_DIR_ . '/modulename/main.php'));

        (new Filesystem())->remove(_PS_MODULE_DIR_);
    }
}
