<?php
/**
 * @author Mickaël Andrieu <andrieu.travail@gmail.com>
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
        $this->author = 'Mickaël Andrieu';

        $this->displayName = 'FoP Console';
        $this->description = 'A better console support for PrestaShop 1.7';
        $this->ps_versions_compliancy = [
            'min' => '1.7.5.0',
            'max' => _PS_VERSION_,
        ];

        parent::__construct();
    }
}
