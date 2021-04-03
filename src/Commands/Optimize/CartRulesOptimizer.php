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

use DateTime;
use Db;
use FOP\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

class CartRulesOptimizer extends Command
{
    private $from_date;
    private $logs;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('fop:optimize:cart-rules')
            ->setDescription('Clear and optimize your Prestashop Cart rules')
            ->setHelp('Clear your Prestashop from useless row in cart rules table');

        $this->addUsage('--from=[from-date] (format: Y-m-d, default: 1 month)');
        $this->addOption('from-date', null, InputOption::VALUE_OPTIONAL);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $helper = $this->getHelper('question');
        $from_date = $input->getOption('from-date') ?? $helper->ask($input, $output, new Question('<question>Remove deprecated cart rules since ? Default 1 month, type date format "Y-m-d" to override.</question>'));

        if (!$this->checkDate($from_date)) {
            $io->error('Incorrect date parameter. Please type a date from format Y-m-d. Date given : ' . $from_date);
        }

        $this->clearCartRules();

        if ($this->logs['cart_rules'] === 0) {
            $io->success('Cart rules already clean. No need more.');
        } else {
            $io->success('Cart rules clear successfully ! We delete ' . $this->logs['cart_rules'] . ' cart rules !');
        }
    }

    /**
     * {@inheritdoc}
     */
    private function clearCartRules()
    {
        $instance = Db::getInstance();

        $instance->delete('cart_rule_combination', 'id_cart_rule_1 
            IN (SELECT id_cart_rule  FROM `' . _DB_PREFIX_ . 'cart_rule` WHERE `date_to` < "' . $this->from_date . '" OR active = 0 OR quantity = 0)
            OR `id_cart_rule_2` IN (SELECT id_cart_rule  FROM `' . _DB_PREFIX_ . 'cart_rule` WHERE `date_to` < "' . $this->from_date . '" OR active = 0 OR quantity = 0)');

        $instance->delete('cart_rule_product_rule_group', 'id_cart_rule IN (SELECT id_cart_rule  FROM `ps_cart_rule` WHERE `date_to` < "' . $this->from_date . '" OR active = 0 OR quantity = 0)');
        $instance->delete('cart_rule_product_rule', 'NOT EXISTS (SELECT 1 FROM `' . _DB_PREFIX_ . 'cart_rule_product_rule_group` WHERE `' . _DB_PREFIX_ . 'cart_rule_product_rule`.`id_product_rule_group` = `' . _DB_PREFIX_ . 'cart_rule_product_rule_group`.`id_product_rule_group`)');
        $instance->delete('cart_rule_product_rule_value', 'NOT EXISTS (SELECT 1 FROM `' . _DB_PREFIX_ . 'cart_rule_product_rule` WHERE `' . _DB_PREFIX_ . 'cart_rule_product_rule_value`.`id_product_rule` = `' . _DB_PREFIX_ . 'cart_rule_product_rule`.`id_product_rule`)');
        $instance->delete('cart_rule', '`date_to` < "' . $this->from_date . '" OR active = 0 OR quantity = 0');

        $this->logs['cart_rules'] = $instance->affected_rows();
    }

    /**
     * @param $date
     *
     * @return bool
     */
    private function checkDate($date)
    {
        if (is_null($date)) {
            $this->from_date = date('Y-m-d', strtotime('-1 month'));

            return true;
        }

        if ($date = DateTime::createFromFormat('Y-m-d', $date)) {
            $this->from_date = $date->format('Y-m-d');

            return true;
        }

        return false;
    }
}
