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

use Exception;
use FOP\Console\Command;
use FOP\Console\Tools\FindAndReplaceTool;
use Module;
use PrestaShop\PrestaShop\Core\Addon\Module\ModuleManagerBuilder;
use RuntimeException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

final class ModuleRename extends Command
{
    /**
     * @var FindAndReplaceTool
     */
    private $findAndReplaceTool;

    /**
     * @var array{prefix: string, name: string, author: string}
     */
    private $oldModuleInfos = [
        'prefix' => '',
        'name' => '',
        'author' => '',
    ];

    /**
     * @var array{prefix: string, name: string, author: string}
     */
    private $newModuleInfos = [
        'prefix' => '',
        'name' => '',
        'author' => '',
    ];

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('fop:module:rename')
            ->setAliases(['fop:modules:rename'])
            ->setDescription('Rename module')

            ->addUsage('[--new-author] <AuthorName>, [-a] <AuthorName>')
            ->addUsage('[--extra-replacement] <search,replace>, [-r] <search,replace>')
            ->addUsage('[--cased-extra-replacement] <PascalCasedSearch,PascalCasedReplace>, [-R] <PascalCasedSearch,PascalCasedReplace>')
            ->addUsage('[--keep-old], [-k]')
            ->addUsage('[--install-new-module], [-i]')

            ->addArgument(
                'old-name',
                InputArgument::REQUIRED,
                'Module current class name with following format : Prefix,ModuleCurrentClassNameWithoutPrefix'
            )
            ->addArgument(
                'new-name',
                InputArgument::REQUIRED,
                'Module new class name with following format : Prefix,ModuleNewClassNameWithoutPrefix'
            )

            ->addOption('new-author', 'a', InputOption::VALUE_REQUIRED, 'New author name')
            ->addOption(
                'extra-replacement',
                'r',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Extra search/replace pairs'
            )
            ->addOption(
                'cased-extra-replacement',
                'R',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Extra search/replace pairs formatted with all usual case formats'
            )
            ->addOption('keep-old', 'k', InputOption::VALUE_NONE, 'Keep the old module untouched and only creates a copy of it with the new name')
            ->addOption('install-new-module', 'i', InputOption::VALUE_NONE, 'Install new module + composer and node_modules if configured')

            ->setHelp('This command allows you to replace the name of a module in the files and in the database.'
                . PHP_EOL . 'Here are some concrete usage examples:'
                . PHP_EOL . '   • fop:modules:rename PS_,CustomerSignIn KJ,ModuleExample => Rename ps_customersignin module into kjmoduleexample'
                . PHP_EOL . '   • fop:modules:rename KJ,ModuleExample KJ,ModuleExample2 => Rename kjmoduleexample module into kjmoduleexample2');
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->findAndReplaceTool = new FindAndReplaceTool();

