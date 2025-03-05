<?php

namespace FOP\Console\Tests\Unit\Generator;

include __DIR__.'/../../../../../vendor/autoload.php';

use FOP\Console\Generator\ClassFileGenerator;
use FOP\Console\Generator\ContentFileDTO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;

class ClassFileGeneratorTest extends TestCase
{
    public function testGenerate()
    {
        $twig = $this->createMock(Environment::class);

        $g = new ClassFileGenerator($twig);
        $g->setTemplatesBaseFolder('');
        $g->setTemplateName('twig_template_file_name.js');

        $g->setModuleName('modulename');
        $g->setClassFolderName('src/Classes/');

        $twigValues = new ContentFileDTO();
        $twigValues->moduleName = '';
        $twigValues->fileNameModule = '';


        //this create the file name MyClassNameBackOffice.php
        $twigValues->className = 'MyClassName';
        $g->setFileNameModule('BackOffice.php');

        $g->setTwigValues($twigValues);

        $twig->method('render')->with('@Modules/twig_template_file_name.js', (array)$twigValues)->willReturn('test');

        //  $g->setFileNameSeparator('');
        $g->generate();


        $file = _PS_MODULE_DIR_.'/modulename/src/Classes/MyClassNameBackOffice.php';
        $this->assertTrue(file_exists($file));

        (new Filesystem())->remove(_PS_MODULE_DIR_);
    }
}
