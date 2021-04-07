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
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    Friends of Presta <infos@friendsofpresta.org>
 * @copyright since 2020 Friends of Presta
 * @license   https://opensource.org/licenses/AFL-3.0  Academic Free License ("AFL") v. 3.0
 */

namespace FOP\Console\Commands;

use FOP\Console\Command;
use PrestaShop\PrestaShop\Core\Crypto\Hashing;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

class DevSetupEnv extends Command
{
    /**
     * @var SymfonyStyle
     */
    protected $io;

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

    protected function configure()
    {
        $this->setName('fop:dev:setup-env')
            ->setDescription('Install your project for local developement')
            ->setHelp('<info>This command update database configuration with dev parameters (url, ssl, passwords). </info>' . PHP_EOL .
                        '<info>How to use : </info>' . PHP_EOL .
                        '  <info>php bin/console fop:dev:setup-env --url=url.local --modifyemployeepwd=1 --modifycustomerpwd=1 --employeepwd=fopisnice --customerpwd=fopisnice --ssl=0</info>'
                        );

        $this->addUsage('--url=[url]');
        $this->addUsage('--ssl ssl option ');
        $this->addUsage('--id_shop specify shop id ');
        $this->addUsage('--modifyemployeepwd to change all employees password');
        $this->addUsage('--modifycustomerpwd to change all customers password');
        $this->addUsage('--customerpwd password for all customers');
        $this->addUsage('--employeepwd password for all employees');
        $this->addOption('url', 'u', InputOption::VALUE_REQUIRED, 'url to set');
        $this->addOption('ssl', null, InputOption::VALUE_REQUIRED, 'Use ssl?', 0);
        $this->addOption('modifyemployeepwd', 'mep', InputOption::VALUE_REQUIRED, 'Modify all employee BO password', 0);
        $this->addOption('modifycustomerpwd', 'mcp', InputOption::VALUE_REQUIRED, 'Modify all customers password', 0);
        $this->addOption('customerpwd', 'cpwd', InputOption::VALUE_REQUIRED, 'Define all customers passwords', false);
        $this->addOption('employeepwd', 'epwd', InputOption::VALUE_REQUIRED, 'Define all employees passwords', false);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->helper = $this->getHelper('question');
        $this->dbi = \Db::getInstance();
        $this->crypto = new Hashing();

        $res = true;

        if ($input->getOption('verbose')) {
            dump($input->getOptions());
            dump($input->getArguments());

            //dump($input->getArguments());
        }
        //START MYSQL TRANSACTION
        $this->dbi->execute('START TRANSACTION');

        //Get options value
        $ssl = (bool) $input->getOption('ssl');
        $url = $input->getOption('url') ?? $this->helper->ask($input, $output, new Question('<question>Please, specify the url you want for your env</question>'));
        $idShop = (int) $input->getOption('id_shop');
        $modifyemployeepwd = (bool) $input->getOption('modifyemployeepwd');
        $modifycustomerpwd = (bool) $input->getOption('modifycustomerpwd');

        $this->io->text('<info>Update table ps_configuration</info>');

        //URL configuration
        $res = $res && $this->updateUrlConfiguration($idShop, $url);

        //SSL configuration
        $res = $res && $this->updateSslConfiguration($idShop, $ssl);

        //URL configuration in shop_url
        $res = $res && $this->updateShopUrl($idShop, $url);

        //Regenerate htaccess
        $this->io->text('<info>Regenerate htaccess</info>');
        $command = $this->getApplication()->find('fop:generate:htaccess');
        $returnCode = $command->execute($input, $output);
        $res = $res && !$returnCode;

        //Change Employee BO pwd
        if ($modifyemployeepwd) {
            $this->updateEmployeesPwd($input, $output);
        }

        //Change all customer pwd
        if ($modifycustomerpwd) {
            $this->updateCustomersPwd($input, $output);
        }

        //debug mode on
        $this->enableDecbugMode();

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
     * @param $idShop
     * @param $url
     *
     * @return bool
     */
    protected function updateUrlConfiguration(int $idShop, string $url): bool
    {
        //URL configuration
        $this->io->text(sprintf('<info>set value %s for configuration name : PS_SHOP_DOMAIN and PS_SHOP_DOMAIN_SSL</info>', $url));
        $where = sprintf('name in ("%s","%s")', 'PS_SHOP_DOMAIN', 'PS_SHOP_DOMAIN_SSL');
        if ($idShop) {
            $where = sprintf('name in ("%s","%s") AND id_shop = %s', 'PS_SHOP_DOMAIN', 'PS_SHOP_DOMAIN_SSL', $idShop);
        }

        return $this->dbi->update('configuration', ['value' => $url], $where, 0, false, false);
    }

    /**
     * Update ssl in configuration table
     *
     * @param $idShop
     * @param $ssl
     *
     * @return bool
     */
    protected function updateSslConfiguration(int $idShop, bool $ssl): bool
    {
        $this->io->text(sprintf('<info>set value %s for configuration name : PS_SSL_ENABLED_EVERYWHERE and PS_SSL_ENABLED</info>', (int) $ssl));
        $where = sprintf('name in ("%s", "%s")', 'PS_SSL_ENABLED_EVERYWHERE', 'PS_SSL_ENABLED');
        if ($idShop) {
            $where = sprintf('name in ("%s", "%s") AND id_shop = %s', 'PS_SSL_ENABLED_EVERYWHERE', 'PS_SSL_ENABLED', $idShop);
        }

        return $this->dbi->update('configuration', ['value' => (int) $ssl], $where, 0, false, false);
    }

    /**
     * Update Url in ps_shop_url table
     *
     * @param $idShop
     * @param $url
     *
     * @return bool
     */
    protected function updateShopUrl(int $idShop, string $url): bool
    {
        $this->io->text('<info>Update table ps_shop_url</info>');
        $where = sprintf('id_shop = %s', $idShop);

        return $this->dbi->update('shop_url', ['domain' => $url, 'domain_ssl' => $url], $where, 0, false, false);
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
        $pwd = ((bool) $input->getOption('employeepwd') === true) ? $input->getOption('employeepwd') : $this->helper->ask($input, $output, new Question($this->createQuestionString('Please, define password for all employee')));

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
        $pwd = ((bool) $input->getOption('customerpwd') === true) ? $input->getOption('customerpwd') : $this->helper->ask($input, $output, new Question($this->createQuestionString('Please, define password for all customer')));

        return $this->dbi->update('customer', ['passwd' => $this->crypto->hash($pwd, _COOKIE_KEY_)], '', 0, false, false);
    }

    /**
     * Enable debug mode
     *
     * @return int
     */
    protected function enableDecbugMode(): int
    {
        $this->io->text('<info>Active debug mode</info>');
        $debugMode = new \PrestaShop\PrestaShop\Adapter\Debug\DebugMode();

        return $debugMode->enable();
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
