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

namespace FOP\Console\Commands\Optimize;

use Category;
use Db;
use FOP\Console\Command;
use PrestaShop\PrestaShop\Core\Addon\Module\ModuleManager;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DataIntegrityOptimizer extends Command
{
    private $module_manager;
    private $db_instance;
    private $progressBar;
    private $logs;

    public function __construct(ModuleManager $moduleManager, $name = null)
    {
        $this->module_manager = $moduleManager;
        $this->db_instance = Db::getInstance();

        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('fop:optimize:integrity')
            ->setDescription('Optimize your Prestashop database')
            ->setHelp('Clear your Prestashop from useless row in database');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $this->progressBar = new ProgressBar($output, 100);
        $this->progressBar->start();
        $this->clearConfiguration();
        $this->progressBar->advance(15);

        $this->fixDataTables();
        $this->progressBar->advance(15);

        $this->fixLangShopTables();
        $this->progressBar->advance(20);

        $this->repairAndOptimizeTables();

        //regenerate tree after optimization
        Category::regenerateEntireNtree();
        $this->progressBar->advance(5);

        $this->progressBar->finish();
    }

    private function clearConfiguration()
    {
        $this->db_instance->delete('configuration_lang', '`id_configuration` NOT IN (SELECT `id_configuration` FROM `' . _DB_PREFIX_ . 'configuration`)
		OR `id_configuration` IN (SELECT `id_configuration` FROM `' . _DB_PREFIX_ . 'configuration` WHERE name IS NULL OR name = "")');

        $duplicate = [];
        $configuration_values = $this->db_instance->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'configuration');
        foreach ($configuration_values as $configuration) {
            $key = $configuration['id_shop_group'] . '-|-' . $configuration['id_shop'] . '-|-' . $configuration['name'];

            if (in_array($key, $duplicate)) {
                $this->db_instance->delete('configuration', 'id_configuration = ' . $configuration['id_configuration']);
                $this->logs['configuration'][$configuration['name']] = 1;

                continue;
            }

            $duplicate[] = $key;
        }
    }

    private function fixDataTables()
    {
        foreach ($this->getTablesToCheck() as $table) {
            // If this is a module and the module is not installed, we continue
            if (isset($table[4]) && !$this->module_manager->isInstalled($table[4])) {
                continue;
            }

            $this->db_instance->delete($table[0], '`' . $table[1] . '` NOT IN (SELECT `' . $table[3] . '` FROM `' . _DB_PREFIX_ . $table[2] . '`)');
            $this->logs['integrity'][$table[0]] = $this->db_instance->affected_rows();
        }
    }

    private function fixLangShopTables()
    {
        // _lang table cleaning
        $tables_lang = $this->db_instance->executeS('SHOW TABLES LIKE "' . _DB_PREFIX_ . '%_lang"');
        foreach ($tables_lang as $table) {
            $table_lang = str_replace(_DB_PREFIX_, '', current($table));
            $table = str_replace('_lang', '', $table_lang);
            $id_table = 'id_' . preg_replace('/^' . _DB_PREFIX_ . '/', '', $table);

            if ($this->columnExists($table, $id_table)) {
                $this->db_instance->delete($table_lang, '`' . bqSQL($id_table) . '` NOT IN (SELECT `' . bqSQL($id_table) . '` FROM `' . _DB_PREFIX_ . bqSQL($table) . '`)');
                $this->db_instance->delete($table_lang, '`id_lang` NOT IN (SELECT `id_lang` FROM `' . _DB_PREFIX_ . 'lang`)');
                $this->logs['table_lang'][$table_lang] = $this->db_instance->affected_rows();
            }
        }

        // _shop table cleaning
        $tables_shop = $this->db_instance->executeS('SHOW TABLES LIKE "' . _DB_PREFIX_ . '%_shop"');
        foreach ($tables_shop as $table) {
            $table_shop = str_replace(_DB_PREFIX_, '', current($table));
            $table = str_replace('_shop', '', $table_shop);
            $id_table = 'id_' . preg_replace('/^' . _DB_PREFIX_ . '/', '', $table);

            //prevent for carrier_tax_rules_group_shop table
            if (in_array($table_shop, array('carrier_tax_rules_group_shop'))) {
                continue;
            }

            if ($this->columnExists($table, $id_table)) {
                $this->db_instance->delete(bqSQL($table_shop), '`' . bqSQL($id_table) . '` NOT IN (SELECT `' . bqSQL($id_table) . '` FROM `' . _DB_PREFIX_ . bqSQL($table) . '`)');
                $this->db_instance->delete(bqSQL($table_shop), '`id_shop` NOT IN (SELECT `id_shop` FROM `' . _DB_PREFIX_ . 'shop`)');
                $this->logs['table_shop'][$table_shop] = $this->db_instance->affected_rows();
            }
        }

        //clean stock available
        $this->db_instance->delete('stock_available', '`id_shop` NOT IN (SELECT `id_shop` FROM `' . _DB_PREFIX_ . 'shop`) AND `id_shop_group` NOT IN (SELECT `id_shop_group` FROM `' . _DB_PREFIX_ . 'shop_group`)');
    }

    private function columnExists($table, $column)
    {
        $this->db_instance->execute('SHOW COLUMNS FROM `'._DB_PREFIX_ . $table.'` LIKE "'. $column . '"');

        return $this->db_instance->numRows() > 0;
    }

    private function repairAndOptimizeTables()
    {
        $tables = $this->db_instance->executeS('SHOW TABLES LIKE "' . _DB_PREFIX_ . '%"');
        $len = count($tables);
        $progress_per_loop = 45 / $len;

        $i = 1;
        $local_progress = 0;
        foreach ($tables as $table) {
            $table = current($table);
            $this->db_instance->execute('OPTIMIZE TABLE `' . $table . '`');
            $progress = round($i * $progress_per_loop);

            if ($progress > $local_progress) {
                $this->progressBar->advance($progress - $local_progress);
                $local_progress = $progress;
            }

            $i++;
        }

        return true;
    }

    private function getTablesToCheck()
    {
        return [
            // 0 => DELETE FROM __table__, 1 => WHERE __id__ NOT IN, 2 => NOT IN __table__, 3 => __id__ used in the "NOT IN" table, 4 => module_name
            ['access', 'id_profile', 'profile', 'id_profile'],
            ['accessory', 'id_product_1', 'product', 'id_product'],
            ['accessory', 'id_product_2', 'product', 'id_product'],
            ['address_format', 'id_country', 'country', 'id_country'],
            ['attribute', 'id_attribute_group', 'attribute_group', 'id_attribute_group'],
            ['carrier_group', 'id_carrier', 'carrier', 'id_carrier'],
            ['carrier_group', 'id_group', 'group', 'id_group'],
            ['carrier_zone', 'id_carrier', 'carrier', 'id_carrier'],
            ['carrier_zone', 'id_zone', 'zone', 'id_zone'],
            ['cart_cart_rule', 'id_cart', 'cart', 'id_cart'],
            ['cart_product', 'id_cart', 'cart', 'id_cart'],
            ['cart_rule_carrier', 'id_cart_rule', 'cart_rule', 'id_cart_rule'],
            ['cart_rule_carrier', 'id_carrier', 'carrier', 'id_carrier'],
            ['cart_rule_combination', 'id_cart_rule_1', 'cart_rule', 'id_cart_rule'],
            ['cart_rule_combination', 'id_cart_rule_2', 'cart_rule', 'id_cart_rule'],
            ['cart_rule_country', 'id_cart_rule', 'cart_rule', 'id_cart_rule'],
            ['cart_rule_country', 'id_country', 'country', 'id_country'],
            ['cart_rule_group', 'id_cart_rule', 'cart_rule', 'id_cart_rule'],
            ['cart_rule_group', 'id_group', 'group', 'id_group'],
            ['cart_rule_lang', 'id_cart_rule', 'cart_rule', 'id_cart_rule'],
            ['cart_rule_lang', 'id_lang', 'lang', 'id_lang'],
            ['cart_rule_product_rule_group', 'id_cart_rule', 'cart_rule', 'id_cart_rule'],
            ['cart_rule_product_rule', 'id_product_rule_group', 'cart_rule_product_rule_group', 'id_product_rule_group'],
            ['cart_rule_product_rule_value', 'id_product_rule', 'cart_rule_product_rule', 'id_product_rule'],
            ['category_group', 'id_category', 'category', 'id_category'],
            ['category_group', 'id_group', 'group', 'id_group'],
            ['category_product', 'id_category', 'category', 'id_category'],
            ['category_product', 'id_product', 'product', 'id_product'],
            ['cms', 'id_cms_category', 'cms_category', 'id_cms_category'],
            ['cms_block', 'id_cms_category', 'cms_category', 'id_cms_category', 'blockcms'],
            ['cms_block_page', 'id_cms', 'cms', 'id_cms', 'blockcms'],
            ['cms_block_page', 'id_cms_block', 'cms_block', 'id_cms_block', 'blockcms'],
            ['connections', 'id_shop_group', 'shop_group', 'id_shop_group'],
            ['connections', 'id_shop', 'shop', 'id_shop'],
            ['connections_page', 'id_connections', 'connections', 'id_connections'],
            ['connections_page', 'id_page', 'page', 'id_page'],
            ['connections_source', 'id_connections', 'connections', 'id_connections'],
            ['customer', 'id_shop_group', 'shop_group', 'id_shop_group'],
            ['customer', 'id_shop', 'shop', 'id_shop'],
            ['customer_group', 'id_group', 'group', 'id_group'],
            ['customer_group', 'id_customer', 'customer', 'id_customer'],
            ['customer_message', 'id_customer_thread', 'customer_thread', 'id_customer_thread'],
            ['customer_thread', 'id_shop', 'shop', 'id_shop'],
            ['customization', 'id_cart', 'cart', 'id_cart'],
            ['customization_field', 'id_product', 'product', 'id_product'],
            ['customized_data', 'id_customization', 'customization', 'id_customization'],
            ['delivery', 'id_shop', 'shop', 'id_shop'],
            ['delivery', 'id_shop_group', 'shop_group', 'id_shop_group'],
            ['delivery', 'id_carrier', 'carrier', 'id_carrier'],
            ['delivery', 'id_zone', 'zone', 'id_zone'],
            ['editorial', 'id_shop', 'shop', 'id_shop', 'editorial'],
            ['favorite_product', 'id_product', 'product', 'id_product', 'favoriteproducts'],
            ['favorite_product', 'id_customer', 'customer', 'id_customer', 'favoriteproducts'],
            ['favorite_product', 'id_shop', 'shop', 'id_shop', 'favoriteproducts'],
            ['feature_product', 'id_feature', 'feature', 'id_feature'],
            ['feature_product', 'id_product', 'product', 'id_product'],
            ['feature_value', 'id_feature', 'feature', 'id_feature'],
            ['group_reduction', 'id_group', 'group', 'id_group'],
            ['group_reduction', 'id_category', 'category', 'id_category'],
            ['homeslider', 'id_shop', 'shop', 'id_shop', 'homeslider'],
            ['homeslider', 'id_homeslider_slides', 'homeslider_slides', 'id_homeslider_slides', 'homeslider'],
            ['hook_module', 'id_hook', 'hook', 'id_hook'],
            ['hook_module', 'id_module', 'module', 'id_module'],
            ['hook_module_exceptions', 'id_hook', 'hook', 'id_hook'],
            ['hook_module_exceptions', 'id_module', 'module', 'id_module'],
            ['hook_module_exceptions', 'id_shop', 'shop', 'id_shop'],
            ['image', 'id_product', 'product', 'id_product'],
            ['message', 'id_cart', 'cart', 'id_cart'],
            ['message_readed', 'id_message', 'message', 'id_message'],
            ['message_readed', 'id_employee', 'employee', 'id_employee'],
            ['module_access', 'id_profile', 'profile', 'id_profile'],
            ['module_country', 'id_module', 'module', 'id_module'],
            ['module_country', 'id_country', 'country', 'id_country'],
            ['module_country', 'id_shop', 'shop', 'id_shop'],
            ['module_currency', 'id_module', 'module', 'id_module'],
            ['module_currency', 'id_currency', 'currency', 'id_currency'],
            ['module_currency', 'id_shop', 'shop', 'id_shop'],
            ['module_group', 'id_module', 'module', 'id_module'],
            ['module_group', 'id_group', 'group', 'id_group'],
            ['module_group', 'id_shop', 'shop', 'id_shop'],
            ['module_preference', 'id_employee', 'employee', 'id_employee'],
            ['orders', 'id_shop', 'shop', 'id_shop'],
            ['orders', 'id_shop_group', 'group_shop', 'id_shop_group'],
            ['order_carrier', 'id_order', 'orders', 'id_order'],
            ['order_cart_rule', 'id_order', 'orders', 'id_order'],
            ['order_detail', 'id_order', 'orders', 'id_order'],
            ['order_detail_tax', 'id_order_detail', 'order_detail', 'id_order_detail'],
            ['order_history', 'id_order', 'orders', 'id_order'],
            ['order_invoice', 'id_order', 'orders', 'id_order'],
            ['order_invoice_payment', 'id_order', 'orders', 'id_order'],
            ['order_invoice_tax', 'id_order_invoice', 'order_invoice', 'id_order_invoice'],
            ['order_return', 'id_order', 'orders', 'id_order'],
            ['order_return_detail', 'id_order_return', 'order_return', 'id_order_return'],
            ['order_slip', 'id_order', 'orders', 'id_order'],
            ['order_slip_detail', 'id_order_slip', 'order_slip', 'id_order_slip'],
            ['pack', 'id_product_pack', 'product', 'id_product'],
            ['pack', 'id_product_item', 'product', 'id_product'],
            ['page', 'id_page_type', 'page_type', 'id_page_type'],
            ['page_viewed', 'id_shop', 'shop', 'id_shop'],
            ['page_viewed', 'id_shop_group', 'shop_group', 'id_shop_group'],
            ['page_viewed', 'id_date_range', 'date_range', 'id_date_range'],
            ['product_attachment', 'id_attachment', 'attachment', 'id_attachment'],
            ['product_attachment', 'id_product', 'product', 'id_product'],
            ['product_attribute', 'id_product', 'product', 'id_product'],
            ['product_attribute_combination', 'id_product_attribute', 'product_attribute', 'id_product_attribute'],
            ['product_attribute_combination', 'id_attribute', 'attribute', 'id_attribute'],
            ['product_attribute_image', 'id_image', 'image', 'id_image'],
            ['product_attribute_image', 'id_product_attribute', 'product_attribute', 'id_product_attribute'],
            ['product_carrier', 'id_product', 'product', 'id_product'],
            ['product_carrier', 'id_shop', 'shop', 'id_shop'],
            ['product_carrier', 'id_carrier_reference', 'carrier', 'id_reference'],
            ['product_country_tax', 'id_product', 'product', 'id_product'],
            ['product_country_tax', 'id_country', 'country', 'id_country'],
            ['product_country_tax', 'id_tax', 'tax', 'id_tax'],
            ['product_download', 'id_product', 'product', 'id_product'],
            ['product_group_reduction_cache', 'id_product', 'product', 'id_product'],
            ['product_group_reduction_cache', 'id_group', 'group', 'id_group'],
            ['product_sale', 'id_product', 'product', 'id_product'],
            ['product_supplier', 'id_product', 'product', 'id_product'],
            ['product_supplier', 'id_supplier', 'supplier', 'id_supplier'],
            ['product_tag', 'id_product', 'product', 'id_product'],
            ['product_tag', 'id_tag', 'tag', 'id_tag'],
            ['range_price', 'id_carrier', 'carrier', 'id_carrier'],
            ['range_weight', 'id_carrier', 'carrier', 'id_carrier'],
            ['referrer_cache', 'id_referrer', 'referrer', 'id_referrer'],
            ['referrer_cache', 'id_connections_source', 'connections_source', 'id_connections_source'],
            ['search_index', 'id_product', 'product', 'id_product'],
            ['search_word', 'id_lang', 'lang', 'id_lang'],
            ['search_word', 'id_shop', 'shop', 'id_shop'],
            ['shop_url', 'id_shop', 'shop', 'id_shop'],
            ['specific_price_priority', 'id_product', 'product', 'id_product'],
            ['stock', 'id_warehouse', 'warehouse', 'id_warehouse'],
            ['stock', 'id_product', 'product', 'id_product'],
            ['stock_available', 'id_product', 'product', 'id_product'],
            ['stock_mvt', 'id_stock', 'stock', 'id_stock'],
            ['tab_module_preference', 'id_employee', 'employee', 'id_employee'],
            ['tab_module_preference', 'id_tab', 'tab', 'id_tab'],
            ['tax_rule', 'id_country', 'country', 'id_country'],
            ['warehouse_carrier', 'id_warehouse', 'warehouse', 'id_warehouse'],
            ['warehouse_carrier', 'id_carrier', 'carrier', 'id_carrier'],
            ['warehouse_product_location', 'id_product', 'product', 'id_product'],
            ['warehouse_product_location', 'id_warehouse', 'warehouse', 'id_warehouse'],
        ];
    }
}
