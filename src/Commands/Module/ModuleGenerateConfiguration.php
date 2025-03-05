<?php
/**
 * Copyright (c) Since 2020 Friends of Presta
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file docs/licenses/LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to infos@friendsofpresta.org so we can send you a copy immediately.
 *
 * @author    Friends of Presta <infos@friendsofpresta.org>
 * @copyright since 2020 Friends of Presta
 * @license   https://opensource.org/licenses/AFL-3.0  Academic Free License ("AFL") v. 3.0
 */

namespace FOP\Console\Commands\Module;

use FOP\Console\Command;
use FOP\Console\Generator\ContentFileDTO;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

class ModuleGenerateConfiguration extends Command
{
    private array $generatorServices = [];
    private string $moduleName;
    private ContentFileDTO $configGeneration;

    public function setGeneratorServices(iterable $services)
    {
        $this->generatorServices = iterator_to_array($services);
    }

    protected function configure(): void
    {
        $this->setName('fop:module:generate:configuration')
            ->setDescription('Add configuratione to a Prestashop module');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->twig = $this->getContainer()
            ->get('twig');

        $output->writeln('create configuration');
        $this->createGrid($this->moduleName);
    }

    private function createGrid($moduleName)
    {
        foreach ($this->generatorServices as $serviceGenerator) {
            $serviceGenerator->setModuleName($this->moduleName);
            $serviceGenerator->setTwigValues($this->configGeneration);
            $serviceGenerator->generate();
        }
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->configGeneration = new ContentFileDTO();
        $helper = $this->getHelper('question');

        $ask_module_name = new Question(
            'Please enter the module name: ',
        );

        $this->moduleName = $helper->ask($input, $output, $ask_module_name);

        if (!file_exists(_PS_MODULE_DIR_.$this->moduleName)) {
            $output->writeln('Module does not exist');

            return;
        }

        $this->configGeneration->nameSpace = $helper->ask($input, $output, new Question('Main name space? : '));
        $this->configGeneration->serviceNameSpace =
            $helper->ask($input, $output, new Question('Service name space? (example mymodule.service...): '));
        $this->configGeneration->className =
            $helper->ask($input, $output, new Question('Class name? (example BasicData): ', 'BasicData'));
        $converter = new CamelCaseToSnakeCaseNameConverter();
        $this->configGeneration->serviceName = $converter->normalize($this->configGeneration->className);
        $this->configGeneration->moduleName = $this->moduleName;
    }
}
