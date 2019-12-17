<?php

namespace FOP\Console\Commands;

use FOP\Console\Command;
use FOP\Console\Builder\ModuleBuilder;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * This command is a working exemple.
 */
final class GenerateModule extends Command
{
    private $tabHook=array();
    private $hookStringInstall="";
    private $hookStringFunction="";
    
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
        if ($confirm == "No") {
            $io->text("End of the program ...");
            return;
        }

        $io->newLine(1);
        $io->section('Edition of the entry point');
        $name = $io->ask('Give a name to your module', 'fop_examplemodule');
        $displayName = $io->ask('Give a display name to your module', 'FOP Exemple Module');
        $fileName = strtolower($name);
        $filenameFirstletterCaps = ucfirst($name);
        $filenameCamel = ucwords(strtolower($name));
        $description = $io->ask("Give the description of the module");
        $author = $io->ask("Give the author of the module", 'FOP');

        $io->section('File generation');

        $fileDirectories = array();
        try {
            $fileDirectories["dir"][] = _PS_MODULE_DIR_."".$fileName;
            $fileDirectories["dir"][] = _PS_MODULE_DIR_."/".$fileName."/config";
            $fileDirectories["dir"][] = _PS_MODULE_DIR_."/".$fileName."/controller/admin";
            $fileDirectories["dir"][] = _PS_MODULE_DIR_."/".$fileName."/controller/front";
            $fileDirectories["dir"][] = _PS_MODULE_DIR_."/".$fileName."/src/cache";
            $fileDirectories["dir"][] = _PS_MODULE_DIR_."/".$fileName."/src/Controller/Admin/Improve/Design";
            $fileDirectories["dir"][] = _PS_MODULE_DIR_."/".$fileName."/src/Core/Grid/Definition/Factory";
            $fileDirectories["dir"][] = _PS_MODULE_DIR_."/".$fileName."/src/Core/Grid/Query";
            $fileDirectories["dir"][] = _PS_MODULE_DIR_."/".$fileName."/src/Core/Search/Filters";
            $fileDirectories["dir"][] = _PS_MODULE_DIR_."/".$fileName."/src/Form/ChoiceProvider";
            $fileDirectories["dir"][] = _PS_MODULE_DIR_."/".$fileName."/src/Form/Type";
            $fileDirectories["dir"][] = _PS_MODULE_DIR_."/".$fileName."/src/img/banner";
            $fileDirectories["dir"][] = _PS_MODULE_DIR_."/".$fileName."/src/img/gerant";
            $fileDirectories["dir"][] = _PS_MODULE_DIR_."/".$fileName."/src/img/slider";
            $fileDirectories["dir"][] = _PS_MODULE_DIR_."/".$fileName."/src/Model";
            $fileDirectories["dir"][] = _PS_MODULE_DIR_."/".$fileName."/src/Presenter";
            $fileDirectories["dir"][] = _PS_MODULE_DIR_."/".$fileName."/src/Repository";
            $fileDirectories["dir"][] = _PS_MODULE_DIR_."/".$fileName."/views/js/form";
            $fileDirectories["dir"][] = _PS_MODULE_DIR_."/".$fileName."/views/js/grid";
            $fileDirectories["dir"][] = _PS_MODULE_DIR_."/".$fileName."/views/public";
            $fileDirectories["dir"][] = _PS_MODULE_DIR_."/".$fileName."/views/templates/admin";
            $fileDirectories["dir"][] = _PS_MODULE_DIR_."/".$fileName."/views/templates/hook";
            $fileDirectories["file"][] = _PS_MODULE_DIR_."/".$fileName."/config/services.yml";
            $fileDirectories["file"][] = _PS_MODULE_DIR_."/".$fileName."/config/routes.yml";
            $fileDirectories["file"][] = _PS_MODULE_DIR_."/".$fileName."/".$fileName.".php";
            $rows = (count($fileDirectories["dir"])+count($fileDirectories["file"]));
            $progressBar = new ProgressBar($output, $rows);
            $progressBar->setFormat(
                "<fg=white;bg=cyan> %status:-45s%</>\n%current%/%max% [%bar%] %percent:3s%%"
            );
            $progressBar->setBarCharacter('<fg=magenta>=</>');
            $progressBar->setProgressCharacter("<fg=green>➤</>");
            $progressBar->start();
            $i = 0;
            foreach ($fileDirectories["dir"] as $directory) {
                $filesystem->mkdir($directory, 0755);
                if ($i = 0) {
                    $progressBar->setMessage("Starting...", 'status');
                } else {
                    $progressBar->setMessage("In progress...", 'status');
                }

                $progressBar->advance();
                $i++;
            }
            foreach ($fileDirectories["file"] as $file) {
                $filesystem->touch($file);
                if ($i < $rows-1) {
                    $progressBar->setMessage("Almost finished...", 'status');
                } else {
                    $progressBar->setMessage("In progress...", 'status');
                }

                $progressBar->advance();
                $i++;
            }

            $progressBar->finish();
            $io->newLine(2);
            $io->success('Generation finished');
        } catch (IOExceptionInterface $exception) {
            $io->error('An error as occurred in the directory path at '.$exception->getPath());
        }

