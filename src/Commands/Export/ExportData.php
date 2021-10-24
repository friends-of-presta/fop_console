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

namespace FOP\Console\Commands\Export;

use Customer;
use FOP\Console\Command;
use Order;
use Product;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This command is an exporter.
 */
final class ExportData extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('fop:export:data')
            ->setAliases(['fop:export'])
            ->setDescription('Allows to export data in XML')
            ->setHelp('This command allows you to export most of your data in XML')
            ->addArgument('model', InputArgument::OPTIONAL, 'The Object Model to export', 'Product')
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'The limit if any, default to 100', 100)
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $objectModel = $input->getArgument('model');
        $limit = (int) $input->getOption('limit');

        switch ($objectModel) {
            case 'Product':
                $objects = Product::getProducts(1, 0, $limit, 'id_product', 'ASC');
                break;

            case 'Customer':
                $objects = Customer::getCustomers();
                break;

            case 'Order':
                $objects = Order::getOrdersWithInformations();
                break;

            default:
                $objects = [];
        }

        $serializer = $this->getContainer()->get('serializer');

        $this->io->text($serializer->serialize([strtolower($objectModel) => $objects], 'xml', ['xml_format_output' => true]));

        return 0;
    }
}
