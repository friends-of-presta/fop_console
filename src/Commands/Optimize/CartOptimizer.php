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

class CartOptimizer extends Command
{
    private $from_date;
    private $logs;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('fop:optimize:cart')
            ->setDescription('Clear and optimize your Prestashop')
            ->setHelp('Clear your Prestashop from useless row in cart table');

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
        $from_date = $input->getOption('from-date') ?? $helper->ask($input, $output, new Question('<question>Remove abandoned cart since ? Default 1 month, type date format "Y-m-d" to override.</question>'));

        if (!$this->checkDate($from_date)) {
            $io->error('Incorrect date parameter. Please type a date from format Y-m-d. Date given : ' . $from_date);
        }

        $this->clearCart();

        if ($this->logs['cart'] === 0) {
            $io->success('Cart already clean. No need more.');
        } else {
            $io->success('Cart clear successfully ! We delete ' . $this->logs['cart'] . ' cart(s) !');
        }
    }

    /**
     * {@inheritdoc}
     */
    private function clearCart()
    {
        $instance = Db::getInstance();

        $instance->delete('cart', 'id_cart NOT IN (SELECT id_cart FROM `'._DB_PREFIX_.'orders`) AND date_add < "' . pSQL($this->from_date) . '"');
        $this->logs['cart'] = $instance->affected_rows();
    }

    /**
     * @param $date
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