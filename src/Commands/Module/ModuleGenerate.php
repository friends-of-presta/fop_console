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
 *
 */

namespace FOP\Console\Commands\Module;

use FOP\Console\Command;
use Nette\PhpGenerator\PhpFile;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;

class ModuleGenerate extends Command
{
    protected Filesystem $filesystem;
    protected string $base_controller_folder;
    protected string $base_test_folder;
    protected string $base_folder;
    private Environment $twig;

    public function __construct()
    {
        parent::__construct();
        $this->filesystem = new Filesystem();
        $this->base_folder = _PS_MODULE_DIR_ . 'fop_console/templates/generate_module_command/module';
        $this->base_controller_folder = $this->base_folder . '/controller';
        $this->base_test_folder = $this->base_folder . '/test';
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('fop:module:generate')
            ->setDescription('Scaffold new PrestaShop module')
            ->addArgument('modulename', InputArgument::REQUIRED)
            ->addArgument('namespace', InputArgument::REQUIRED)
        ;
    }

    protected function createConfig($modulename)
    {
        $module_config_path =
            $this->getModuleDirectory($modulename) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'admin';
        $this->filesystem->mkdir($module_config_path);
        $this->filesystem->copy(
            $this->base_controller_folder . DIRECTORY_SEPARATOR . 'services.yml',
            $module_config_path . DIRECTORY_SEPARATOR . 'services.yml'
        );
    }

    protected function createController($modulename, $namespace)
    {
        $controller_code =
            $this->twig->render($this->base_controller_folder . DIRECTORY_SEPARATOR . 'configuration.php.twig', [
                'class_name' => 'ConfigurationController',
                'module_name' => $modulename,
                'name_space' => $namespace,
            ]);
//        $file = PhpFile::fromCode($controller_code);
        $this->filesystem->dumpFile($this->getModuleDirectory($modulename) . DIRECTORY_SEPARATOR . 'src' .
            DIRECTORY_SEPARATOR . 'Controller' . DIRECTORY_SEPARATOR . 'ConfigurationController.php', $controller_code);
    }

    protected function createControllerForm($modulename, $namespace)
    {
        $controller_code = $this->twig->render($this->base_controller_folder . DIRECTORY_SEPARATOR . 'form.php.twig', [
            'class_name' => 'ConfigurationType',
            'module_name' => $modulename,
            'name_space' => $namespace,
        ]);
//        $file = PhpFile::fromCode($controller_code);
        $this->filesystem->dumpFile(
            $this->getModuleDirectory($modulename) . DIRECTORY_SEPARATOR . 'src' .
            DIRECTORY_SEPARATOR . 'Form' . DIRECTORY_SEPARATOR . 'Type' . DIRECTORY_SEPARATOR . 'ConfigurationType.php',
            $controller_code
        );
    }

    protected function createControllerTemplate($modulename, $templatename)
    {
        $module_view_path =
            $this->getModuleDirectory($modulename) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'templates' .
            DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'controller';
        $this->filesystem->mkdir($module_view_path);
        $this->filesystem->copy(
            $this->base_controller_folder . DIRECTORY_SEPARATOR . 'template_controller.twig',
            $module_view_path . DIRECTORY_SEPARATOR . 'admin_configuration.html.twig'
        );
    }

    protected function createMain($modulename)
    {
        $controller_code = $this->twig->render($this->base_folder . DIRECTORY_SEPARATOR . 'main.php.twig', [
            'module_name' => $modulename,
        ]);

        $this->filesystem->dumpFile(
            $this->getModuleDirectory($modulename) . DIRECTORY_SEPARATOR . $modulename . '.php',
            $controller_code
        );
    }

    protected function createModule($modulename)
    {
        $this->filesystem->mkdir($this->getModuleDirectory($modulename));
    }

    protected function createRoute($modulename, $namespace)
    {
        $module_route_path = $this->getModuleDirectory($modulename) . DIRECTORY_SEPARATOR . 'config';
        if ($this->filesystem->exists($module_route_path) === false) {
            $this->filesystem->mkdir($module_route_path);
        }
        $route_code = $this->twig->render($this->base_controller_folder . DIRECTORY_SEPARATOR . 'routes.yml.twig', [
            'module_name' => $modulename,
            'name_space' => $namespace,
        ]);
        $this->filesystem->dumpFile($module_route_path . DIRECTORY_SEPARATOR . 'routes.yml', $route_code);
    }

    protected function createTest($modulename)
    {
        $module_dir = $this->getModuleDirectory($modulename);
        $test_dir = $module_dir . DIRECTORY_SEPARATOR . 'test';
        $this->filesystem->mkdir($test_dir);
        $this->filesystem->copy(
            $this->base_test_folder . DIRECTORY_SEPARATOR . 'bootstrap.php.twig',
            $test_dir . DIRECTORY_SEPARATOR . 'bootstrap.php'
        );
        $this->filesystem->copy(
            $this->base_test_folder . DIRECTORY_SEPARATOR . 'phpunit.xml.twig',
            $module_dir . DIRECTORY_SEPARATOR . 'phpunit.xml'
        );
    }

    protected function creteComposerJson($modulename, $namespace)
    {
        $composer_code = $this->twig->render($this->base_folder . DIRECTORY_SEPARATOR . 'composer.json.twig', [
            'module_name' => $modulename,
            'name_space_psr4' => str_replace('\\', '\\\\', $namespace),
        ]);
        $this->filesystem->dumpFile(
            $this->getModuleDirectory($modulename) . DIRECTORY_SEPARATOR . 'composer.json',
            $composer_code
        );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->twig = $this->getContainer()->get('twig');

        $modulename = $input->getArgument('modulename');
        $namespace = $input->getArgument('namespace');

        $output->writeln('create module folder');
        $this->createModule($modulename);

        $output->writeln('create main file');
        $this->createMain($modulename);

        $output->writeln('create composer.json');
        $this->creteComposerJson($modulename, $namespace);

        $output->writeln('create config');
        $this->createConfig($modulename);

        $output->writeln('create routes');
        $this->createRoute($modulename, $namespace);

        $output->writeln('create configuration controller');
        $this->createController($modulename, $namespace);

        $output->writeln('create form ');
        $this->createControllerForm($modulename, $namespace);

        $output->writeln('create configuration controller template');
        $this->createControllerTemplate($modulename, $namespace);

        $output->writeln('create test folder');
        $this->createTest($modulename);
        $output->writeln('....');

        $output->writeln('OK! Now you can edit composer.json and run "composer install" inside your new module.');
        $output->writeln('');
    }

    /**
     * @param string $modulename
     *
     * @return string
     */
    protected function getModuleDirectory($modulename): string
    {
        return _PS_MODULE_DIR_ . $modulename;
    }
}
