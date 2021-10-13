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
use FOP\Console\Tools\FindAndReplaceTool;
use Module;
use PrestaShop\PrestaShop\Core\Addon\Module\ModuleManagerBuilder;
use RuntimeException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

final class ModuleRename extends Command
{
    /**
     * @var SymfonyStyle
     */
    private $io;

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

            ->setHelp('This command allows you to replace the name of a module in the files and in the database.'
                . PHP_EOL . 'Here are some concrete usage examples:'
                . PHP_EOL . '   • fop:modules:rename PS_,CustomerSignIn KJ,ModuleExample => Rename ps_customersignin module into kjmoduleexample'
                . PHP_EOL . '   • fop:modules:rename KJ,ModuleExample KJ,ModuleExample2 => Rename kjmoduleexample module into kjmoduleexample2');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

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
            $this->installNewModule();

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
                if (!empty($oldModuleFullName['prefix'])) {
                    $oldModuleFullName['prefix'] = substr(
                        $oldModuleClass,
                        stripos($oldModuleClass, $oldModuleFullName['prefix']),
                        strlen($oldModuleFullName['prefix'])
                    ) ?: $oldModuleFullName['prefix'];
                }
                $oldModuleFullName['name'] = substr(
                    $oldModuleClass,
                    stripos(
                        $oldModuleClass,
                        $oldModuleFullName['name']
                    )
                ) ?: $oldModuleFullName['name'];
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
        $findAndReplaceTool = new FindAndReplaceTool();

        $usualCaseFormats = $findAndReplaceTool->getUsualCasesFormats();

        $oldFolderPath = _PS_MODULE_DIR_ . strtolower($this->oldModuleInfos['prefix'] . $this->oldModuleInfos['name']) . '/';
        $oldModuleFiles = $findAndReplaceTool
            ->getFilesSortedByDepth($oldFolderPath)
            ->exclude(['vendor', 'node_modules']);

        $replacePairs = $findAndReplaceTool->findReplacePairsInFiles(
            $oldModuleFiles,
            $findAndReplaceTool->getCasedReplacePairs(
                $usualCaseFormats,
                $this->oldModuleInfos['prefix'] . $this->oldModuleInfos['name'],
                $this->newModuleInfos['prefix'] . $this->newModuleInfos['name']
            )
        ) + $findAndReplaceTool->findReplacePairsInFiles(
            $oldModuleFiles,
            $findAndReplaceTool->getCasedReplacePairs(
                $usualCaseFormats,
                $this->oldModuleInfos['name'],
                $this->newModuleInfos['name']
            )
        );

        if (isset($this->newModuleInfos['author'])) {
            $authorCaseFormats = $findAndReplaceTool->getAestheticCasesFormats();

            $replacePairs +=
                $findAndReplaceTool->findReplacePairsInFiles(
                    $oldModuleFiles,
                    $findAndReplaceTool->getCasedReplacePairs(
                        $authorCaseFormats,
                        $this->oldModuleInfos['author'],
                        $this->newModuleInfos['author']
                    )
                );
        }

        $extraReplacements = $input->getOption('extra-replacement');
        if ($extraReplacements) {
            foreach ($extraReplacements as $replacement) {
                $terms = explode(',', $replacement);
                if (count($terms) != 2) {
                    throw new RuntimeException('Each extra replacement must be a pair of two words separated by a comma');
                }

                $search = $terms[0];
                $replace = $terms[1];
                $replacePairs += $findAndReplaceTool->findReplacePairsInFiles(
                    $oldModuleFiles,
                    [$search => $replace]
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

                $search = $terms[0];
                $replace = $terms[1];
                $replacePairs += $findAndReplaceTool->findReplacePairsInFiles(
                    $oldModuleFiles,
                    $findAndReplaceTool->getCasedReplacePairs($usualCaseFormats, $search, $replace)
                );
            }
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
                $newModule = Module::getInstanceByName($newModuleName);
                if (!($newModule && $newModule->uninstall())) {
                    throw new RuntimeException("The module $newModuleName couldn't be uninstalled.");
                }
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
            if (!$oldModule->uninstall()) {
                throw new RuntimeException("The old module $oldModuleName couldn't be uninstalled.");
            }
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
    }

    private function installNewModule()
    {
        $newFolderPath = _PS_MODULE_DIR_ . strtolower($this->newModuleInfos['prefix'] . $this->newModuleInfos['name']) . '/';
        if (file_exists($newFolderPath . 'composer.json')) {
            $this->io->newLine();
            $this->io->text('Installing composer...');
            chdir($newFolderPath);
            $this->removeFile('vendor');
            $this->removeFile('composer.lock');
            $this->installComposer();
            chdir('../..');
        }

        $newModuleName = strtolower($this->newModuleInfos['prefix'] . $this->newModuleInfos['name']);
        $newModule = Module::getInstanceByName($newModuleName);
        if ($newModule) {
            $this->io->newLine();
            $this->io->text("Installing $newModuleName module...");
            if (!$newModule->install()) {
                $this->io->error("The new module $newModuleName couldn't be installed.");
            }
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
        $question = new ConfirmationQuestion($potentialFullName['prefix'] . ' has been identified as a potential prefix.'
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
        $process = new Process(['composer', 'install']);
        $process->run();
        $this->handleUnsucessfullProcess(__FUNCTION__, $process);

        $process = new Process(['composer', 'dumpautoload', '-a']);
        $process->run();
        $this->handleUnsucessfullProcess(__FUNCTION__, $process);
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
