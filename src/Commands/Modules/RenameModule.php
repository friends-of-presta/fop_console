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

namespace FOP\Console\Commands\Modules;

use Composer\Console\Application;
use FOP\Console\Command;
use Module;
use PrestaShop\PrestaShop\Core\Addon\Module\ModuleManagerBuilder;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

final class RenameModule extends Command
{
    private $caseReplaceFormats;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('fop:modules:rename')
            ->setDescription('Rename module')
            ->setHelp('This command allows you to replace the name of a module in the files and database.'
                . PHP_EOL . 'Here are some usage examples:'
                . PHP_EOL . '   • fop:modules:rename PS_,CustomerSignIn KJ,ModuleExample to rename ps_customersignin into kjmoduleexample'
                . PHP_EOL . '   • fop:modules:rename KJ,ModuleExample KJ,ModuleExample2 to rename kjmoduleexample into kjmoduleexample2')
            ->addArgument(
                'old-name',
                InputArgument::REQUIRED,
                'Module current name with following format : Prefix,ModuleCurrentNamePascalCased'
            )
            ->addArgument(
                'new-name',
                InputArgument::REQUIRED,
                'Module new name with following format : Prefix,ModuleNewNamePascalCased'
            )
            ->addUsage('--new-author=[AuthorNamePascalCased]')
            ->addOption('new-author', 'a', InputOption::VALUE_REQUIRED, 'New author name')
            ->addUsage('--extra-replacement=[search,replace], -r [search,replace]')
            ->addOption('extra-replacement', 'r', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Extra search/replace pairs')
            ->addUsage('--cased-extra-replacement=[search,replace], -R [search,replace]')
            ->addOption('cased-extra-replacement', 'R', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Extra search/replace pairs formatted with all usual case formats')
            ->addUsage('--keep-old, -k')
            ->addOption('keep-old', 'k', InputOption::VALUE_NONE, 'Keep the old module untouched and only creates a copy of it with the new name');

        $this->caseReplaceFormats = [
            //StringToFormat
            'pascalCase' => function ($string) {
                return $string;
            },
            //stringToFormat
            'camelCase' => function ($string) {
                return lcfirst($string);
            },
            //String To Format
            'pascalCaseSpaced' => function ($string) {
                return implode(
                    ' ',
                    preg_split('/(?=[A-Z])/', $string, -1, PREG_SPLIT_NO_EMPTY)
                );
            },
            //String to format
            'firstUpperCasedSpaced' => function ($string) {
                return ucfirst(
                    strtolower(
                        implode(
                            ' ',
                            preg_split('/(?=[A-Z])/', $string, -1, PREG_SPLIT_NO_EMPTY)
                        )
                    )
                );
            },
            //string-to-format
            'kebabCase' => function ($string) {
                return strtolower(
                    implode('-',
                    str_replace('_', '',
                        preg_split('/(?=[A-Z])/', $string, -1, PREG_SPLIT_NO_EMPTY)
                    ))
                );
            },
            //STRING_TO_FORMAT
            'upperCaseSnakeCase' => function ($string) {
                return strtoupper(
                    implode('_',
                    str_replace('_', '',
                        preg_split('/(?=[A-Z])/', $string, -1, PREG_SPLIT_NO_EMPTY)
                    ))
                );
            },
            //string_to_format
            'snakeCase' => function ($string) {
                return strtolower(
                    implode('_',
                    str_replace('_', '',
                        preg_split('/(?=[A-Z])/', $string, -1, PREG_SPLIT_NO_EMPTY)
                    ))
                );
            },
            //Stringtoformat
            'firstUpperCased' => function ($string) {
                return ucfirst(strtolower($string));
            },
            //STRINGTOFORMAT
            'upperCase' => function ($string) {
                return strtoupper($string);
            },
            //stringtoformat
            'lowerCase' => function ($string) {
                return strtolower($string);
            },
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $questionHelper = $this->getHelper('question');

        if (!($oldFullName = $this->formatModuleName($input, $output, $input->getArgument('old-name')))) {
            $io->error('The module old name format is not valid. Please check --help for valid examples.');

            return 1;
        }
        $oldModuleName = strtolower($oldFullName['prefix'] . $oldFullName['name']);
        $oldFolderPath = _PS_MODULE_DIR_ . $oldModuleName . '/';
        if (!file_exists($oldFolderPath)) {
            $io->error('The old module folder ' . $oldFolderPath . ' wasn\'t found.');

            return 1;
        }
        $oldModule = Module::getInstanceByName($oldModuleName);
        if (!preg_match('/[A-Z]/', $oldFullName['prefix'] . $oldFullName['name'])) {
            if ($oldModule) {
                $oldModuleClass = get_class($oldModule);
                if (!empty($oldFullName['prefix'])) {
                    $oldFullName['prefix'] = substr(
                        $oldModuleClass,
                        stripos($oldModuleClass, $oldFullName['prefix']),
                        strlen($oldFullName['prefix'])
                    ) ?: $oldFullName['prefix'];
                }
                $oldFullName['name'] = substr(
                    $oldModuleClass,
                    stripos(
                        $oldModuleClass,
                        $oldFullName['name']
                    )
                ) ?: $oldFullName['name'];
            } else {
                $question = new ConfirmationQuestion('The old name is not camel cased, so the camel cased occurences won\'t be replaced.'
                    . PHP_EOL . 'Are you sure that you didn\'t forget to camel case it ? (y to continue, n to abort)', false);
                if (!$questionHelper->ask($input, $output, $question)) {
                    return 0;
                }
            }
        }

        if (!($newFullName = $this->formatModuleName($input, $output, $input->getArgument('new-name')))) {
            $io->error('The module new name format is not valid. Please check --help for valid examples.');

            return 1;
        }
        if (!preg_match('/[A-Z]/', $newFullName['prefix'] . $newFullName['name'])) {
            $question = new ConfirmationQuestion('The new name is not camel cased, so it will be counted as one word. It can cause aesthetic issues.'
            . PHP_EOL . 'Are you sure that you didn\'t forget to camel case it ? (y to continue, n to abort)', false);
            if (!$questionHelper->ask($input, $output, $question)) {
                return 0;
            }
        }

        $newAuthor = $input->getOption('new-author');
        $oldAuthor = '';
        if ($newAuthor) {
            if ($oldModule) {
                $oldAuthor = $oldModule->author;
                if ($newAuthor == $oldAuthor) {
                    $io->text('Author replacements have been ignored since the old and the new one are equal');
                    $newAuthor = false;
                }
            } else {
                $question = new Question('Can\'t create old module instance to retrieve old author name. '
                    . PHP_EOL . 'Please specify the old author name manually (empty to ignore author replacement):');
                $oldAuthor = $questionHelper->ask($input, $output, $question);
                if (empty($oldAuthor)) {
                    $newAuthor = false;
                }
            }
        }

        $replace_pairs = [];

        $fullNameReplaceFormats = [
            //PREFIXModuleName
            function ($fullName) {
                return $this->caseReplaceFormats['upperCase']($fullName['prefix'])
                    . $this->caseReplaceFormats['pascalCase']($fullName['name']);
            },
            //moduleName
            function ($fullName) {
                return $this->caseReplaceFormats['pascalCase']($fullName['name']);
            },
            //Module Name
            function ($fullName) {
                return $this->caseReplaceFormats['pascalCaseSpaced']($fullName['name']);
            },
            //Module name
            function ($fullName) {
                return $this->caseReplaceFormats['firstUpperCasedSpaced']($fullName['name']);
            },
            //PREFIX_MODULE_NAME
            function ($fullName) {
                return strtoupper(str_replace('_', '', $fullName['prefix']))
                    . (!empty($fullName['prefix']) ? '_' : '')
                    . $this->caseReplaceFormats['upperCaseSnakeCase']($fullName['name']);
            },
            //prefix_module_name
            function ($fullName) {
                return strtolower(str_replace('_', '', $fullName['prefix']))
                    . (!empty($fullName['prefix']) ? '_' : '')
                    . $this->caseReplaceFormats['snakeCase']($fullName['name']);
            },
            //PrefixModuleName
            function ($fullName) {
                return $this->caseReplaceFormats['pascalCase']($fullName['prefix'] . $fullName['name']);
            },
            //Prefixmodulename
            function ($fullName) {
                return str_replace('_', '', $this->caseReplaceFormats['firstUpperCased']($fullName['prefix'] . $fullName['name']));
            },
            //prefixmodulename
            function ($fullName) {
                return $this->caseReplaceFormats['lowerCase']($fullName['prefix'] . $fullName['name']);
            },
        ];

        foreach ($fullNameReplaceFormats as $replaceFormat) {
            $search = $replaceFormat($oldFullName);
            $replace = $replaceFormat($newFullName);
            $replace_pairs[$search] = $replace;
        }
        foreach ($fullNameReplaceFormats as $replaceFormat) {
            $search = $replaceFormat(['prefix' => '', 'name' => $oldFullName['name']]);
            $replace = $replaceFormat(['prefix' => '', 'name' => $newFullName['name']]);
            $replace_pairs[$search] = $replace;
        }

        if ($newAuthor) {
            $authorReplaceFormats = [
                //AuthorName
                function ($authorName) {
                    return $this->caseReplaceFormats['pascalCase']($authorName);
                },
                //authorname
                function ($authorName) {
                    return $this->caseReplaceFormats['lowerCase']($authorName);
                },
            ];

            foreach ($authorReplaceFormats as $replaceFormat) {
                $search = $replaceFormat($oldAuthor);
                $replace = $replaceFormat($newAuthor);
                $replace_pairs[$search] = $replace;
            }
        }

        $extraReplacements = $input->getOption('extra-replacement');
        if ($extraReplacements) {
            foreach ($extraReplacements as $replacement) {
                $terms = explode(',', $replacement);
                if (count($terms) != 2) {
                    $io->error('Each extra replacement must be a pair of two words separated by a comma');

                    return 1;
                }
                $replace_pairs[$terms[0]] = $terms[1];
            }
        }

        $casedExtraReplacements = $input->getOption('cased-extra-replacement');
        if ($casedExtraReplacements) {
            foreach ($casedExtraReplacements as $replacement) {
                $terms = explode(',', $replacement);
                if (count($terms) != 2) {
                    $io->error('Each extra replacement must be a pair of two words separated by a comma');

                    return 1;
                }

                foreach ($this->caseReplaceFormats as $case => $replaceFormat) {
                    $replace_pairs[$replaceFormat($terms[0])] = $replaceFormat($terms[1]);
                }
            }
        }

        $io->title('The following replacements will occur:');
        $table = new Table($output);
        $table->setHeaders(['Occurence', 'Replacement']);
        foreach ($replace_pairs as $search => $replace) {
            $table->addRow([$search, $replace]);
        }
        $table->render();

        $question = new ConfirmationQuestion('Do you confirm these replacements (y for yes, n for no)?', false);
        if (!$questionHelper->ask($input, $output, $question)) {
            return 0;
        }

        $oldFolderName = strtolower($oldFullName['prefix'] . $oldFullName['name']);
        $oldFolderPath = _PS_MODULE_DIR_ . $oldFolderName . '/';
        if (!is_dir($oldFolderPath)) {
            $oldFolderName = strtolower($oldFullName['prefix'] . '_' . $oldFullName['name']);
            $oldFolderPath = _PS_MODULE_DIR_ . $oldFolderName . '/';
        }
        $newFolderPath = _PS_MODULE_DIR_ . strtolower($newFullName['prefix'] . $newFullName['name']) . '/';

        $moduleManagerBuilder = ModuleManagerBuilder::getInstance();
        $moduleManager = $moduleManagerBuilder->build();
        $keepOld = $input->getOption('keep-old');

        if (file_exists($newFolderPath)) {
            $question = new ConfirmationQuestion(
                'The destination folder ' . $newFolderPath . ' already exists. The folder will be removed and the module uninstalled.'
                . PHP_EOL . 'Do you want to continue? (y for yes, n for no)?', false);
            if (!$questionHelper->ask($input, $output, $question)) {
                return 0;
            }

            $newModuleName = strtolower($newFullName['prefix'] . $newFullName['name']);
            if ($moduleManager->isInstalled($newModuleName)) {
                $newModule = Module::getInstanceByName($newModuleName);
                if ($newModule && $newModule->uninstall()) {
                    $io->success('The module ' . $newModuleName . ' has been uninstalled.');
                } else {
                    $io->error('The module ' . $newModuleName . ' couldn\'t be uninstalled.');

                    return 1;
                }
            }
            exec('rm -rf ' . $newFolderPath);
        }

        if (!$keepOld && $oldModule && $moduleManager->isInstalled($oldModuleName)) {
            if ($oldModule->uninstall()) {
                $io->success('The old module ' . $oldModuleName . ' has been uninstalled.');
            } else {
                $io->error('The old module ' . $oldModuleName . ' couldn\'t be uninstalled.');

                return 1;
            }
        }
        exec('cp -R ' . $oldFolderPath . '. ' . $newFolderPath);
        if (!$keepOld) {
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

        if (file_exists($newFolderPath . 'composer.json')) {
            chdir($newFolderPath);
            exec('rm -rf vendor');
            $installCommand = new ArrayInput(['command' => 'update']);
            $dumpautoloadCommand = new ArrayInput(['command' => 'dumpautoload', '-a']);
            $application = new Application();
            $application->setAutoExit(false);
            $application->run($installCommand, new NullOutput());
            $application->run($dumpautoloadCommand, new NullOutput());
            chdir('../..');
        }

        $newModuleName = strtolower($newFullName['prefix'] . $newFullName['name']);
        $newModule = Module::getInstanceByName($newModuleName);
        if ($newModule && $newModule->install()) {
            $io->success('The fresh module ' . $newModuleName . ' has been installed.');
        } else {
            $io->error('The fresh module ' . $newModuleName . ' couldn\'t be installed.');
        }

        return 0;
    }

    public function formatModuleName($input, $output, $arg)
    {
        if (!$arg) {
            return false;
        }

        $explodedArg = explode(',', $arg);
        if (count($explodedArg) > 2) {
            return false;
        }

        $fullName = [];
        $fullName['prefix'] = count($explodedArg) == 2 ? $explodedArg[0] : '';
        $fullName['name'] = $explodedArg[count($explodedArg) - 1];
        if (empty($fullName['name'])) {
            return false;
        }

        if (!empty($fullName['prefix'])) {
            return $fullName;
        }

        $questionHelper = $this->getHelper('question');
        $splitIndex = strpos($fullName['name'], '_');
        if ($splitIndex === false) {
            return $fullName;
        }

        $potentialFullName = [
            'prefix' => substr($fullName['name'], 0, $splitIndex + 1),
            'name' => substr($fullName['name'], $splitIndex + 1),
        ];
        $question = new ConfirmationQuestion($potentialFullName['prefix'] . ' has been identified as a potential prefix.'
            . PHP_EOL . 'Do you want to use it as such? (y for yes, n for no)', false);
        if ($questionHelper->ask($input, $output, $question)) {
            return $potentialFullName;
        }

        return $fullName;
    }
}
