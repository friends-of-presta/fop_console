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
    * This function build a string to put in the php file
    */
    public static function getInstallString($tabHook){
        $hookStringInstall = "";
        foreach ($tabHook as $hookname) {
            $hookStringInstall .= "
            && \$this->registerHook('".$hookname."')";
        }
        return $hookStringInstall;
    }

    /*
     * This function build a string to put in the php file
     */
    public static function getFunctionString($tabHook){
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
     * This function build a string to put in the php file
     */
    public static function getInstallSqlTableString(){
        $sqlTableStringInstall="&& \$this->createTableSql()";
        return $sqlTableStringInstall;
    }

    /*
     * This function build a string to put in the php file
     */
    public static function getUninstallSqlTableString(){
        $sqlTableStringUninstall="&& \$this->dropTableSql()";
        return $sqlTableStringUninstall;
    }

    /*
     * This function build a string to put in the php file
     */
    public static function getFunctionSqlTableString($tabSql){
        //building of the createTable function
        $sqlTableStringFunction = "";
        $sqlTableStringFunction=$sqlTableStringFunction."
    /**
    * Table generated thanks to FOP
    */
    protected function createTableSql(){
        \$sql = array();
        ";
        foreach($tabSql as $table) {
            $sqlChampsString="";
            $sqlChampsString=$sqlChampsString
                ."`".$table['pk_constraint']."` int(11) NOT NULL AUTO_INCREMENT,";
            foreach ($table as $number => $field) {
                if (is_array($field)) {
	                $types = array("Date", "Time", "Blob");
	                if (in_array($field['type'], $types)) {
	                	$size = " ";
	                }else{
		                $size = "(".$field['size'].") ";
	                }
                    $sqlChampsString=$sqlChampsString."
                `".$field['name']."` ".$field['type']."".$size."".$field['null'].",";
                }
            }
            $sqlTableStringFunction=$sqlTableStringFunction."
        \$sql[] = '
            CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'".$table['tab_name']."` (
                ".$sqlChampsString."
                PRIMARY KEY (`".$table['pk_constraint']."`)
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=UTF8;';
            ";
        }
        $sqlTableStringFunction=$sqlTableStringFunction."
        foreach (\$sql as \$query) {
            if (Db::getInstance()->execute(\$query) == false) {
                return false;
            }
        }
    }
        ";

        //building of the dropTable Function
        $sqlTableStringFunction=$sqlTableStringFunction."
    /**
     * Table generated thanks to FOP
     */
    protected function dropTableSql(){
        \$sql = array();";
        foreach($tabSql as $table) {
            $sqlTableStringFunction=$sqlTableStringFunction."
        \$sql[] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'".$table['tab_name']."`';";
        }
        $sqlTableStringFunction=$sqlTableStringFunction."
        foreach (\$sql as \$query) {
            if (Db::getInstance()->execute(\$query) == false) {
                return false;
            }
        }
    }";

        return $sqlTableStringFunction;
    }

	public static function getInstallTabString($tabtabs){
		$tabStringInstall="\$this->tabs = [";
    	foreach($tabtabs as $tab){
		    $tabStringInstall=$tabStringInstall."
		    [
                'name' => '".$tab['name']."',
                'visible' => true,
                'class_name' => '".$tab['class_name']."',
                'parent_class_name' => '".$tab['parent_class_name']."',
            ],";
        }
		$tabStringInstall=$tabStringInstall."
		];";
    	
    	return $tabStringInstall;
	}

    /*
     * This function build a string to put in the php file
     */
    public static function getEntryPointString($filenameFirstletterCaps, $fileName, $filenameCamel, $author, $displayName,
                                               $description, $hookStringInstall, $hookStringFunction, $sqlTableStringInstall,
                                               $sqlTableStringUninstall, $sqlTableStringFunction, $tabStringInstall, $filesystem){
        $stringEntryPoint = "<?php
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

        ".$tabStringInstall."
    }

    public function install()
    {
        return parent::install()".$hookStringInstall."
            ".$sqlTableStringInstall."
            && \$this->installTab();
    }

    public function uninstall()
    {
        return parent::uninstall()
            ".$sqlTableStringUninstall."
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
    ".$sqlTableStringFunction."
}";
	    $filesystem->dumpFile(_PS_MODULE_DIR_."/".$fileName."/".$fileName.".php", $stringEntryPoint);
    }
	
	/*
	 * This function build a string to put in the json file
	 */
	public static function getJsonComposerString($fileName, $description, $author, $mailContact, $filesystem){
		$stringJsonComposer ='{
	"name": "prestashop/'.$fileName.'",
	"description": "'.$description.'",
	"version": "1.0.0",
	"authors": [
		{
            "name": "'.$author.'",
            "email": "'.$mailContact.'"
        }
	],
	"autoload": {
		"psr-4": {
            "PrestaShop\\\\Module\\\\'.$fileName.'\\\\": "src/"
		}
	},
	"require": {
		"php": "^7.1"
    },
    "license": "MIT",
	"config": {
		"preferred-install": "dist"
	},
	"type": "prestashop-module"
}';
		$filesystem->dumpFile(_PS_MODULE_DIR_."/".$fileName."/composer.json", $stringJsonComposer);
	}
}