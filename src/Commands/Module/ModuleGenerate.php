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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Filesystem;

class ModuleGenerate extends Command
{
    protected $filesystem;
    protected $base_controller_folder;
    protected $base_test_folder;
    protected $base_folder;
    protected $module_name;
    protected $module_namespace;
    protected $front_controller_name;
    protected $is_new_module = false;
    protected $base_view_folder;
    private $twig;

    public function __construct()
    {
        parent::__construct();
        $this->filesystem = new Filesystem();
        $this->base_folder = _PS_MODULE_DIR_ . 'fop_console/templates/generate_module_command/module';
        $this->base_controller_folder = $this->base_folder . '/controller';
        $this->base_view_folder = $this->base_folder . '/views';
        $this->base_test_folder = $this->base_folder . '/test';
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('fop:module:generate')
            ->setDescription('Scaffold new PrestaShop module')
        ;
    }

    protected function createComposerJson($modulename, $namespace)
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

    protected function createFrontController($module_name, $front_controller_name)
    {
        $front_controller_folder =
            $this->getModuleDirectory($this->module_name) . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'front';

        $this->filesystem->mkdir($front_controller_folder);

        $model_front_file_name = $this->base_controller_folder . DIRECTORY_SEPARATOR . 'front_controller.php.twig';
        $front_controller_code = $this->twig->render($model_front_file_name, [
            'module_name' => $module_name,
            'front_controller_name' => $front_controller_name,
        ]);

        $front_filename = $front_controller_folder . DIRECTORY_SEPARATOR . $front_controller_name . '.php';
        $this->filesystem->dumpFile($front_filename, $front_controller_code);
    }

    protected function createFrontControllerJavascript($module_name, $front_controller_name)
    {
        $js_folder = $this->getModuleDirectory($this->module_name) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'js';
        $this->filesystem->mkdir($js_folder);

        $js_front_controller_code =
            $this->twig->render($this->base_view_folder . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR .
                'front_controller.js.twig', [
                'module_name' => $module_name,
                'front_controller_name' => $front_controller_name,
            ]);
        $this->filesystem->dumpFile(
            $js_folder . DIRECTORY_SEPARATOR . $front_controller_name . '.js',
            $js_front_controller_code
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

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        xdebug_break();
        $this->twig = $this->getContainer()
            ->get('twig');

        if ($this->is_new_module === true) {
            $output->writeln('create module folder');
            $this->createModule($this->module_name);

            $output->writeln('create main file');
            $this->createMain($this->module_name);

            $output->writeln('create composer.json');
            $this->createComposerJson($this->module_name, $this->module_namespace);

            $output->writeln('create config');
            $this->createConfig($this->module_name);

            $output->writeln('create routes');
            $this->createRoute($this->module_name, $this->module_namespace);

            $output->writeln('create configuration controller');
            $this->createController($this->module_name, $this->module_namespace);

            $output->writeln('create form ');
            $this->createControllerForm($this->module_name, $this->module_namespace);

            $output->writeln('create configuration controller template');
            $this->createControllerTemplate($this->module_name, $this->module_namespace);

            $output->writeln('create test folder');
            $this->createTest($this->module_name);
            $output->writeln('....');

            $output->writeln('OK! Now you can edit composer.json and run "composer install" inside your new module.');
            $output->writeln('');
        } else {
            if ($this->front_controller_name) {
                $output->writeln('create front controller file');
                $this->createFrontController($this->module_name, $this->front_controller_name);

                $output->writeln('create front javascript file');
                $this->createFrontControllerJavascript($this->module_name, $this->front_controller_name);
            }
        }
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

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');

        $ask_module_name = new Question('Please enter the name of the module (ex. testmodule): ', 'testmodule');
        $ask_new_module = new Question('Is a new module? [yes,no] ', 'no');
        $ask_namespace = new Question('Please enter the name space (ex Test\Module): ', 'Test\Module');
        $ask_front_controller = new Question('You need add a front controller? [yes/no]: ', 'no');
        $ask_front_controller_name = new Question('What\'s the name of the front contoller? [yes/no]: ', 'no');

        $this->module_name = $helper->ask($input, $output, $ask_module_name);
        $this->is_new_module = ($helper->ask($input, $output, $ask_new_module) === 'yes');

        if ($this->is_new_module === true) {
            $this->module_namespace = $helper->ask($input, $output, $ask_namespace);
        }

        if ($helper->ask($input, $output, $ask_front_controller) === 'yes') {
            $this->front_controller_name = $helper->ask($input, $output, $ask_front_controller_name);
        }
    }
}