        parent::initialize($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->io->section('Initialization');

            $this->setOldModuleFullName($input, $output);
            $this->setNewModuleFullName($input, $output);
            $this->setAuthors($input, $output);
            $replacePairs = $this->findReplacePairsInModuleFiles($input, $output);

            $this->io->newLine();
            $this->io->section('Processing');

            $this->uninstallModules($input, $output);
            $this->replaceOccurences($replacePairs, $input->getOption('keep-old'));

            if ($input->getOption('install-new-module')) {
                $this->installNewModule($output);
            }

            $this->io->success('Success: your new module is ready!');

            return 0;
        } catch (RuntimeException $exception) {
            $this->io->error("Error processing {$this->getName()}:\u{a0}" . $exception->getMessage());

            return 1;
        }
    }

    private function setOldModuleFullName($input, $output)
    {
        $oldModuleFullName = $this->getModuleFullName($input, $output, $input->getArgument('old-name'));

        $oldModuleName = strtolower($oldModuleFullName['prefix'] . $oldModuleFullName['name']);
        $oldModuleFolderPath = _PS_MODULE_DIR_ . $oldModuleName . '/';
        if (!file_exists($oldModuleFolderPath)) {
            throw new RuntimeException("The old module folder $oldModuleFolderPath wasn't found.");
        }
        $oldModule = Module::getInstanceByName($oldModuleName);
        if (!preg_match('/[A-Z]/', $oldModuleFullName['prefix'] . $oldModuleFullName['name'])) {
            if ($oldModule) {
                $oldModuleClass = get_class($oldModule);

                $oldModuleFullName = $this->formatOldModuleFullNameFromClassName(
                    $oldModuleFullName,
                    $oldModuleClass,
                    $input,
                    $output
                );
            } else {
                $this->io->newLine();
                $question = new ConfirmationQuestion("The old name is not pascal cased, so the pascal cased occurences won't be replaced."
                    . PHP_EOL . "Are you sure that you didn't forget to pascal case it ? (y to continue, n to abort)", false);

                $questionHelper = $this->getHelper('question');
                if (!$questionHelper->ask($input, $output, $question)) {
                    throw new RuntimeException('Execution aborted by user.');
                }
            }
        }

        $this->oldModuleInfos['prefix'] = $oldModuleFullName['prefix'];
        $this->oldModuleInfos['name'] = $oldModuleFullName['name'];
    }

    /**
     * Returns old module full name with corresponding class name case format.
     *
     * @param array{prefix: string, name: string} $oldModuleFullName
     * @param string $oldModuleClass
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return array{prefix: string, name: string} $formattedFullName
     */
    private function formatOldModuleFullNameFromClassName($oldModuleFullName, $oldModuleClass, $input, $output)
    {
        $formattedFullName = [
            'prefix' => '',
            'name' => '',
        ];

        if (!empty($oldModuleFullName['prefix'])) {
            $formattedFullName['prefix'] = substr(
                $oldModuleClass,
                stripos($oldModuleClass, $oldModuleFullName['prefix']),
                strlen($oldModuleFullName['prefix'])
            ) ?: $oldModuleFullName['prefix'];
        }

        $formattedFullName['name'] = str_ireplace(
            $oldModuleFullName['prefix'],
            '',
            $oldModuleClass
        ) ?: $oldModuleFullName['name'];

        if (empty($formattedFullName['prefix'])) {
            $moduleNameWords = $this->findAndReplaceTool->getWords($formattedFullName['name']);
            if (count($moduleNameWords) > 1) {
                $potentialPrefix = $moduleNameWords[0];

                $this->io->newLine();
                $question = new ConfirmationQuestion($potentialPrefix . " has been identified as a potential prefix in $oldModuleClass class name."
                    . PHP_EOL . 'Do you want to use it as such? (y for yes, n for no)', false);

                $questionHelper = $this->getHelper('question');
                if ($questionHelper->ask($input, $output, $question)) {
                    $formattedFullName['prefix'] = $potentialPrefix;
                    $formattedFullName['name'] = str_ireplace(
                        $formattedFullName['prefix'],
                        '',
                        $formattedFullName['name']
                    );
                }
            }
        }

        return $formattedFullName;
    }

    private function setNewModuleFullName($input, $output)
    {
        $newModuleFullName = $this->getModuleFullName($input, $output, $input->getArgument('new-name'));

        if (!preg_match('/[A-Z]/', $newModuleFullName['prefix'] . $newModuleFullName['name'])) {
            $this->io->newLine();
            $question = new ConfirmationQuestion('The new name is not pascal cased, so it will be counted as one word. It can cause aesthetic issues.'
            . PHP_EOL . "Are you sure that you didn't forget to pascal case it ? (y to continue, n to abort)", false);

            $questionHelper = $this->getHelper('question');
            if (!$questionHelper->ask($input, $output, $question)) {
                throw new RuntimeException('Execution aborted by user.');
            }
        }

        $newModuleName = strtolower($newModuleFullName['prefix'] . $newModuleFullName['name']);
        $oldModuleName = strtolower($this->oldModuleInfos['prefix'] . $this->oldModuleInfos['name']);
        $keepOld = $input->getOption('keep-old');
        if ($oldModuleName === $newModuleName && $keepOld) {
            throw new RuntimeException("You can't keep the old module when the new module name is equal to the old one.");
        }

        $this->newModuleInfos['prefix'] = $newModuleFullName['prefix'];
        $this->newModuleInfos['name'] = $newModuleFullName['name'];
    }

    private function setAuthors($input, $output)
    {
        $newAuthor = $input->getOption('new-author');
        $oldAuthor = '';
        $oldModuleName = strtolower($this->oldModuleInfos['prefix'] . $this->oldModuleInfos['name']);
        $oldModule = Module::getInstanceByName($oldModuleName);

        if ($newAuthor) {
            if ($oldModule) {
                $oldAuthor = $oldModule->author;
                if ($newAuthor == $oldAuthor) {
                    $this->io->newLine();
                    $this->io->text('Author replacements have been ignored since the old and the new one are equal');
                    $newAuthor = false;
                }
            } else {
                $this->io->newLine();
                $question = new Question("Can't create old module instance to retrieve old author name. "
                . PHP_EOL . 'Please specify the old author name manually (empty to ignore author replacement):');

                $questionHelper = $this->getHelper('question');
                $oldAuthor = $questionHelper->ask($input, $output, $question);
                if (empty($oldAuthor)) {
                    $newAuthor = false;
                }
            }
        }

        $this->oldModuleInfos['author'] = $oldAuthor;
        $this->newModuleInfos['author'] = $newAuthor;
    }

    private function findReplacePairsInModuleFiles($input, $output)
    {
        $usualCaseFormats = $this->findAndReplaceTool->getUsualCasesFormats();

        $oldFolderPath = _PS_MODULE_DIR_ . strtolower($this->oldModuleInfos['prefix'] . $this->oldModuleInfos['name']) . '/';
        $oldModuleFiles = $this->findAndReplaceTool
            ->getFilesSortedByDepth($oldFolderPath)
            ->exclude(['vendor', 'node_modules']);

        $searchAndReplacePairs = [
            [
                'search' => $this->oldModuleInfos['name'],
                'replace' => $this->newModuleInfos['name'],
                'caseFormats' => $usualCaseFormats,
            ],
            [
                'search' => $this->oldModuleInfos['prefix'] . $this->oldModuleInfos['name'],
                'replace' => $this->newModuleInfos['prefix'] . $this->newModuleInfos['name'],
                'caseFormats' => $usualCaseFormats,
            ],
            [
                'search' => array_merge([$this->oldModuleInfos['prefix']], $this->findAndReplaceTool->getWords($this->oldModuleInfos['name'])),
                'replace' => array_merge([$this->newModuleInfos['prefix']], $this->findAndReplaceTool->getWords($this->newModuleInfos['name'])),
                'caseFormats' => $usualCaseFormats,
            ],
        ];

        if (!empty($this->newModuleInfos['author'])) {
            array_push(
                $searchAndReplacePairs,
                [
                    'search' => $this->oldModuleInfos['author'],
                    'replace' => $this->newModuleInfos['author'],
                    'caseFormats' => $usualCaseFormats,
                ]
            );
        }

        $extraReplacements = $input->getOption('extra-replacement');
        if ($extraReplacements) {
            foreach ($extraReplacements as $replacement) {
                $terms = explode(',', $replacement);
                if (count($terms) != 2) {
                    throw new RuntimeException('Each extra replacement must be a pair of two words separated by a comma');
                }

                array_push(
                    $searchAndReplacePairs,
                    [
                        'search' => $terms[0],
                        'replace' => $terms[1],
                    ]
                );
            }
        }

        $casedExtraReplacements = $input->getOption('cased-extra-replacement');
        if ($casedExtraReplacements) {
            foreach ($casedExtraReplacements as $replacement) {
                $terms = explode(',', $replacement);
                if (count($terms) != 2) {
                    throw new RuntimeException('Each extra replacement must be a pair of two words separated by a comma');
                }

                array_push(
                    $searchAndReplacePairs,
                    [
                        'search' => $terms[0],
                        'replace' => $terms[1],
                        'caseFormats' => $usualCaseFormats,
                    ]
                );
            }
        }

        $replacePairs = [];

        foreach ($searchAndReplacePairs as $searchAndReplacePair) {
            if (empty($searchAndReplacePair['search'])) {
                continue;
            }
            $search = $searchAndReplacePair['search'];
            $replace = $searchAndReplacePair['replace'];

            $caseFormats = isset($searchAndReplacePair['caseFormats'])
                ? $searchAndReplacePair['caseFormats']
                : [];

            $replacePairs = array_merge(
                $replacePairs,
                $this->findAndReplaceTool->findReplacePairsInFiles(
                    $oldModuleFiles,
                    $this->findAndReplaceTool->getCasedReplacePairs(
                        $search,
                        $replace,
                        $caseFormats
                    )
                )
            );
        }

        $this->io->newLine();
        $this->io->text('The following replacements will occur:');
        $table = new Table($output);
        $table->setHeaders(['Occurence', 'Replacement']);
        foreach ($replacePairs as $search => $replace) {
            $table->addRow([$search, $replace]);
        }
        $table->render();

        $this->io->newLine();
        $question = new ConfirmationQuestion('Do you confirm these replacements (y for yes, n for no)?', false);

        $questionHelper = $this->getHelper('question');
        if (!$questionHelper->ask($input, $output, $question)) {
            throw new RuntimeException('Execution aborted by user.');
        }

        return $replacePairs;
    }

    private function uninstallModules($input, $output)
    {
        $oldFolderPath = _PS_MODULE_DIR_ . strtolower($this->oldModuleInfos['prefix'] . $this->oldModuleInfos['name']) . '/';
        $newFolderPath = _PS_MODULE_DIR_ . strtolower($this->newModuleInfos['prefix'] . $this->newModuleInfos['name']) . '/';

        $moduleManagerBuilder = ModuleManagerBuilder::getInstance();
        $moduleManager = $moduleManagerBuilder->build();

        if (file_exists($newFolderPath) && $oldFolderPath != $newFolderPath) {
            $question = new ConfirmationQuestion(
                'The destination folder ' . $newFolderPath . ' already exists. The folder will be removed and the module will be uninstalled.'
                . PHP_EOL . 'Do you want to continue? (y for yes, n for no)?',
                false
            );

            $questionHelper = $this->getHelper('question');
            if (!$questionHelper->ask($input, $output, $question)) {
                throw new RuntimeException('Execution aborted by user.');
            }

            $newModuleName = strtolower($this->newModuleInfos['prefix'] . $this->newModuleInfos['name']);
            if ($moduleManager->isInstalled($newModuleName)) {
                $this->io->newLine();
                $this->io->text("Uninstalling $newModuleName module...");
                $this->uninstallModule($newModuleName, $output);
            }

            $this->io->newLine();
            $this->io->text("Removing $newFolderPath folder...");
            $this->removeFile($newFolderPath);
        }

        $keepOld = $input->getOption('keep-old');
        $oldModuleName = strtolower($this->oldModuleInfos['prefix'] . $this->oldModuleInfos['name']);
        $oldModule = Module::getInstanceByName($oldModuleName);
        if (!$keepOld && $oldModule && $moduleManager->isInstalled($oldModuleName)) {
            $this->io->newLine();
            $this->io->text("Uninstalling $oldModuleName module...");
            $this->uninstallModule($oldModuleName, $output);
        }
    }

    private function replaceOccurences($replacePairs, $keepOld)
    {
        $oldFolderPath = _PS_MODULE_DIR_ . strtolower($this->oldModuleInfos['prefix'] . $this->oldModuleInfos['name']) . '/';
        $newFolderPath = _PS_MODULE_DIR_ . strtolower($this->newModuleInfos['prefix'] . $this->newModuleInfos['name']) . '/';
        if ($oldFolderPath != $newFolderPath) {
            $this->io->newLine();
            $this->io->text("Copying $oldFolderPath folder to $newFolderPath folder...");
            $this->copyFolder($oldFolderPath, $newFolderPath);
            if (!$keepOld) {
                $this->io->newLine();
                $this->io->text("Removing $oldFolderPath folder...");
                $this->removeFile($oldFolderPath);
            }
        }

        $finder = new Finder();
        $iterator = $finder
            ->exclude(['vendor', 'node_modules'])
            ->sort(function (\SplFileInfo $a, \SplFileInfo $b) {
                $depth = substr_count($a->getRealPath(), '/') - substr_count($b->getRealPath(), '/');

                return ($depth === 0) ? strlen($a->getRealPath()) - strlen($b->getRealPath()) : $depth;
            })
            ->in($newFolderPath);

        $this->io->newLine();
        $this->io->text("Replacing occurences in $newFolderPath folder...");
        $this->io->progressStart($iterator->count());
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $fileContent = file_get_contents($file->getPathname());
                file_put_contents($file->getPathname(), strtr($fileContent, $replacePairs));
            }
            rename(
                $newFolderPath . strtr($file->getRelativePath(), $replacePairs) . '/' . $file->getFilename(),
                $newFolderPath . strtr($file->getRelativePathname(), $replacePairs)
            );
            $this->io->progressAdvance();
        }
        $this->io->newLine();
    }

    private function installNewModule($output)
    {
        $newFolderPath = _PS_MODULE_DIR_ . strtolower($this->newModuleInfos['prefix'] . $this->newModuleInfos['name']) . '/';

        chdir($newFolderPath);

        if (file_exists('composer.json')) {
            $this->io->newLine();
            $this->io->text('Installing composer...');
            $this->installComposer();
        }

        if (file_exists('_dev')) {
            $this->io->newLine();
            $this->io->text('Installing node modules...');
            $this->installNodeModules();
        }

        chdir('../..');

        $newModuleName = strtolower($this->newModuleInfos['prefix'] . $this->newModuleInfos['name']);
        $newModule = Module::getInstanceByName($newModuleName);
        if ($newModule) {
            $this->io->newLine();
            $this->io->text("Installing $newModuleName module...");
            $this->installModule($newModuleName, $output);
        }
    }

    /**
     * Extracts the prefix and the name from the module class name.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $moduleClassName
     *
     * @return array{prefix: string, name: string}
     *
     * @throws RuntimeException
     */
    private function getModuleFullName($input, $output, $moduleClassName)
    {
        $explodedName = explode(',', $moduleClassName);
        if (count($explodedName) > 2) {
            throw new RuntimeException('Only one comma is accepted in module class name argument.');
        }

        $fullName = [];
        $fullName['prefix'] = count($explodedName) == 2 ? $explodedName[0] : '';
        $fullName['name'] = $explodedName[count($explodedName) - 1];
        if (empty($fullName['name'])) {
            throw new RuntimeException("Module name can't be empty.");
        }

        if (!empty($fullName['prefix'])) {
            return $fullName;
        }

        $splitIndex = strpos($fullName['name'], '_');
        if ($splitIndex === false) {
            return $fullName;
        }

        $potentialFullName = [
            'prefix' => substr($fullName['name'], 0, $splitIndex + 1),
            'name' => substr($fullName['name'], $splitIndex + 1),
        ];

        $this->io->newLine();
        $question = new ConfirmationQuestion($potentialFullName['prefix'] . " has been identified as a potential prefix in $moduleClassName class name."
            . PHP_EOL . 'Do you want to use it as such? (y for yes, n for no)', false);

        $questionHelper = $this->getHelper('question');
        if ($questionHelper->ask($input, $output, $question)) {
            return $potentialFullName;
        }

        return $fullName;
    }

    private function removeFile($filePath)
    {
        if ($this->isWindows()) {
            $filePath = str_replace('/', '\\', $filePath);

            $output = [];
            $return = 0;
            $returnLine = exec("rmdir /S /Q $filePath", $output, $return);

            if ($return !== 0) {
                throw new RuntimeException('Error doing ' . __FUNCTION__ . ' : ' . PHP_EOL . ' : ' . print_r($output, true));
            }
        } else {
            $process = new Process(['rm', '-rf', $filePath]);
            $process->run();
            $this->handleUnsucessfullProcess(__FUNCTION__, $process);
        }
    }

    private function copyFolder($sourcePath, $destinationPath)
    {
        if ($this->isWindows()) {
            $sourcePath = str_replace('/', '\\', $sourcePath);
            $destinationPath = str_replace('/', '\\', $destinationPath);

            $output = [];
            $return = 0;
            $returnLine = exec("robocopy $sourcePath $destinationPath /E", $output, $return);

            if ($return !== 0 && $return !== 1) {
                throw new RuntimeException('Error doing ' . __FUNCTION__ . ' : ' . PHP_EOL . ' : ' . print_r($output, true));
            }
        } else {
            $process = new Process(['cp', '-R', $sourcePath, $destinationPath]);
            $process->run();
            $this->handleUnsucessfullProcess(__FUNCTION__, $process);
        }
    }

    private function installComposer()
    {
        $this->removeFile('vendor');
        $this->removeFile('composer.lock');

        $process = new Process(['composer', 'install']);
        $process->run();
        $this->handleUnsucessfullProcess(__FUNCTION__, $process);

        $process = new Process(['composer', 'dumpautoload', '-a']);
        $process->run();
        $this->handleUnsucessfullProcess(__FUNCTION__, $process);
    }

    private function installNodeModules()
    {
        chdir('_dev');

        $this->removeFile('_dev/node_modules');
        $this->removeFile('_dev/package-lock.json');

        $process = new Process(['npm', 'install']);
        $process->run();
        $this->handleUnsucessfullProcess(__FUNCTION__, $process);

        chdir('..');
    }

    private function installModule($moduleName, $output)
    {
        $command = $this->getApplication()->find('prestashop:module');
        $arguments = [
            'action' => 'install',
            'module name' => $moduleName,
        ];

        try {
            if ($command->run(new ArrayInput($arguments), $output)) {
                throw new RuntimeException("The module $moduleName couldn't be installed.");
            }
        } catch (Exception $e) {
            throw new RuntimeException("The new module $moduleName couldn't be installed:" . PHP_EOL . $e->getMessage());
        }
    }

    private function uninstallModule($moduleName, $output)
    {
        $command = $this->getApplication()->find('prestashop:module');
        $arguments = [
            'action' => 'uninstall',
            'module name' => $moduleName,
        ];

        try {
            if ($command->run(new ArrayInput($arguments), $output)) {
                throw new RuntimeException("The module $moduleName couldn't be uninstalled.");
            }
        } catch (Exception $e) {
            throw new RuntimeException("The new module $moduleName couldn't be uninstalled." . PHP_EOL . $e->getMessage());
        }
    }

    private function isWindows()
    {
        return 'WIN' === strtoupper(substr(PHP_OS, 0, 3));
    }

    private function handleUnsucessfullProcess(string $__FUNCTION__, Process $process)
    {
        if (!$process->isSuccessful()) {
            throw new RuntimeException("Error doing $__FUNCTION__ : " . PHP_EOL . ' : ' . $process->getErrorOutput());
        }
    }
}
