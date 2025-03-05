<?php

namespace FOP\Console\Tests\Unit\Generator;

include __DIR__.'/../../../../../vendor/autoload.php';

use FOP\Console\Generator\AssetsFileGenerator;
use FOP\Console\Generator\ContentFileDTO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;

class AssetFileGeneratorTest extends TestCase
{
    public function testGenerate()
    {
        $twigValues = new ContentFileDTO();
        $twigValues->moduleName = '';
        $twigValues->className = '';
        $twigValues->fileNameModule = '';

        $twig = $this->createMock(Environment::class);
        $twig->method('render')->with('@Modules/twig_template_file_name.js')->willReturn('test');
        $g = new AssetsFileGenerator($twig);
        $g->setTemplatesBaseFolder('');
        $g->setTwigValues($twigValues);

        $g->setModuleName('modulename');
        $g->setTemplateName('twig_template_file_name.js');
        $g->setClassFolderName('views/js/');
        $g->setFileNameModule('file.js');
        $g->setFileNameSeparator('');
        $g->generate();


        $this->assertTrue(file_exists(_PS_MODULE_DIR_.'/modulename/views/js/file.js'));

        (new Filesystem())->remove(_PS_MODULE_DIR_);
    }
}
