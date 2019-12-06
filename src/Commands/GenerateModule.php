<?php

namespace FOP\Console\Commands;

use FOP\Console\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * This command is a working exemple.
 */
final class GenerateModule extends Command
{
    protected static $tabHook=array();
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('fop:console:generate-module')
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

        $io->text(sprintf("\033\143"));

        $io->title('This program was carried out by FriendsOfPresta and OHweb');
        $io->note("It work better on unix system");
        $io->warning('Be careful if you run this program it may erase data, be sure to specify an unused module name or you know what you are doing');
        $confirm = $io->ask('Would like to continue ? (yes/no)', 'no');
        while ($confirm != "yes" && $confirm != "no") {
            $confirm = $io->ask('Please answer with "yes" or no "no"', 'no');
        }
        if ($confirm == "no") {
            $io->text("End of the program ...");
            return;
        }

        $io->text(sprintf("\033\143"));
        $io->section('Edition of the entry point');

        $name = $io->ask('Give a name to your module', 'fop_examplemodule');
        $name = str_replace(' ', '_', $name);
        $fileName = strtolower($name);
        $filenameFirstletterCaps = ucfirst($name);
        $filenameCamel = ucwords(strtolower($name));
        $description = $io->ask("Give the description of the module");
        $author = $io->ask("Give the author of the module", 'OHweb');

        $io->text(sprintf("\033\143"));
        $io->section('File generation');

        try {
            $filesystem->mkdir(_PS_MODULE_DIR_."".$fileName, 0755);
            $filesystem->mkdir(_PS_MODULE_DIR_."/".$fileName."/config", 0755);
            $filesystem->touch(_PS_MODULE_DIR_."/".$fileName."/config/services.yml");
            $filesystem->touch(_PS_MODULE_DIR_."/".$fileName."/config/routes.yml");
            $filesystem->mkdir(_PS_MODULE_DIR_."/".$fileName."/controller/admin", 0755);
            $filesystem->mkdir(_PS_MODULE_DIR_."/".$fileName."/controller/front", 0755);
            $filesystem->mkdir(_PS_MODULE_DIR_."/".$fileName."/src/cache", 0755);
            $filesystem->mkdir(_PS_MODULE_DIR_."/".$fileName."/src/Controller/Admin/Improve/Design", 0755);
            $filesystem->mkdir(_PS_MODULE_DIR_."/".$fileName."/src/Core/Grid/Definition/Factory", 0755);
            $filesystem->mkdir(_PS_MODULE_DIR_."/".$fileName."/src/Core/Grid/Query", 0755);
            $filesystem->mkdir(_PS_MODULE_DIR_."/".$fileName."/src/Core/Search/Filters", 0755);
            $filesystem->mkdir(_PS_MODULE_DIR_."/".$fileName."/src/Form/ChoiceProvider", 0755);
            $filesystem->mkdir(_PS_MODULE_DIR_."/".$fileName."/src/Form/Type", 0755);
            $filesystem->mkdir(_PS_MODULE_DIR_."/".$fileName."/src/img/banner", 0755);
            $filesystem->mkdir(_PS_MODULE_DIR_."/".$fileName."/src/img/gerant", 0755);
            $filesystem->mkdir(_PS_MODULE_DIR_."/".$fileName."/src/img/slider", 0755);
            $filesystem->mkdir(_PS_MODULE_DIR_."/".$fileName."/src/Model", 0755);
            $filesystem->mkdir(_PS_MODULE_DIR_."/".$fileName."/src/Presenter", 0755);
            $filesystem->mkdir(_PS_MODULE_DIR_."/".$fileName."/src/Repository", 0755);
            $filesystem->mkdir(_PS_MODULE_DIR_."/".$fileName."/views/js/form", 0755);
            $filesystem->mkdir(_PS_MODULE_DIR_."/".$fileName."/views/js/grid", 0755);
            $filesystem->mkdir(_PS_MODULE_DIR_."/".$fileName."/views/public", 0755);
            $filesystem->mkdir(_PS_MODULE_DIR_."/".$fileName."/views/templates/admin", 0755);
            $filesystem->mkdir(_PS_MODULE_DIR_."/".$fileName."/views/templates/hook", 0755);
            $rows = 24;
            $progressBar = new ProgressBar($output, $rows);
            $progressBar->setFormat(
                "<fg=white;bg=cyan> %status:-45s%</>\n%current%/%max% [%bar%] %percent:3s%%"
            );
            $progressBar->setBarCharacter('<fg=magenta>=</>');
            $progressBar->setProgressCharacter("<fg=green>➤</>");
            $progressBar->start();
            for ($i = 0; $i<$rows; $i++) {
                if ($i < 2) {
                    $progressBar->setMessage("Starting...", 'status');
                } elseif ($i < 22) {
                    $progressBar->setMessage("In progress...", 'status');
                } else {
                    $progressBar->setMessage("Almost finished...", 'status');
                }
                $progressBar->advance();
                usleep(20000);
            }
            $progressBar->finish();
            $io->newLine(2);
            $io->success('Generation finished');
        } catch (IOExceptionInterface $exception) {
            $io->error('An error as occurred in the directory path at '.$exception->getPath());
        }

