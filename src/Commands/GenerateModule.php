<?php

namespace FOP\Console\Commands;

use FOP\Console\Builder\ModuleBuilder;
use FOP\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * This command is a working exemple.
 */
final class GenerateModule extends Command
{
    private $tabHook = [];
    private $tabSQL = [];
    private $tabTabs = [];
    private $hookStringInstall = '';
    private $hookStringFunction = '';
    private $sqlTableStringInstall = '';
    private $sqlTableStringUninstall = '';
    private $sqlTableStringFunction = '';
    private $tabStringInstall = '';
    
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('fop:module:generate')
            ->setDescription('create a new module')
            ->setHelp('This command instantiate every files and directores of Symfony/Prestashop in Console Context')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $filesystem = new Filesystem();
        
        $io->title('This program was carried out by FriendsOfPresta');
        $io->warning('Be careful if you run this program it may erase data, be sure to specify an unused module name or you know what you are doing');

        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Would like to continue (defaults to No)',
            ['No', 'Yes'],
            0
        );
        $question->setErrorMessage('Incorrect number, enter 0 for No and 1 for Yes.');
        $confirm = $helper->ask($input, $output, $question);
        if ($confirm == 'No') {
            $io->text('End of the program ...');

            return;
        }

        $io->newLine(1);
        $io->section('Edition of the entry point');
        $name = $io->ask('Give a name to your module', 'fop_examplemodule');
        $displayName = $io->ask('Give a display name to your module', 'FOP Exemple Module');
        $fileName = strtolower($name);
        $filenameFirstletterCaps = ucfirst($name);
        $filenameCamel = ucwords(strtolower($name));
        $description = $io->ask('Give the description of the module');
        $author = $io->ask('Give the author of the module', 'FOP');
        $mailContact = $io->ask('Give your contact email to reach you in case of problems', 'Generic@email.com');

        $io->section('File generation');

        $fileDirectories = [];
        try {
            $fileDirectories['dir'][] = _PS_MODULE_DIR_ . '' . $fileName;
            $fileDirectories['dir'][] = _PS_MODULE_DIR_ . '/' . $fileName . '/config';
            $fileDirectories['dir'][] = _PS_MODULE_DIR_ . '/' . $fileName . '/controller/admin';
            $fileDirectories['dir'][] = _PS_MODULE_DIR_ . '/' . $fileName . '/controller/front';
            $fileDirectories['dir'][] = _PS_MODULE_DIR_ . '/' . $fileName . '/src/cache';
            $fileDirectories['dir'][] = _PS_MODULE_DIR_ . '/' . $fileName . '/src/Controller/Admin/Improve/Design';
            $fileDirectories['dir'][] = _PS_MODULE_DIR_ . '/' . $fileName . '/src/Core/Grid/Definition/Factory';
            $fileDirectories['dir'][] = _PS_MODULE_DIR_ . '/' . $fileName . '/src/Core/Grid/Query';
            $fileDirectories['dir'][] = _PS_MODULE_DIR_ . '/' . $fileName . '/src/Core/Search/Filters';
            $fileDirectories['dir'][] = _PS_MODULE_DIR_ . '/' . $fileName . '/src/Form/ChoiceProvider';
            $fileDirectories['dir'][] = _PS_MODULE_DIR_ . '/' . $fileName . '/src/Form/Type';
            $fileDirectories['dir'][] = _PS_MODULE_DIR_ . '/' . $fileName . '/src/img/banner';
            $fileDirectories['dir'][] = _PS_MODULE_DIR_ . '/' . $fileName . '/src/img/gerant';
            $fileDirectories['dir'][] = _PS_MODULE_DIR_ . '/' . $fileName . '/src/img/slider';
            $fileDirectories['dir'][] = _PS_MODULE_DIR_ . '/' . $fileName . '/src/Model';
            $fileDirectories['dir'][] = _PS_MODULE_DIR_ . '/' . $fileName . '/src/Presenter';
            $fileDirectories['dir'][] = _PS_MODULE_DIR_ . '/' . $fileName . '/src/Repository';
            $fileDirectories['dir'][] = _PS_MODULE_DIR_ . '/' . $fileName . '/views/js/form';
            $fileDirectories['dir'][] = _PS_MODULE_DIR_ . '/' . $fileName . '/views/js/grid';
            $fileDirectories['dir'][] = _PS_MODULE_DIR_ . '/' . $fileName . '/views/public';
            $fileDirectories['dir'][] = _PS_MODULE_DIR_ . '/' . $fileName . '/views/templates/admin';
            $fileDirectories['dir'][] = _PS_MODULE_DIR_ . '/' . $fileName . '/views/templates/hook';
            $fileDirectories['file'][] = _PS_MODULE_DIR_ . '/' . $fileName . '/config/services.yml';
            $fileDirectories['file'][] = _PS_MODULE_DIR_ . '/' . $fileName . '/config/routes.yml';
            $fileDirectories['file'][] = _PS_MODULE_DIR_ . '/' . $fileName . '/' . $fileName . '.php';
            $fileDirectories['file'][] = _PS_MODULE_DIR_ . '/' . $fileName . '/composer.json';
            $rows = (count($fileDirectories['dir']) + count($fileDirectories['file']));
            $progressBar = new ProgressBar($output, $rows);
            $progressBar->setFormat(
                "<fg=white;bg=cyan> %status:-45s%</>\n%current%/%max% [%bar%] %percent:3s%%"
            );
            $progressBar->setBarCharacter('<fg=magenta>=</>');
            $progressBar->setProgressCharacter('<fg=green>➤</>');
            $progressBar->start();
            $i = 0;
            foreach ($fileDirectories['dir'] as $directory) {
                $filesystem->mkdir($directory, 0755);
                if ($i = 0) {
                    $progressBar->setMessage('Starting...', 'status');
                } else {
                    $progressBar->setMessage('In progress...', 'status');
                }

                $progressBar->advance();
                ++$i;
            }
            foreach ($fileDirectories['file'] as $file) {
                $filesystem->touch($file);
                if ($i < $rows - 1) {
                    $progressBar->setMessage('Almost finished...', 'status');
                } else {
                    $progressBar->setMessage('In progress...', 'status');
                }

                $progressBar->advance();
                ++$i;
            }

            $progressBar->finish();
            $io->newLine(2);
            $io->success('Generation finished');
        } catch (IOExceptionInterface $exception) {
            $io->error('An error as occurred in the directory path at ' . $exception->getPath());
        }

        $io->section('Menu for generating hooks');
        $this->menuHook($io, $input, $output);
        $io->newLine(1);
        $io->success('Generation finished');

        $io->section('Menu for SQL tables');
        $nbtable = 0;
        $this->menuSQl($io, $input, $output, $nbtable);
        $io->newLine(1);
        $io->success('Generation finished');

        $io->section('Menu for Tabs');
        $this->menuTab($io, $input, $output);
        $io->newLine(1);
        $io->success('Generation finished');

        if (sizeof($this->tabHook) > 0) {
            $this->hookStringInstall = Modulebuilder::getInstallString($this->tabHook);
            $this->hookStringFunction = ModuleBuilder::getFunctionString($this->tabHook);
        }

        if (sizeof($this->tabSQL) > 0) {
            $this->sqlTableStringInstall = Modulebuilder::getInstallSqlTableString();
            $this->sqlTableStringUninstall = Modulebuilder::getUninstallSqlTableString();
            $this->sqlTableStringFunction = ModuleBuilder::getFunctionSqlTableString($this->tabSQL);
        }

        if (sizeof($this->tabTabs) > 0) {
            $this->tabStringInstall = Modulebuilder::getInstallTabString($this->tabTabs);
        }

        ModuleBuilder::getEntryPointString($filenameFirstletterCaps, $fileName, $filenameCamel, $author,
            $displayName, $description,$this->hookStringInstall, $this->hookStringFunction, $this->sqlTableStringInstall,
            $this->sqlTableStringUninstall, $this->sqlTableStringFunction, $this->tabStringInstall, $filesystem);

        ModuleBuilder::getJsonComposerString($fileName, $description, $author, $mailContact, $filesystem);
        //automatic dump-autoload the json
        $this->launchModule($fileName);

        $io->success('Module created, installed and activated');
    }

    /**
     * This function ask the user which hook he want add to the module and build the code consequently
     */
    protected function menuHook($io, $input, $output)
    {
        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Please select a menu entry (defaults to "leave")',
            ['Add hook', 'Edit hook', 'Delete hook', 'Display hooks', 'Leave'],
            4
        );
        $question->setErrorMessage('Number %s is invalid.');
        $number = $helper->ask($input, $output, $question);

        switch ($number) {
            case 'Add hook':
                $this->tabHook[] = $io->ask('Give the name of the hook', 'displayAfterFooter');
                $io->newLine();
                $this->menuHook($io, $input, $output);
                break;
            case 'Edit hook':
                if (sizeof($this->tabHook) <= 0) {
                    $io->text('You have to add a hook to modify one');
                    $io->newLine();
                } else {
                    foreach ($this->tabHook as $number => $hookname) {
                        $io->text($number . '. ' . $hookname);
                    }
                    $io->newLine();
                    $numMenu = $io->ask('Give the number of the hook to modify');
                    if (isset($this->tabHook[$numMenu])) {
                        $this->tabHook[$numMenu] = $io->ask('Give the new name of the hook', $this->tabHook[$numMenu]);
                        $io->newLine();
                    } else {
                        $io->text('There is no hook number ' . $numMenu);
                        $io->newLine();
                    }
                }
                $this->menuHook($io, $input, $output);
                break;
            case 'Delete hook':
                if (sizeof($this->tabHook) <= 0) {
                    $io->text('You have to add a hook before deleting one');
                    $io->newLine();
                } else {
                    foreach ($this->tabHook as $number => $hookname) {
                        $io->text($number . '. ' . $hookname);
                    }
                    $io->newLine();
                    $numMenu = $io->ask('Give the number of the hook to delete');
                    if (isset($this->tabHook[$numMenu])) {
                        unset($this->tabHook[$numMenu]);
                        $io->newLine();
                    } else {
                        $io->text('There is no hook number ' . $numMenu);
                        $io->newLine();
                    }
                }
                $this->menuHook($io, $input, $output);
                break;
            case 'Display hooks':
                if (sizeof($this->tabHook) <= 0) {
                    $io->text('There is no Hook for the moment');
                    $io->newLine();
                } else {
                    foreach ($this->tabHook as $number => $hookname) {
                        $io->text($number . '. ' . $hookname);
                    }
                    $io->newLine();
                }
                $this->menuHook($io, $input, $output);
                break;
            case 'Leave':
                break;
            default:
                $io->text("That's not a correct number");
                $io->newLine();
                $this->menuHook($io, $input, $output);
                break;
        }
    }

    /**
     * This function ask the user which tables he want add to database and build the code consequently
     */
    protected function menuSQL($io, $input, $output, $nbtable, $nbchamp = null)
    {
        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Please select a menu entry (defaults to "leave")',
            ['Add table', 'Edit table', 'Edit fields', 'Delete table', 'Display tables', 'Leave'],
            5
        );
        $question->setErrorMessage('Number %s is invalid.');
        $number = $helper->ask($input, $output, $question);

        switch ($number) {
            case 'Add table':
                $this->tabSQL[$nbtable] = ['tab_name' => $io->ask('Give the name of the table => without DB prefix <=', 'fop_product'),
                                            'pk_constraint' => $io->ask('What\'s the name of the primary key', 'id_product'), ];
                $io->newLine();
                $nbchamp = 0;
                $nbchamp = $this->sousMenuSQL($io, $input, $output, $nbtable, $nbchamp);
                ++$nbtable;
                $io->newLine();
                $this->menuSQL($io, $input, $output, $nbtable, $nbchamp);
                break;
            case 'Edit fields':
                if (sizeof($this->tabSQL) <= 0) {
                    $io->text('You have to add a table to edit the fields');
                    $io->newLine();
                } else {
                    foreach ($this->tabSQL as $number => $table) {
                        $io->text('table ' . ($number + 1) . ': ' . $table['tab_name']);
                    }
                    $io->newLine();
                    $numTable = $io->ask('Give the number of the table to modify');
                    if (isset($this->tabSQL[$numTable - 1])) {
                        $nbchamp = $this->sousMenuSQL($io, $input, $output, $numTable - 1, $nbchamp);
                        $io->newLine();
                    } else {
                        $io->text('There is no table number ' . $numTable);
                        $io->newLine();
                    }
                }
                $this->menuSQL($io, $input, $output, $nbtable, $nbchamp);
                break;
            case 'Edit table':
                if (sizeof($this->tabSQL) <= 0) {
                    $io->text('You have to add a table to modify one');
                    $io->newLine();
                } else {
                    foreach ($this->tabSQL as $number => $table) {
                        $io->text('table ' . ($number + 1) . ': ' . $table['tab_name']);
                    }
                    $io->newLine();
                    $numTable = $io->ask('Give the number of the table to modify');
                    if (isset($this->tabSQL[$numTable - 1])) {
                        $this->tabSQL[$numTable - 1]['tab_name'] = $io->ask('Give the new name of the table', $this->tabSQL[$numTable - 1]['tab_name']);
                        $io->newLine();
                    } else {
                        $io->text('There is no table number ' . $numTable);
                        $io->newLine();
                    }
                }
                $this->menuSQL($io, $input, $output, $nbtable, $nbchamp);
                break;
            case 'Delete table':
                if (sizeof($this->tabSQL) <= 0) {
                    $io->text('You have to add a table before deleting deleting one');
                    $io->newLine();
                } else {
                    foreach ($this->tabSQL as $number => $table) {
                        $io->text('table ' . ($number + 1) . ': ' . $table['tab_name']);
                    }
                    $io->newLine();
                    $numTable = $io->ask('Give the number of the table to delete');
                    if (isset($this->tabSQL[$numTable - 1])) {
                        unset($this->tabSQL[$numTable - 1]);
                        --$nbtable;
                        $io->newLine();
                    } else {
                        $io->text('There is no table number ' . $numTable);
                        $io->newLine();
                    }
                }
                $this->menuSQL($io, $input, $output, $nbtable, $nbchamp);
                break;
            case 'Display tables':
                if (sizeof($this->tabSQL) <= 0) {
                    $io->text('There is no tables for the moment');
                    $io->newLine();
                } else {
                    foreach ($this->tabSQL as $number => $table) {
                        $io->text('table ' . ($number + 1) . ': ' . $table['tab_name']);
                    }
                    $io->newLine();
                }
                $this->menuSQL($io, $input, $output, $nbtable, $nbchamp);
                break;
            case 'Leave':
                break;
            default:
                $io->text("That's not a correct entry");
                $io->newLine();
                $this->menuSQL($io, $input, $output, $nbtable, $nbchamp);
                break;
        }
    }

    /**
     * This function ask the user which field he want add to the sql table and build the code consequently
     */
    protected function sousMenuSQL($io, $input, $output, $nbtable, $nbchamp)
    {
        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Please select a menu entry (defaults to "leave")',
            ['Add field', 'Edit field', 'Delete field', 'Display fields', 'Leave'],
            4
        );
        $question->setErrorMessage('Number %s is invalid.');
        $number = $helper->ask($input, $output, $question);

        switch ($number) {
            case 'Add field':
                $name = $io->ask('Give a name to the field of the table', 'name');
                $type = $this->askType($input, $output);
                $size = $this->askSize($io, $type);
                $null = $this->askNullable($input, $output);
                $this->tabSQL[$nbtable][$nbchamp] = [
                    'name' => $name,
                    'type' => $type,
                    'size' => $size,
                    'null' => $null,
                ];
                ++$nbchamp;
                $io->newLine();
                $this->sousMenuSQL($io, $input, $output, $nbtable, $nbchamp);
                break;
            case 'Edit field':
                if (sizeof($this->tabSQL[$nbtable]) <= 1) {
                    $io->text('You have to add a field to modify one');
                    $io->newLine();
                } else {
                    foreach ($this->tabSQL[$nbtable] as $number => $field) {
                        if (is_array($field)) {
                            $field = implode(',', $field);
                            $io->text('Field list ' . ($number + 1) . ': ' . $field);
                        }
                    }
                    $io->newLine();
                    $numMenu = $io->ask('Give the field list number to modifiy it');
                    if (isset($this->tabSQL[$nbtable][$numMenu - 1])) {
                        $constraint = $this->askConstraint($input, $output);
                        switch ($constraint) {
                            case 'name':
                                $newname = $io->ask('Give the new name of the field', $this->tabSQL[$nbtable][$numMenu - 1][$constraint]);
                                $this->tabSQL[$nbtable][$numMenu - 1][$constraint] = $newname;
                                break;
                            case 'type':
                                $newtype = $this->askType($input, $output);
                                $this->tabSQL[$nbtable][$numMenu - 1][$constraint] = $newtype;
                                $types = ['Date', 'Time', 'Blob'];
                                if (in_array($newtype, $types)) {
                                    $this->tabSQL[$nbtable][$numMenu - 1]['size'] = null;
                                } else {
                                    $newsize = $io->ask('This new type require a new size. Give the new size of the field', $this->tabSQL[$nbtable][$numMenu - 1]['size']);
                                    $this->tabSQL[$nbtable][$numMenu - 1]['size'] = $newsize;
                                }
                                break;
                            case 'size':
                                $types = ['Date', 'Time', 'Blob'];
                                if (!in_array($this->tabSQL[$nbtable][$numMenu - 1]['type'], $types)) {
                                    $newsize = $io->ask('Give the new size of the field', $this->tabSQL[$nbtable][$numMenu - 1][$constraint]);
                                    $this->tabSQL[$nbtable][$numMenu - 1][$constraint] = $newsize;
                                } else {
                                    $io->text('The size of this type cannot be changed');
                                }
                                break;
                            case 'null':
                                $newnullable = $this->askNullable($input, $output);
                                $this->tabSQL[$nbtable][$numMenu - 1][$constraint] = $newnullable;
                                break;
                            case 'none':
                                break;
                        }
                        $io->newLine();
                    } else {
                        $io->text('There is no field number ' . $numMenu);
                        $io->newLine();
                    }
                }
                $this->sousMenuSQL($io, $input, $output, $nbtable, $nbchamp);
                break;
            case 'Delete field':
                if (sizeof($this->tabSQL[$nbtable]) <= 1) {
                    $io->text('You have to add a field before deleting one');
                    $io->newLine();
                } else {
                    foreach ($this->tabSQL[$nbtable] as $number => $field) {
                        if (is_array($field)) {
                            $field = implode(',', $field);
                            $io->text('Field list ' . ($number + 1) . ': ' . $field);
                        }
                    }
                    $io->newLine();
                    $numMenu = $io->ask('Give the field list number to delete it');
                    if (isset($this->tabSQL[$nbtable][$numMenu - 1])) {
                        unset($this->tabSQL[$nbtable][$numMenu - 1]);
                        $io->newLine();
                        --$nbchamp;
                    } else {
                        $io->text('There is no field number ' . $numMenu);
                        $io->newLine();
                    }
                }
                $this->sousMenuSQL($io, $input, $output, $nbtable, $nbchamp);
                break;
            case 'Display fields':
                if (sizeof($this->tabSQL[$nbtable]) <= 1) {
                    $io->text('There is no field for the moment');
                    $io->newLine();
                } else {
                    foreach ($this->tabSQL[$nbtable] as $number => $field) {
                        if (is_array($field)) {
                            $field = implode(',', $field);
                            $io->text('Field list ' . ($number + 1) . ': ' . $field);
                        }
                    }
                    $io->newLine();
                }
                $this->sousMenuSQL($io, $input, $output, $nbtable, $nbchamp);
                break;
            case 'Leave':
                break;
            default:
                $io->text("That's not a correct entry");
                $io->newLine();
                $this->sousMenuSQL($io, $input, $output, $nbtable, $nbchamp);
                break;
        }

        return $nbchamp;
    }

    /**
     * This function ask the user which tabs he want add to the module and build the code consequently
     */
    protected function menuTab($io, $input, $output)
    {
        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Please select a menu entry (defaults to "Leave")',
            ['Add tab', 'Edit tab', 'Delete tab', 'Display tabs', 'Leave'],
            4
        );
        $question->setErrorMessage('Number %s is invalid.');
        $number = $helper->ask($input, $output, $question);

        switch ($number) {
            case 'Add tab':
                $this->tabTabs[] = [
                    'name' => $io->ask('Give the name of the tab'),
                    'class_name' => $io->ask('Give the class_name of the tab'),
                    'parent_class_name' => $io->ask('Give the parent_class_name of the tab'),
                ];
                $io->newLine();
                $this->menuTab($io, $input, $output);
                break;
            case 'Edit tab':
                if (sizeof($this->tabTabs) <= 0) {
                    $io->text('You have to add a tab before modifying one');
                    $io->newLine();
                } else {
                    foreach ($this->tabTabs as $number => $tab) {
                        $io->text('tab list ' . ($number + 1) . ': name = ' . $tab['name'] . '(' . $tab['class_name'] . ', ' . $tab['parent_class_name'] . ')');
                    }
                    $io->newLine();
                    $numMenu = $io->ask('Give the number of the tab to modify');
                    if (isset($this->tabTabs[$numMenu - 1])) {
                        $tabEle = $this->askTabElement($input, $output);
                        switch ($tabEle) {
                            case 'name':
                                $this->tabTabs[$number]['name'] = $io->ask('Give the new name of the tab');
                                break;
                            case 'class_name':
                                $this->tabTabs[$number]['class_name'] = $io->ask('Give the new class_name of the tab');
                                break;
                            case 'parent_class_name':
                                $this->tabTabs[$number]['parent_class_name'] = $io->ask('Give the new parent_class_name of the tab');
                                break;
                            case 'none':
                                break;
                        }
                        $io->newLine();
                    } else {
                        $io->text('There is no hook number ' . $numMenu - 1);
                        $io->newLine();
                    }
                }
                $this->menuTab($io, $input, $output);
                break;
            case 'Delete tab':
                if (sizeof($this->tabTabs) <= 0) {
                    $io->text('You have to add a tab before deleting one');
                    $io->newLine();
                } else {
                    foreach ($this->tabTabs as $number => $tab) {
                        $io->text('tab list ' . ($number + 1) . ' name = ' . $tab['name'] . '(' . $tab['class_name'] . ', ' . $tab['parent_class_name'] . ')');
                    }
                    $io->newLine();
                    $numMenu = $io->ask('Give the number of the tab to modify');
                    if (isset($this->tabTabs[$numMenu - 1])) {
                        unset($this->tabTabs[$numMenu - 1]);
                        $io->newLine();
                    } else {
                        $io->text('There is no tab number ' . $numMenu - 1);
                        $io->newLine();
                    }
                }
                $this->menuTab($io, $input, $output);
                break;
            case 'Display tabs':
                if (sizeof($this->tabTabs) <= 0) {
                    $io->text('There is no tab for the moment');
                    $io->newLine();
                } else {
                    foreach ($this->tabTabs as $number => $tab) {
                        $io->text('tab list ' . ($number + 1) . ': name = ' . $tab['name'] . '(' . $tab['class_name'] . ', ' . $tab['parent_class_name'] . ')');
                    }
                    $io->newLine();
                }
                $this->menuTab($io, $input, $output);
                break;
            case 'Leave':
                break;
            default:
                $io->text("That's not a correct number");
                $io->newLine();
                $this->menuTab($io, $input, $output);
                break;
        }
    }

    protected function askType($input, $output)
    {
        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Please select a type (defaults to int)',
            ['bit', 'tinyint', 'int',
                'decimal', 'numeric', 'float', 'real', 'Date',
                'Time', 'Datetime', 'Timestamp', 'Year', 'Char',
                'Varchar', 'Text', 'Binary', 'Varbinary', 'image', 'Blob', 'XML', 'JSON', ],
            2
        );
        $question->setErrorMessage('Type %s is invalid.');
        $type = $helper->ask($input, $output, $question);

        return $type;
    }

    protected function askSize($io, $type)
    {
        $types = ['Date', 'Time', 'Blob'];
        if (!in_array($type, $types)) {
            return $io->ask('Give a size to the field', '1');
        } else {
            return null;
        }
    }

    protected function askNullable($input, $output)
    {
        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Does the field is nullable ? (defaults to false)',
            ['yes', 'no'],
            1
        );
        $question->setErrorMessage('%s is invalid.');
        $null = $helper->ask($input, $output, $question);

        if ($null == 'yes') {
            return 'NULL';
        } else {
            return 'NOT NULL';
        }
    }

    protected function askConstraint($input, $output)
    {
        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Which constraint do you want to modify ? (defaults to none)',
            ['name', 'type', 'size', 'null', 'none'],
            4
        );
        $question->setErrorMessage('%s is invalid.');
        $constraint = $helper->ask($input, $output, $question);

        return $constraint;
    }

    protected function askTabElement($input, $output)
    {
        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Which one do you want to modify ? (defaults to none)',
            ['name', 'class_name', 'parant_class_name', 'none'],
            3
        );
        $question->setErrorMessage('%s is invalid.');
        $constraint = $helper->ask($input, $output, $question);

        return $constraint;
    }

    protected function launchModule($filename)
    {
        //dump
        $process = new Process('composer dump-autoload -d modules\\' . $filename);
        $process->run();
        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
        echo $process->getOutput();

        //install
        $process->setCommandLine('php bin/console prestashop:module install ' . $filename);
        $process->run();
        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
        echo $process->getOutput();
    }
}

