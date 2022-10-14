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

namespace FOP\Console\Commands\CartRules;

use Db;
use FOP\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CartRulesRemoveOutdated extends Command
{
    private $from_date;
    private $logs;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('fop:cart-rules:remove-outdated')
            ->setDescription('Clear and optimize your Prestashop Cart rules')
            ->setHelp('Clear your Prestashop from useless row in cart rules table');

        $this->addUsage('--from=[days] (30 for example)');
        $this->addOption('days', null, InputOption::VALUE_OPTIONAL, '', 30);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $helper = $this->getHelper('question');
        $days = (int) $input->getOption('days');

        if (!is_int($days)) {
            $io->error('Incorrect days parameter. Please type a number. Days given: ' . $days);

            return 1;
        }

        $this->clearCartRules($days, $output);

        if ($this->logs['cart_rules'] === 0) {
            $io->success('Cart rules already clean. No need more.');
        } else {
            $io->success('Cart rules clear successfully ! We delete ' . $this->logs['cart_rules'] . ' cart rules !');
        }

        return 0;
    }

    /**
     * {@inheritdoc}
     */
    private function clearCartRules(int $days = 30, OutputInterface $output)
    {
        $instance = Db::getInstance();

        $date = new \DateTime('now');
        $date->sub(new \DateInterval('P' . $days . 'D'));
        $this->from_date = $date->format('Y-m-d');
        $output->write('Delete all cart rules with finish or inactive before ' . $this->from_date);

        $instance->delete('cart_rule_combination', 'id_cart_rule_1 
            IN (SELECT id_cart_rule  FROM `' . _DB_PREFIX_ . 'cart_rule` WHERE `date_to` < "' . $this->from_date . '" OR active = 0 OR quantity = 0)
            OR `id_cart_rule_2` IN (SELECT id_cart_rule  FROM `' . _DB_PREFIX_ . 'cart_rule` WHERE `date_to` < "' . $this->from_date . '" OR active = 0 OR quantity = 0)');

        $instance->delete('cart_rule_product_rule_group', 'id_cart_rule IN (SELECT id_cart_rule  FROM `ps_cart_rule` WHERE `date_to` < "' . $this->from_date . '" OR active = 0 OR quantity = 0)');
        $instance->delete('cart_rule_product_rule', 'NOT EXISTS (SELECT 1 FROM `' . _DB_PREFIX_ . 'cart_rule_product_rule_group` WHERE `' . _DB_PREFIX_ . 'cart_rule_product_rule`.`id_product_rule_group` = `' . _DB_PREFIX_ . 'cart_rule_product_rule_group`.`id_product_rule_group`)');
        $instance->delete('cart_rule_product_rule_value', 'NOT EXISTS (SELECT 1 FROM `' . _DB_PREFIX_ . 'cart_rule_product_rule` WHERE `' . _DB_PREFIX_ . 'cart_rule_product_rule_value`.`id_product_rule` = `' . _DB_PREFIX_ . 'cart_rule_product_rule`.`id_product_rule`)');
        $instance->delete('cart_rule', '`date_to` < "' . $this->from_date . '" OR active = 0 OR quantity = 0');

        $this->logs['cart_rules'] = $instance->affected_rows();
    }
}
