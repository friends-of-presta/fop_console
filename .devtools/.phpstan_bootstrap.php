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

declare(strict_types=1);

define('DEFAULT_PATH_TO_PRESTASHOP', '/path/to/prestashop/');
define('FOP_PHPSTAN_PS_ROOT_CONFIG_FILE', __DIR__ . '/.phpstan_bootstrap_config.php');

try {
    getenv('_PS_ROOT_DIR_') || include_env_configuration() || create_default_config_file_and_exit();
    abort_on_incorrect_configuration();

    echo 'Phpstan configuration : _PS_ROOT_DIR_ successfully set to ' . getenv('_PS_ROOT_DIR_');
} catch (RuntimeException $exception) {
    echo PHP_EOL . ' Phpstan configuration problem : ' . $exception->getMessage();
    exit(1);
}

// ----------- end of script  -------------

function include_env_configuration(): bool
{
    if (file_exists(FOP_PHPSTAN_PS_ROOT_CONFIG_FILE)) {
        require FOP_PHPSTAN_PS_ROOT_CONFIG_FILE;

        return true;
    }

    return false;
}

function create_default_config_file_and_exit(): void
{
    if (!getenv('_PS_ROOT_DIR_')) {
        echo 'Environment variable _PS_ROOT_DIR_ not set.' . PHP_EOL;

        // check if config file exists and create a default one if needed.
        if (!file_exists(FOP_PHPSTAN_PS_ROOT_CONFIG_FILE)) {
            file_put_contents(FOP_PHPSTAN_PS_ROOT_CONFIG_FILE,
                '<?php' . PHP_EOL .
                 '// replace ' . DEFAULT_PATH_TO_PRESTASHOP . ' with a local path.' . PHP_EOL .
                'putenv(\'_PS_ROOT_DIR_=' . DEFAULT_PATH_TO_PRESTASHOP . '\');');

            throw new RuntimeException(PHP_EOL . PHP_EOL . '    Missing phpstan configuration file.' . PHP_EOL . '    Default file created. Edit file "' . FOP_PHPSTAN_PS_ROOT_CONFIG_FILE . '"' . PHP_EOL . PHP_EOL);
        }

        throw new RuntimeException(PHP_EOL . '     Configuration file was found but _PS_ROOT_DIR_ is still not set.' . PHP_EOL . '     Edit or remove FOP_PHPSTAN_PS_ROOT_CONFIG_FILE to define a _PS_ROOT_DIR_ (using putenv()).');
    }
}

function abort_on_incorrect_configuration(): void
{
    // envirornnemt var not set
    if (false === getenv('_PS_ROOT_DIR_')) {
        throw new RuntimeException(PHP_EOL . PHP_EOL . '     PS_ROOT_DIR_ not set on "' . FOP_PHPSTAN_PS_ROOT_CONFIG_FILE . PHP_EOL . '     Correct this file or delete it. ');
    }

    // default path was left
    if (DEFAULT_PATH_TO_PRESTASHOP === getenv('_PS_ROOT_DIR_')) {
        throw new RuntimeException(PHP_EOL . PHP_EOL . '     You must configure the path to Prestashop in "' . FOP_PHPSTAN_PS_ROOT_CONFIG_FILE . PHP_EOL . '     You left the default value.' . PHP_EOL);
    }

    // abort if _PS_ROOT_DIR_ is not a valid path
    if (!file_exists(getenv('_PS_ROOT_DIR_'))) {
        throw new RuntimeException(PHP_EOL . PHP_EOL . '     Invalid path to prestashop. Provided : ' . getenv('_PS_ROOT_DIR_') . PHP_EOL . ' Edit "' . FOP_PHPSTAN_PS_ROOT_CONFIG_FILE . '" and modify _PS_ROOT_DIR_ ');
    }
}
