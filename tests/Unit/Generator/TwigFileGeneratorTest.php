<?php

namespace FOP\Console\Tests\Unit\Generator;

include __DIR__.'/../../../../../vendor/autoload.php';

use FOP\Console\Generator\ContentFileDTO;
use FOP\Console\Generator\FileGenerator;
use FOP\Console\Generator\TwigFileGenerator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;

class TwigFileGeneratorTest extends TestCase
{
    public function testGenerate()
    {
        $twigValues = new ContentFileDTO();
        $twigValues->moduleName = '';
        $twigValues->className = '';
        $twigValues->fileNameModule = '';

        $twig = $this->createMock(Environment::class);
        $twig->method('render')->with('@Modules/twig_template_file_name.js')->willReturn('test');


        $g = new TwigFileGenerator($twig);
        $g->setTwigValues($twigValues);
        $g->setTemplatesBaseFolder('');


        //service name is used as a prefix for the file name
        $twigValues->serviceName = 'prova';
        $g->setFileNameModule('new_twig_file.html.twig');

        $g->setTemplateName('twigfile.html.twig');
        $g->setModuleName('modulename');
        $g->setModuleFolder('views/templates/admin/');
        $g->setFileNameSeparator('_');

        $g->generate();


        $this->assertTrue(
            file_exists(__DIR__.'/modulename/views/templates/admin/prova_new_twig_file.html.twig')
        );

        (new Filesystem())->remove(FileGenerator::$PSMODULEDIR.'/modulename');
    }

    protected function setUp(): void
    {
        parent::setUp();
        FileGenerator::$PSMODULEDIR = __DIR__.'/';
    }
}
