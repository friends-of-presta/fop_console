<?php

namespace FOP\Console\Builder;

/**
 * 20019-20120 FriendsOfPresta
 *
 * NOTICE OF LICENSE
 *
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please contact friends of presta
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.prestashop.com for more information.
 *
 * @author    FriendsOfPresta https://github.com/friends-of-presta/who-we-are
 * @copyright 2019-2020 FriendsOfPresta
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of FriendsOfPresta
 */

class ModuleBuilder
{

    /*
    * This function build the string to put in the php file
    */
    public static function getInstallString($tabHook)
    {
        $hookStringInstall = "";
        foreach ($tabHook as $hookname) {
            $hookStringInstall .= "
            && \$this->registerHook('".$hookname."')";
        }
        return $hookStringInstall;
    }

    /*
     * This function build the string to put in the php file
     */
    public static function getFunctionString($tabHook)
    {
        $hookStringFunction = "";
        foreach ($tabHook as $hookfunction) {
            $hookfunction = ucfirst($hookfunction);
            $hookStringFunction .= "
    public function hook".$hookfunction."(\$params) {
        //TODO
    }
    ";
        }
        return $hookStringFunction;
    }

    /*
     * This function build the string to put in the php file
     */
    public static function getEntryPointString($filenameFirstletterCaps, $fileName, $filenameCamel, $author, $displayName,
                                               $description, $hookStringInstall, $hookStringFunction, $filesystem){
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

        \$this->displayName = '".$displayName."';
        \$this->description = '".$description."';
        \$this->secure_key = Tools::encrypt(\$this->name);

        \$this->ps_versions_compliancy = array('min' => '1.7.5.0', 'max' => _PS_VERSION_);
        \$this->templateFile = 'module:".$fileName."/views/templates/hook/';

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
        Self::dumpModulePHPFile($filesystem,$fileName,$stringEntryPoint);
    }

    /*
     * Write the necessary code in a PHP file
     */
    private static function dumpModulePHPFile($filesystem, $fileName, $string)
    {
        $filesystem->dumpFile(_PS_MODULE_DIR_."/".$fileName."/".$fileName.".php", $string);
    }

}