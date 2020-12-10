<?php

$filename = __DIR__.'/.phpstan_bootstrap_config.php';
// check if config file exists and create a default one if needed.
if(!file_exists($filename)) {
    file_put_contents($filename,
        '<?php'.PHP_EOL.'putenv(\'_PS_ROOT_DIR_=/path/to/prestashop/\');');
    throw new \Exception(PHP_EOL.PHP_EOL.">>> Missing phpstan configuration file.".PHP_EOL."Default file created. Edit it : \"$filename\"");
}

require($filename);

// test if the value is correct
$ps_root_dir = getenv('_PS_ROOT_DIR_');

// still the default
if('/path/to/prestashop/' === $ps_root_dir) {
    throw new \Exception(PHP_EOL.PHP_EOL.">>> You MUST configure the path to Prestashop in \"$filename\"".PHP_EOL.PHP_EOL);
}

// not a valid path
if(!file_exists($ps_root_dir)) {
    throw new \Exception(PHP_EOL.PHP_EOL.">>> invalid path to prestashop. Edit \"$filename\"");
}

// everything is fine