        $io->text(sprintf("\033\143"));
        $io->section('Menu for generating hooks');
        $this->menuHook($io);

        $hookStringInstall="";
        foreach (self::$tabHook as $hookname) {
            $hookStringInstall .= "
            && \$this->registerHook('".$hookname."')";
        }
        $hookStringFunction="";
        foreach (self::$tabHook as $hookfunction) {
            $hookfunction = ucfirst($hookfunction);
            $hookStringFunction .= "
    public function hook".$hookfunction."(\$params) {
        //TODO
    }
    ";
        }

        $stringEntryPoint = "";
        $stringEntryPoint.= "<?php
if (!defined('_CAN_LOAD_FILES_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

/**
* Class ".$filenameFirstletterCaps."
*/

class ".$filenameFirstletterCaps." extends Module implements WidgetInterface
{

    const MODULE_NAME = '".$fileName."';

    public \$templateFile;

    public function __construct()
    {
        \$this->name = '".$filenameCamel."';
        \$this->author = '".$author."';
        \$this->version = '1.0.0';
        \$this->need_instance = 0;
        \$this->bootstrap = true;
        parent::__construct();

        \$this->displayName = '".$filenameCamel."';
        \$this->description = '".$description."';
        \$this->secure_key = Tools::encrypt(\$this->name);

        \$this->ps_versions_compliancy = array('min' => '1.7.5.0', 'max' => _PS_VERSION_);
        \$this->templateFile = 'module:".$filenameCamel."/views/templates/hook/';

        \$this->tabs = [
        ];
    }

    public function install()
    {
        return parent::install()".$hookStringInstall."
            && \$this->installTab();
    }

    public function uninstall()
    {
        return parent::uninstall()
            && \$this->uninstallTab();
    }

    public function installTab()
    {
        //TODO
    }

    public function uninstallTab()
    {
        //TODO
    }

    public function enable(\$force_all = false)
    {
        return parent::enable(\$force_all)
            && \$this->installTab();
    }

    public function disable(\$force_all = false)
    {
        return parent::disable(\$force_all)
            && \$this->uninstallTab();
    }
    ".$hookStringFunction."
    public function renderWidget(\$hookName, array \$configuration)
    {
        //TODO
    }

    public function getWidgetVariables(\$hookName, array \$configuration)
    {
        //TODO
    }
}";
        $this->whriteCode($filesystem, $fileName, $stringEntryPoint);

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

    protected function whriteCode($filesystem, $fileName, $string)
    {
        $filesystem->dumpFile(_PS_MODULE_DIR_."/".$fileName."/".$fileName.".php", $string);
    }

    protected function menuHook($io)
    {
        $io->listing([
            '1. Add a hook',
            '2. Modify a hook',
            '3. Delete a hook',
            '4. Display the list of hooks',
            '5. Leave',
        ]);
        $numMenu = $io->ask('Enter a number');

        switch ($numMenu) {
            case 1:
                self::$tabHook[] = $io->ask('Give the name of the hook', 'displayAfterFooter');
                $io->text("Hook created");
                $io->newLine();
                $this->menuHook($io);
                break;
            case 2:
                if (sizeof(self::$tabHook) <= 0) {
                    $io->text("You have to add a hook to modify one");
                    $io->newLine();
                } else {
                    foreach (self::$tabHook as $number=>$hookname) {
                        $io->text($number.'. '.$hookname);
                    }
                    $io->newLine();
                    $numMenu = $io->ask("Give the number of the hook to modify");
                    if (isset(self::$tabHook[$numMenu])) {
                        self::$tabHook[$numMenu] = $io->ask('Give the new name of the hook', self::$tabHook[$numMenu]);
                        $io->text("Hook modified");
                        $io->newLine();
                    } else {
                        $io->text("There is no hook number ".$numMenu);
                        $io->newLine();
                    }
                }
                $this->menuHook($io);
                break;
            case 3:
                if (sizeof(self::$tabHook) <= 0) {
                    $io->text("You have to add a hook to delete one");
                    $io->newLine();
                } else {
                    foreach (self::$tabHook as $number=>$hookname) {
                        $io->text($number.'. '.$hookname);
                    }
                    $io->newLine();
                    $numMenu = $io->ask("Give the number of the hook to delete");
                    if (isset(self::$tabHook[$numMenu])) {
                        unset(self::$tabHook[$numMenu]);
                        $io->text("Hook deleted");
                        $io->newLine();
                    } else {
                        $io->text("There is no hook number ".$numMenu);
                        $io->newLine();
                    }
                }
                $this->menuHook($io);
                break;
            case 4:
                if (sizeof(self::$tabHook) <= 0) {
                    $io->text("There is no Hook for the moment");
                    $io->newLine();
                } else {
                    foreach (self::$tabHook as $number=>$hookname) {
                        $io->text($number.'. '.$hookname);
                    }
                    $io->newLine();
                }
                $this->menuHook($io);
                break;
            case 5:
                break;
            default:
                $io->text("That's not a correct number");
                $io->newLine();
                $this->menuHook($io);
                break;
        }
    }
}