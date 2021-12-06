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

namespace FOP\Console\Commands\Environment;

use Configuration;
use FOP\Console\Command;
use PrestaShop\PrestaShop\Adapter\Debug\DebugMode;
use PrestaShop\PrestaShop\Core\Crypto\Hashing;
use ShopUrl;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class EnvironmentSetupDev extends Command
{
    /**
     * @var mixed
     */
    protected $helper;

    /**
     * @var \Db
     */
    protected $dbi;

    /**
     * @var Hashing
     */
    protected $crypto;

    protected $isMultiShop;

    protected function configure(): void
    {
        $this->setName('fop:environment:setup-dev')
            ->setAliases(['fop:dev:setup-env'])
            ->setDescription('Install your project for local developement')
            ->setHelp(
                '<info>This command update database configuration with dev parameters (url, ssl, passwords). </info>' . PHP_EOL .
                        '<info>How to use : </info>' . PHP_EOL .
                        '<info>php bin/console fop:dev:setup-env --host=localhost --purl="/pro/clients/jojo/"  --modify-employee-pwd=1 --modify-customer-pwd=1 --employee-pwd=fopisnice --customer-pwd=fopisnice --ssl=0</info>'
            );

        $this->addUsage('--host=[localhost]');
        $this->addUsage('--purl=[physical_url]');
        $this->addUsage('--vurl=[virutal_url]');
        $this->addUsage('--ssl ssl option ');
        $this->addUsage('--id_shop specify shop id by default : PS_SHOP_DEFAULT ');
        $this->addUsage('--modify-employee-pwd to change all employees password');
        $this->addUsage('--modify-customer-pwd to change all customers password');
        $this->addUsage('--customer-pwd password for all customers');
        $this->addUsage('--employee-pwd password for all employees');
        $this->addOption('host', 'u', InputOption::VALUE_REQUIRED, 'host to set');
        $this->addOption('purl', 'p', InputOption::VALUE_REQUIRED, 'physical url');
        $this->addOption('vurl', null, InputOption::VALUE_REQUIRED, 'virtual url');
        $this->addOption('ssl', null, InputOption::VALUE_REQUIRED, 'Use ssl?', 0);
        $this->addOption('modify-employee-pwd', 'mep', InputOption::VALUE_NONE, 'Interactively modify all employee BO password');
        $this->addOption('modify-customer-pwd', 'mcp', InputOption::VALUE_NONE, 'Interactively modify all customers password');
        $this->addOption('customer-pwd', 'cpwd', InputOption::VALUE_REQUIRED, 'Modify all customers passwords', false);
        $this->addOption('employee-pwd', 'epwd', InputOption::VALUE_REQUIRED, 'Modify all employees passwords', false);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->helper = $this->getHelper('question');
        $this->dbi = \Db::getInstance();
        $this->crypto = new Hashing();
        $this->isMultiShop = (bool) Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE');

        $res = true;

        if ($input->getOption('verbose')) {
            dump($input->getOptions());
            dump($input->getArguments());
        }

        $this->dbi->execute('START TRANSACTION');

        //Get options value
        $ssl = (bool) $input->getOption('ssl');

        $idShop = (int) $input->getOption('id_shop') !== 0 ? (int) $input->getOption('id_shop') : (int) Configuration::get('PS_SHOP_DEFAULT');
        $shop = new ShopUrl($idShop);

        $host = $input->getOption('host') ?? $this->helper->ask($input, $output, new Question('<question>Please, specify the host you want for your env, currently  : ' . $shop->domain . ' Press enter for the same</question>'));
        $puri = $input->getOption('purl') ?? $this->helper->ask($input, $output, new Question('<question>Please, specify the physical url you want for your env, currently : ' . $shop->physical_uri . ' Press enter for the same</question>'));
        $vuri = $input->getOption('vurl');

        if (!$host) {
            $host = $shop->domain;
        }

        if (!$puri) {
            $puri = $shop->physical_uri;
        }

        $modifyEmployeePwd = (bool) $input->getOption('modify-employee-pwd');
        $modifyCustomerPwd = (bool) $input->getOption('modify-customer-pwd');

        $this->io->text('<info>Update table ps_configuration</info>');

        //URL configuration
        /** @phpstan-ignore-next-line */
        $res = $res && $this->updateUrlConfiguration($idShop, $host);

        //SSL configuration
        $res = $res && $this->updateSslConfiguration($idShop, $ssl);

        //URL configuration in shop_url
        $res = $res && $this->updateShopUrl($idShop, $host, $puri, $vuri);

        //Regenerate htaccess
        $this->io->text('<info>Regenerate htaccess</info>');
        $command = $this->getApplication()->find('fop:generate:htaccess');
        $command->initialize($input, $output);
        $returnCode = $command->execute($input, $output);
        $res = $res && !$returnCode;

        //Change Employee BO pwd
        if ($modifyEmployeePwd) {
            $this->updateEmployeesPwd($input, $output);
        }

        //Change all customer pwd
        if ($modifyCustomerPwd) {
            $this->updateCustomersPwd($input, $output);
        }

        //debug mode on
        $this->enableDebugMode();

        //Disable maintenance mode
        $this->disableMaintenanceMode();

        //cache off
        $this->io->text('<info>Disable cache</info>');

        //smart cache js css
        $res = $res && $this->disableSmartCacheJsAndCss();

        //global cache
        $res = $res && $this->disableGlobalCache();

        //smarty cache
        $res = $res && $this->disableSmartyCache();

        //Clear all cache
        $this->io->text('<info>Clear all cache</info>');
        $cacheClearChain = $this->getContainer()->get('prestashop.adapter.cache_clearer');
        $cacheClearChain->clearAllCaches();

        if (!$res) {
            //If error ROLLBACK sql update
            $this->dbi->execute('ROLLBACK');
            $this->io->error('Error during setup');

            return 1;
        }

        //If no error commit all sql update
        $this->dbi->execute('COMMIT');
        $this->io->success('Setup finish correctly');

        return 0;
    }

    /**
     * @param string $question
     * @param string|null $default
     * @param string $separator
     *
     * @return string
     */
    protected function createQuestionString(string $question, string $default = null, string $separator = ':'): string
    {
        return null !== $default ?
            sprintf('<fg=green>%s</fg=green> [<fg=yellow>%s</fg=yellow>]%s ', $question, $default, $separator) :
            sprintf('<fg=green>%s</fg=green>%s ', $question, $separator);
    }

    /**
     * Update Shop Url in configuration table
     *
     * @param int $idShop
     * @param string $url
     *
     * @return bool
     */
    protected function updateUrlConfiguration(int $idShop, string $url): bool
    {
        //URL configuration
        $this->io->text(sprintf('<info>set value %s for configuration name : PS_SHOP_DOMAIN and PS_SHOP_DOMAIN_SSL</info>', $url));
        $where = sprintf('name in ("%s","%s")', 'PS_SHOP_DOMAIN', 'PS_SHOP_DOMAIN_SSL');
        if ($idShop && $this->isMultiShop) {
            $where = sprintf('name in ("%s","%s") AND id_shop = %s', 'PS_SHOP_DOMAIN', 'PS_SHOP_DOMAIN_SSL', $idShop);
        }

        return $this->dbi->update('configuration', ['value' => $url], $where, 0, false, false);
    }

    /**
     * Update ssl in configuration table
     *
     * @param int $idShop
     * @param bool $ssl
     *
     * @return bool
     */
    protected function updateSslConfiguration(int $idShop, bool $ssl): bool
    {
        $this->io->text(sprintf('<info>set value %s for configuration name : PS_SSL_ENABLED_EVERYWHERE and PS_SSL_ENABLED</info>', (int) $ssl));
        $where = sprintf('name in ("%s", "%s")', 'PS_SSL_ENABLED_EVERYWHERE', 'PS_SSL_ENABLED');
        if ($idShop && $this->isMultiShop) {
            $where = sprintf('name in ("%s", "%s") AND id_shop = %s', 'PS_SSL_ENABLED_EVERYWHERE', 'PS_SSL_ENABLED', $idShop);
        }

        return $this->dbi->update('configuration', ['value' => (int) $ssl], $where, 0, false, false);
    }

    /**
     * Update Url in ps_shop_url table
     *
     * @param int $idShop
     * @param string $url
     * @param string $physicalUrl
     * @param string $virtualUrl
     *
     * @return bool
     */
    protected function updateShopUrl(int $idShop, string $url, ?string $physicalUrl = '/', ?string $virtualUrl = ''): bool
    {
        $this->io->text('<info>Update table ps_shop_url</info>');
        $where = sprintf('id_shop = %s', $idShop);

        return $this->dbi->update('shop_url', ['domain' => $url, 'domain_ssl' => $url, 'physical_uri' => $physicalUrl, 'virtual_uri' => $virtualUrl], $where, 0, false, false);
    }

    /**
     * Update employees password
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return bool
     */
    protected function updateEmployeesPwd(InputInterface $input, OutputInterface $output): bool
    {
        $this->io->text('<info>Modify password for all BO employees</info>');
        $pwd = ((bool) $input->getOption('employee-pwd') === true) ? $input->getOption('employee-pwd') : $this->helper->ask($input, $output, new Question($this->createQuestionString('Please, define password for all employee')));

        return $this->dbi->update('employee', ['passwd' => $this->crypto->hash($pwd, _COOKIE_KEY_)], '', 0, false, false);
    }

    /**
     * Update customers password
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return bool
     */
    protected function updateCustomersPwd(InputInterface $input, OutputInterface $output): bool
    {
        $this->io->text('<info>Modify password for all customers</info>');
        $pwd = ((bool) $input->getOption('customer-pwd') === true) ? $input->getOption('customer-pwd') : $this->helper->ask($input, $output, new Question($this->createQuestionString('Please, define password for all customer')));

        return $this->dbi->update('customer', ['passwd' => $this->crypto->hash($pwd, _COOKIE_KEY_)], '', 0, false, false);
    }

    /**
     * Enable debug mode
     *
     * - First, check if _PS_MODE_DEV_ is set and truthy.
     * - In case it's not defined (can this happen ?), check the env variable PS_DEV_MODE.
     * - In case it's not defined, rely on \PrestaShop\PrestaShop\Adapter\Debug\DebugMode().
     *
     * @return void
     */
    protected function enableDebugMode(): void
    {
        // 1. check by current defined value, most reliable value.
        if (defined('_PS_MODE_DEV_') && true === (bool) _PS_MODE_DEV_) {
            $this->io->write('<info>Debug mode enabled. (_PS_MODE_DEV_ defined).</info>');

            return;
        }

        // 2. check by environment variable PS_DEV_MODE.
        // On a docker install, PS_DEV_MODE is defined and take priority on the value defined in config/defines.inc.php.
        // https://github.com/PrestaShop/docker/blob/master/base/config_files/defines_custom.inc.php
        // So we can suppose (weak) that PS_DEV_MODE defines the current debug mode.
        $envDebugMode = getenv('PS_DEV_MODE');
        if (true === boolval($envDebugMode)) {
            $this->io->write('<info>Debug mode enabled (defined by environment variable) OK.</info>');

            return;
        }

        // env var set but not truthy
        if ($envDebugMode !== false) {
            $this->io->error(
                'Debug mode disabled (defined by environment variable)'
                . PHP_EOL . ' You must change it in the OS.'
                . PHP_EOL . ' Current value : ' . var_export($envDebugMode, true)
            );

            return;
        }

        // 3. Rely on DebugMode
        $debugMode = new \PrestaShop\PrestaShop\Adapter\Debug\DebugMode();
        if ($debugMode->isDebugModeEnabled()) {
            $this->io->write('<info>Debug mode enabled OK (untouched).</info>');

            return;
        }

        $enabled = $debugMode->enable();

        if ($enabled === DebugMode::DEBUG_MODE_SUCCEEDED) {
            $this->io->write('<info>Debug mode enabled OK.</info>');

            return;
        }

        $this->io->error(
            'Failed to change debug using the DebugMode adapter.'
            . PHP_EOL . ' Error code : ' . var_export($enabled, true)
        );
    }

    /**
     * Set maintenance mode to off
     *
     * @return array
     */
    protected function disableMaintenanceMode(): array
    {
        $this->io->text('<info>Disable maintenance mode</info>');
        $mc = $this->getContainer()->get('prestashop.adapter.maintenance.configuration');
        $maintenanceConf = $mc->getConfiguration();
        $maintenanceConf['enable_shop'] = true;

        return $mc->updateConfiguration($maintenanceConf);
    }

    /**
     * Disable smart cache for js, css and apache
     *
     * @return bool
     */
    protected function disableSmartCacheJsAndCss(): bool
    {
        $this->io->text('<info>Setup smart cache (js & css)</info>');
        $ccc = $this->getContainer()->get('prestashop.adapter.ccc.configuration');
        $combineCacheConfig = $ccc->getConfiguration();
        $combineCacheConfig['smart_cache_css'] = false;
        $combineCacheConfig['smart_cache_js'] = false;
        $combineCacheConfig['apache_optimization'] = false;

        return !$ccc->updateConfiguration($combineCacheConfig);
    }

    /**
     * Disable global cache
     *
     * @return bool
     */
    protected function disableGlobalCache(): bool
    {
        $this->io->text('<info>Setup global cache</info>');
        $cc = $this->getContainer()->get('prestashop.adapter.caching.configuration');
        $cachingConf = $cc->getConfiguration();
        $cachingConf['use_cache'] = false;

        return !$cc->updateConfiguration($cachingConf);
    }

    /**
     * Disable smarty cache, set to force compilation
     *
     * @return bool
     */
    protected function disableSmartyCache(): bool
    {
        $this->io->text('<info>Setup smarty cache</info>');
        $smc = $this->getContainer()->get('prestashop.adapter.smarty_cache.configuration');
        $smartyCacheConf = $smc->getConfiguration();
        $smartyCacheConf['template_compilation'] = 2;
        $smartyCacheConf['cache'] = false;
        $smartyCacheConf['multi_front_optimization'] = false;
        $smartyCacheConf['caching_type'] = 'filesystem';
        $smartyCacheConf['clear_cache'] = 'everytime';

        return !$smc->updateConfiguration($smartyCacheConf);
    }
}
