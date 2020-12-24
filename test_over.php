<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/../../config/config.inc.php';

$o = new \FOP\Console\Overriders\ModuleTemplateOverrider();
$o->init('modules/ps_linklist/views/templates/hook/linkblock.tpl');

chdir(__DIR__ . '/../../');

if (!$o->handle()) {
    echo 'file not handled';
    exit('not handled');
}

echo 'handle';
dump($o->getDangerousConsequences());
$messages = $o->run();
dump($messages);
exit();