        $io->section('Menu for generating hooks');
        $this->menuHook($io,$input,$output);

        $this->hookStringInstall = Modulebuilder::getInstallString($this->tabHook);
        $this->hookStringFunction = ModuleBuilder::getFunctionString($this->tabHook);

        ModuleBuilder::getEntryPointString($filenameFirstletterCaps,$fileName,$filenameCamel,
            $author,$displayName,$description,$this->hookStringInstall,
            $this->hookStringFunction,$filesystem);

//        $io->text("table 1");
//        $io->table(
//            ['Nom', 'Type', 'Null'],
//            [
//                ['id_truc', 'int(11)', 'Y'],
//                ['date_bidule', 'datetime', 'Y'],
//                ['machin', 'varchar(20)', 'Y'],
//            ]
//        );
    }

    /*
     * This function ask the user which hook he want add to the module and build the code consequently
     */
    protected function menuHook($io,$input,$output)
    {
        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Please select a menu entry (defaults to 4)',
            ['Add a hook', 'Modify a hook', 'Delete a hook', 'Display the list of hooks', 'Leave'],
            4
        );
        $question->setErrorMessage('Color %s is invalid.');
        $color = $helper->ask($input, $output, $question);

        switch ($color) {
            case 'Add a hook':
                $this->tabHook[] = $io->ask('Give the name of the hook', 'displayAfterFooter');
                $io->text("Hook created");
                $io->newLine();
                $this->menuHook($io,$input,$output);
                break;
            case 'Modify a hook':
                if (sizeof($this->tabHook) <= 0) {
                    $io->text("You have to add a hook to modify one");
                    $io->newLine();
                } else {
                    foreach ($this->tabHook as $number=>$hookname) {
                        $io->text($number.'. '.$hookname);
                    }
                    $io->newLine();
                    $numMenu = $io->ask("Give the number of the hook to modify");
                    if (isset($this->tabHook[$numMenu])) {
                        $this->tabHook[$numMenu] = $io->ask('Give the new name of the hook', $this->tabHook[$numMenu]);
                        $io->text("Hook modified");
                        $io->newLine();
                    } else {
                        $io->text("There is no hook number ".$numMenu);
                        $io->newLine();
                    }
                }
                $this->menuHook($io,$input,$output);
                break;
            case 'Delete a hook':
                if (sizeof($this->tabHook) <= 0) {
                    $io->text("You have to add a hook to delete one");
                    $io->newLine();
                } else {
                    foreach ($this->tabHook as $number=>$hookname) {
                        $io->text($number.'. '.$hookname);
                    }
                    $io->newLine();
                    $numMenu = $io->ask("Give the number of the hook to delete");
                    if (isset($this->tabHook[$numMenu])) {
                        unset($this->tabHook[$numMenu]);
                        $io->text("Hook deleted");
                        $io->newLine();
                    } else {
                        $io->text("There is no hook number ".$numMenu);
                        $io->newLine();
                    }
                }
                $this->menuHook($io,$input,$output);
                break;
            case 'Display the list of hooks':
                if (sizeof($this->tabHook) <= 0) {
                    $io->text("There is no Hook for the moment");
                    $io->newLine();
                } else {
                    foreach ($this->tabHook as $number=>$hookname) {
                        $io->text($number.'. '.$hookname);
                    }
                    $io->newLine();
                }
                $this->menuHook($io,$input,$output);
                break;
            case 'Leave':
                break;
            default:
                $io->text("That's not a correct number");
                $io->newLine();
                $this->menuHook($io,$input,$output);
                break;
        }
    }
}

