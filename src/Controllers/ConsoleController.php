<?php

namespace FOP\Console\Controllers;

use Controller;

/**
 * Controller used in Console environment.
 */
class ConsoleController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->id = 0;
        $this->controller_type = 'console';
    }
    /**
     * {@inheritdoc}
     */
    public function checkAccess()
    {
        // TODO: Implement checkAccess() method.
    }
    /**
     * {@inheritdoc}
     */
    public function viewAccess()
    {
        // TODO: Implement viewAccess() method.
    }
    /**
     * {@inheritdoc}
     */
    public function postProcess()
    {
        // TODO: Implement postProcess() method.
    }
    /**
     * {@inheritdoc}
     */
    public function display()
    {
        return '';
    }
    /**
     * {@inheritdoc}
     */
    public function setMedia()
    {
        return null;
    }
    /**
     * {@inheritdoc}
     */
    public function initHeader()
    {
        return '';
    }
    /**
     * {@inheritdoc}
     */
    public function initContent()
    {
        return '';
    }
    /**
     * {@inheritdoc}
     */
    public function initCursedPage()
    {
        return '';
    }
    /**
     * {@inheritdoc}
     */
    public function initFooter()
    {
        return '';
    }
    /**
     * {@inheritdoc}
     */
    protected function redirect()
    {
        return '';
    }
    /**
     * {@inheritdoc}
     */
    protected function buildContainer()
    {
        // @todo: Should we return the back office container here ?
        return null;
    }
}
