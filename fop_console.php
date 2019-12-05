<?php
/**
 * 2019-present Friends of Presta community
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License
 * that is bundled with this package in the file LICENSE
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/MIT
 *
 * @author Friends of Presta community
 * @copyright 2019-present Friends of Presta community
 * @license https://opensource.org/licenses/MIT MIT
 */
require_once 'vendor/autoload.php';

class Fop_Console extends Module
{
    public function __construct()
    {
        $this->name = 'fop_console';
        $this->version = '1.0.0';
        $this->author = 'Friends of Presta';

        parent::__construct();

        $this->displayName = 'FoP Console';
        $this->description = $this->l('Set of command lines to perform daily or heavy tasks.');
        $this->ps_versions_compliancy = [
            'min' => '1.7.5.0',
            'max' => _PS_VERSION_,
        ];
    }
}
