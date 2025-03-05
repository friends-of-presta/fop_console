<?php

namespace FOP\Console\Tests\Unit\Generator;

include __DIR__.'/../../../../../vendor/autoload.php';

use FOP\Console\Generator\CodeDisplayGenerator;
use FOP\Console\Generator\ContentFileDTO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;

class CodeDisplayGeneratorTest extends TestCase
{
    public function testGenerate()
    {
        $twigValues = new ContentFileDTO();
        $twigValues->moduleName = '';
        $twigValues->className = '';
        $twigValues->fileNameModule = '';

        $twig = $this->createMock(Environment::class);
        $twig->method('render')->with('@Modules/twig_template_file_name.js')->willReturn('test');
        $g = new CodeDisplayGenerator($twig);
        $g->setTemplatesBaseFolder('');
        $g->setTwigValues($twigValues);

        $g->setModuleName('modulename');
        $g->setTemplateName('twig_template_file_name.js');

        //it's not necessary to set these values beacouse content is displayed in shell
        //        $g->setClassFolderName('');
        //        $g->setFileNameModule('');
        //        $g->setFileNameSeparator('');
        ob_start();
        $g->generate();
        $content = ob_get_contents();
        ob_end_flush();
        $this->assertEquals('test'.PHP_EOL, $content);

        (new Filesystem())->remove(_PS_MODULE_DIR_);
    }
}
