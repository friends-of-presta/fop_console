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

$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

class Fop_Console extends Module
{
    public function __construct()
    {
        $this->name = 'fop_console';
        $this->version = '1.5.0';
        $this->author = 'Friends of Presta';

        parent::__construct();

        $this->displayName = 'FoP Console';
        $this->description = $this->l('Set of command lines to perform daily or heavy tasks.');
        $this->ps_versions_compliancy = [
            'min' => '1.7.5.0',
            'max' => _PS_VERSION_,
        ];
    }

    public function install()
    {
        if (PHP_VERSION_ID < 70200) {
            $this->_errors[] = $this->l('fop_console require at least php version 7.2.');

            return false;
        }

        return parent::install();
    }
}
