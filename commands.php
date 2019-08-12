<?php
/**
 * @author Mickaël Andrieu <andrieu.travail@gmail.com>
 *
 * This module explore Symfony commands the right way.
 */

require_once 'vendor/autoload.php';

class Commands extends Module
{
    public function __construct()
    {
        $this->name = 'commands';
        $this->version = '1.0.0';
        $this->author = 'Mickaël Andrieu';

        $this->displayName = 'Commands';
        $this->description = 'Learn commands the right way';
        $this->ps_versions_compliancy = [
            'min' => '1.7.6.0',
            'max' => _PS_VERSION_,
        ];

        parent::__construct();
    }
}
