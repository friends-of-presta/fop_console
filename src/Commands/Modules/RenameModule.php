<?php

namespace FOP\Console\Commands\Modules;

use Module;
use FOP\Console\Command;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Output\NullOutput;
use Composer\Console\Application;

final class RenameModule extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('fop:modules:rename')
            ->setDescription('Rename module')
            ->setHelp('This command allows you to replace the name of a module in the files and database.')
            ->addArgument(
                'old-name',
                InputArgument::REQUIRED,
                'Module current name with following format : Prefix_ModuleCurrentNameCamelCased'
            )
            ->addArgument(
                'new-name',
                InputArgument::REQUIRED,
                'Module new name with following format : Prefix_ModuleNewNameCamelCased'
            )
            ->addUsage('--new-author=[AuthorCamelCased]')
            ->addOption('new-author', null, InputOption::VALUE_REQUIRED, 'New author name')
            ->addUsage('--extra-replacements=[search,replace]')
            ->addOption('extra-replacements', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Extra search/replace pairs')
            ->addUsage('--only-files=[directory]')
            ->addOption('only-files', null, InputOption::VALUE_OPTIONAL, 
                'Process only files without affecting database. You can pass a directory name if it doesn\'t match the current module name.')
            ->addUsage('--duplicate')
            ->addOption('duplicate', null, InputOption::VALUE_NONE, 'Duplicate the module with a new name instead of renaming it');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!($oldFullName = $this->mapModuleName($input->getArgument('old-name')))) {
            $io->error('Please check the required format for the module old name');
            return 1;
        }
        
        if (!($newFullName = $this->mapModuleName($input->getArgument('new-name')))) {
            $io->error('Please check the required format for the module new name');
            return 1;
        }

        $oldModuleName = strtolower($oldFullName['prefix'] . $oldFullName['name']);
        $oldModule = Module::getInstanceByName($oldModuleName);

        $newAuthor = $input->getOption('new-author');
        if ($newAuthor) {
            $oldAuthor = $oldModule->author;
            if ($newAuthor == $oldAuthor) {
                $io->text('Author replacements have been ignored since the old and the new one are equal');
                $newAuthor = false;
            }
        }

        $replace_pairs = [];

        $extraReplacements = $input->getOption('extra-replacements');
        if ($extraReplacements) {
            foreach($extraReplacements as $replacement) {
                $terms = explode(',', $replacement);
                if (count($terms) != 2) {
                    $io->error('Each extra replacement must be a pair of two words separated by a comma');
                    return 1;
                }
                $replace_pairs[$terms[0]] = $terms[1];
            }
        }

        $prefixReplaceFormats = [
            //PREFIXModuleName
            function($fullName) {
                return strtoupper($fullName['prefix']) . $fullName['name'];
            },
        ];

        foreach ($prefixReplaceFormats as $replaceFormat) {
            $search = $replaceFormat($oldFullName);
            $replace = $replaceFormat($newFullName);
            $replace_pairs[$search] = $replace;
        }

        $moduleNameReplaceFormats = [
            //ModuleName
            function($moduleName) {
                return $moduleName;
            },
            //moduleName
            function($moduleName) {
                return lcfirst($moduleName);
            },
            //Modulename
            function($moduleName) {
                return ucfirst(strtolower($moduleName));
            },
            //modulename
            function($moduleName) {
                return strtolower($moduleName);
            },
            //MODULE_NAME
            function($moduleName) {
                return strtoupper(implode('_', 
                    preg_split('/(?=[A-Z])/', $moduleName, -1, PREG_SPLIT_NO_EMPTY)
                ));
            },
            //module_name
            function($moduleName) {
                return strtolower(implode('_', 
                    preg_split('/(?=[A-Z])/', $moduleName, -1, PREG_SPLIT_NO_EMPTY)
                ));
            },
            //Module Name
            function($moduleName) {
                return implode(' ', 
                    preg_split('/(?=[A-Z])/', $moduleName, -1, PREG_SPLIT_NO_EMPTY)
                );
            },
            //Module name
            function($moduleName) {
                return strtolower(implode(' ', 
                    preg_split('/(?=[A-Z])/', $moduleName, -1, PREG_SPLIT_NO_EMPTY)
                ));
            },
        ];

        foreach ($moduleNameReplaceFormats as $replaceFormat) {
            if (!empty($oldFullName['prefix'])) {
                $search = $replaceFormat($oldFullName['prefix'] . $oldFullName['name']);
                $replace = $replaceFormat($newFullName['prefix'] . $newFullName['name']);
                $replace_pairs[$search] = $replace;
            }
        }

        foreach ($moduleNameReplaceFormats as $replaceFormat) {
            $search = $replaceFormat($oldFullName['name']);
            $replace = $replaceFormat($newFullName['name']);
            $replace_pairs[$search] = $replace;
        }

        if ($newAuthor) {
            $authorReplaceFormats = [
                //AuthorName
                function ($authorName) {
                    return $authorName;
                },
                //authorName
                function ($authorName) {
                    return lcfirst($authorName);
                }
            ];

            foreach($authorReplaceFormats as $replaceFormat) {
                $search = $replaceFormat($oldAuthor);
                $replace = $replaceFormat($newAuthor);
                $replace_pairs[$search] = $replace;
            }
        }

        $io->title('The following replacements will occur:');
        $table = new Table($output);
        $table->setHeaders(['Occurence', 'Replacement']);
        foreach ($replace_pairs as $search => $replace) {
            $table->addRow([$search, $replace]);
        }
        $table->render();

        $questionHelper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Do you confirm these replacements (y for yes, n for no)?', false);
        if (!$questionHelper->ask($input, $output, $question)) {
            return 0;
        }
        
        $onlyFiles = $input->getOption('only-files');
        $duplicate = $input->getOption('duplicate');
        if (!$onlyFiles && !$duplicate && $oldModule && $oldModule->uninstall()) {
            $io->success('The old module ' . $oldModuleName . ' has been uninstalled.');
        }

        $oldFolderName = is_string($onlyFiles) ? $onlyFiles : strtolower($oldFullName['prefix'] . $oldFullName['name']);
        $oldFolderPath = _PS_MODULE_DIR_ . $oldFolderName . '/';
        $newFolderPath = _PS_MODULE_DIR_ . strtolower($newFullName['prefix'] . $newFullName['name']) . '/';

        if (is_dir($newFolderPath)) {
            $question = new ConfirmationQuestion(
                'The destination module already exists. The folder will be removed and the module uninstalled.' 
                . PHP_EOL . 'Do you want to continue? (y for yes, n for no)?', false);
            if (!$questionHelper->ask($input, $output, $question)) {
                return 0;
            }  

            $newModuleName = strtolower($newFullName['prefix'] . $newFullName['name']);
            $newModule = Module::getInstanceByName($newModuleName);
            if (!$onlyFiles && $newModule && $newModule->uninstall()) {
                $io->success('The module ' . $newModuleName . ' has been uninstalled.');
            }
            exec('rm -rf ' . $newFolderPath);
        }
        exec('cp -R ' . $oldFolderPath . '. ' . $newFolderPath);
        if (!$duplicate) {
            exec('rm -rf ' . $oldFolderPath);
        }

        $finder = new Finder();
        $finder->exclude(['vendor', 'node_modules']);
        foreach ($finder->in($newFolderPath) as $file) {
            if ($file->isFile()) {
                $fileContent = file_get_contents($file->getPathname());
                file_put_contents($file->getPathname(), strtr($fileContent, $replace_pairs));
            }
            foreach ($replace_pairs as $search => $replace) {
                if (strpos($file->getRelativePathname(), $search) !== false) {
                    rename($file->getPathname(), $newFolderPath . strtr($file->getRelativePathname(), $replace_pairs));
                    break;
                }
            }
        }

        // Composer\Factory::getHomeDir() method 
        // needs COMPOSER_HOME environment variable set
        //putenv('COMPOSER_HOME=' . __DIR__ . '/vendor/bin/composer');
        
        chdir($newFolderPath);
        exec('rm -rf vendor');
        $installCommand = new ArrayInput(['command' => 'update']);
        $dumpautoloadCommand = new ArrayInput(['command' => 'dumpautoload', '-a']);
        $application = new Application();
        $application->setAutoExit(false);
        $application->run($installCommand, new NullOutput());
        $application->run($dumpautoloadCommand, new NullOutput());
        chdir('../..');

        $newModuleName = strtolower($newFullName['prefix'] . $newFullName['name']);
        $newModule = Module::getInstanceByName($newModuleName);
        if (!$onlyFiles && $newModule && $newModule->install()) {
            $io->success('The fresh module ' . $newModuleName . ' has been installed.');
        }

        return 0;  
    }

    function mapModuleName($arg) {
        if (!$arg) {
            return false;
        }

        $fullName = explode('_', $arg);
        if (count($fullName) > 2) {
            return false;
        }

        $prefix = count($fullName) == 2 ? $fullName[0] : '';
        $name = $fullName[count($fullName) - 1];
        if (empty($name)) {
            return false;
        }

        return [
            'prefix' => $prefix, 
            'name' => $name
        ];
    }
}