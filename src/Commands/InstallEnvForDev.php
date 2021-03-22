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
use PrestaShop\PrestaShop\Adapter\Cache\CombineCompressCacheConfiguration;
use PrestaShop\PrestaShop\Core\Crypto\Hashing;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

class InstallEnvForDev extends Command
{
    /**
     *
     */
    protected function configure()
    {
        $this->setName('fop:install-env-for-dev')
            ->setDescription('Install your project for local developement')
            ->setHelp('This command database configuration with dev parameters (url,ssl)');
        $this->addUsage('--url=[url]');
        $this->addUsage('--ssl=[TRUE or FALSE]');
        $this->addUsage('--is_shop specify shop id ');
        $this->addUsage('--modifyemployeepwd to change all employees password');
        $this->addUsage('--modifycustomerpwd to change all customers password');
        $this->addOption('url', 'u', InputOption::VALUE_REQUIRED, 'url to set');
        $this->addOption('ssl', null, InputOption::VALUE_REQUIRED, 'Use ssl? default false');
        $this->addOption('modifyemployeepwd', 'mep',InputOption::VALUE_OPTIONAL, 'Modify all employee BO password', false);
        $this->addOption('modifycustomerpwd', 'mcp',InputOption::VALUE_OPTIONAL,'Modify all customers password', false);
    }
    
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        dump($input->getOptions());
        dump($input->getArguments());
        $io = new SymfonyStyle($input, $output);
        $helper = $this->getHelper('question');
        $dbi = \Db::getInstance();
        $crypto = new Hashing();
        $res = true;
        
        //START MYSQL TRANSACTION
        $dbi->execute('START TRANSACTION;');
        
        //Get options value
        $ssl = (bool)$input->getOption('ssl');
        $url = $input->getOption('url') ?? $helper->ask($input, $output, new Question('<question>Please, specify the url you want for your env</question>'));
        $idShop = (int)$input->getOption('id_shop');
        $modifyemployeepwd = (bool)$input->getOption('modifyemployeepwd');
        $modifycustomerpwd = (bool)$input->getOption('modifycustomerpwd');
        
        $io->text('<info>Update table ps_configuration</info>');
    
        //URL configuration
        $io->text(sprintf('<info>set value %s for configuration name : PS_SHOP_DOMAIN and PS_SHOP_DOMAIN_SSL</info>', $url));
        $where = sprintf('name in ("%s","%s")', 'PS_SHOP_DOMAIN', 'PS_SHOP_DOMAIN_SSL');
        if ($idShop) {
            $where = sprintf('name in ("%s","%s") AND id_shop = %s', 'PS_SHOP_DOMAIN', 'PS_SHOP_DOMAIN_SSL', $idShop);
        }
        $res = $res && $dbi->update('configuration', ['value' => $url], $where, 0, false, false);
        
        //SSL configuration
        $io->text(sprintf('<info>set value %s for configuration name : PS_SSL_ENABLED_EVERYWHERE and PS_SSL_ENABLED</info>', (int)$ssl));
        $where = sprintf('name in ("%s", "%s")', 'PS_SSL_ENABLED_EVERYWHERE', 'PS_SSL_ENABLED');
        if ($idShop) {
            $where = sprintf('name in ("%s", "%s") AND id_shop = %s', 'PS_SSL_ENABLED_EVERYWHERE', 'PS_SSL_ENABLED', $idShop);
        }
        $res = $res && $dbi->update('configuration', ['value' => (int)$ssl], $where, 0, false, false);
        
        //URL configuration in shop_url
        $io->text('<info>Update table ps_shop_url</info>');
        $where = sprintf('id_shop = %s', $idShop);
        $res = $res && $dbi->update('shop_url', ['domain' => $url, 'domain_ssl' => $url], $where, 0, false, false);
    
    
        //Regenerate htaccess
        $io->text('<info>Regenerate htaccess</info>');
        $command = $this->getApplication()->find('fop:generate:htaccess');
        $returnCode = $command->execute($input, $output);
        $res = $res && !$returnCode;
    
        //Change Employee BO pwd
        if ($modifyemployeepwd) {
            $io->text('<info>Modify password for all BO employees</info>');
            $pwd = $helper->ask($input, $output, new Question($this->createQuestionString('Please, define password for all employee')));
            $res = $res && $dbi->update('employee', ['passwd' => $crypto->hash($pwd, _COOKIE_KEY_)], '', 0, false, false);
        }
    
        //Change all customer pwd
        if ($modifycustomerpwd) {
            $io->text('<info>Modify password for all customers</info>');
            $pwd = $helper->ask($input, $output, new Question($this->createQuestionString('Please, define password for all customer')));
            $res = $res && $dbi->update('customer', ['passwd' => $crypto->hash($pwd, _COOKIE_KEY_)], '', 0, false, false);
        }
        
        //debug mode on
        $io->text('<info>Active debug mode</info>');
        $debugMode = new \PrestaShop\PrestaShop\Adapter\Debug\DebugMode();
        $debugMode->enable();
        
        
        //cache off
        $io->text('<info>Disable cache</info>');
        
        //smart cache js css
        $io->text('<info>Setup smart cache (js & css)</info>');
        $ccc = $this->getContainer()->get('prestashop.adapter.ccc.configuration');
        $combineCacheConfig = $ccc->getConfiguration();
        $combineCacheConfig['smart_cache_css'] = false;
        $combineCacheConfig['smart_cache_js'] = false;
        $combineCacheConfig['apache_optimization'] = false;
        $res = $res && !$ccc->updateConfiguration($combineCacheConfig);
        
        //global cache
        $io->text('<info>Setup global cache</info>');
        $cc = $this->getContainer()->get('prestashop.adapter.caching.configuration');
        $cachingConf = $cc->getConfiguration();
        $cachingConf['use_cache'] = false;
        $res = $res && !$cc->updateConfiguration($cachingConf);
        
        //smarty cache
        $io->text('<info>Setup smarty cache</info>');
        $smc = $this->getContainer()->get('prestashop.adapter.smarty_cache.configuration');
        $smartyCacheConf = $smc->getConfiguration();
        $smartyCacheConf['template_compilation'] = 2;
        $smartyCacheConf['cache'] = false;
        $smartyCacheConf['multi_front_optimization'] = false;
        $smartyCacheConf['caching_type'] = 'filesystem';
        $smartyCacheConf['clear_cache'] = 'everytime';
        $res = $res && !$smc->updateConfiguration($smartyCacheConf);
        
        //Clear all cache
        $io->text('<info>Clear all cache</info>');
        $cacheClearChain = $this->getContainer()->get('prestashop.core.cache.clearer.cache_clearer_chain');
        $cacheClearChain->clear();
        
        if(!$res) {
            //If error ROLLBACK sql update
            $dbi->execute('ROLLBACK');
            $io->error('Error during setup');
            return 1;
        }
        
        //If no error commit all sql update
        $dbi->execute('COMMIT;');
        $io->success('Setup finish correctly');
    
        return 0;
    }
    
    /**
     * @param string $question
     * @param string|null $default
     * @param string $separator
     * @return string
     */
    protected function createQuestionString(string $question, string $default = null, string $separator = ':'): string
    {
        return null !== $default ?
            sprintf('<fg=green>%s</fg=green> [<fg=yellow>%s</fg=yellow>]%s ', $question, $default, $separator) :
            sprintf('<fg=green>%s</fg=green>%s ', $question, $separator);
    }
}