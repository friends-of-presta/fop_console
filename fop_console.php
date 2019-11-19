<?php
/**
 * @author Friends of Presta community
 *
 * This module explore Symfony commands the right way.
 */

require_once 'vendor/autoload.php';

class Console extends Module
{
    public function __construct()
    {
        $this->name = 'fop_console';
        $this->version = '1.0.0';
        $this->author = 'Friends of Presta';

        $this->displayName = 'FoP Console';
        $this->description = 'Set of command lines to perform daily or heavy tasks.';
        $this->ps_versions_compliancy = [
            'min' => '1.7.5.0',
            'max' => _PS_VERSION_,
        ];

        parent::__construct();
    }
}
