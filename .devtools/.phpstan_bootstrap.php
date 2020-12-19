<?php
/**
 * Handle _PS_ROOT_DIR_ environment variable configuration.
 *
 * 1. Set it if needed
 *
 * It could be set before.
 * If it is not, it can be set in __DIR__ . '/.phpstan_bootstrap_config.php'
 *
 * 2. Check it
 */

// -- 1. Set _PS_ROOT_DIR_ if needed

$ps_root_env_file_path = __DIR__ . '/.phpstan_bootstrap_config.php';
if (!getenv('_PS_ROOT_DIR_')) {
    // check if config file exists and create a default one if needed.
    if (!file_exists($ps_root_env_file_path)) {
        file_put_contents($ps_root_env_file_path,
            '<?php' . PHP_EOL . 'putenv(\'_PS_ROOT_DIR_=/path/to/prestashop/\');');
        throw new Exception(PHP_EOL . PHP_EOL . ">>> Missing phpstan configuration file." . PHP_EOL . "Default file created. Edit it : \"$ps_root_env_file_path\"");
    }

    require($ps_root_env_file_path); // _PS_ROOT_DIR_ is set in this file
}

// 2. Check _PS_ROOT_DIR_

// abort if _PS_ROOT_DIR_ is not really defined
if ('/path/to/prestashop/' === getenv('_PS_ROOT_DIR_')) {
    throw new Exception(PHP_EOL . PHP_EOL . ">>> You MUST configure the path to Prestashop in \"$ps_root_env_file_path\"" . PHP_EOL . PHP_EOL);
}

// abort if _PS_ROOT_DIR_ is not a valid path
if (!file_exists(getenv('_PS_ROOT_DIR_'))) {
    throw new Exception(PHP_EOL . PHP_EOL . ">>> invalid path to prestashop. Edit \"$ps_root_env_file_path\"");
}

// if execution passes here, everything is fine
