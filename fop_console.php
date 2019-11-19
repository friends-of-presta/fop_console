<?php
/**
 * @author Friends of Presta community
 *
 * This module explore Symfony commands the right way.
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
