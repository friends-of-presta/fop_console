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
 */

namespace FOP\Console\Commands;

use FOP\Console\Command;
use Product;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * This command display common information the latest products.
 */
final class LatestProducts extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('fop:latest-products')
            ->setDescription('Displays the latest products')
            ->setHelp('This command allows you to display the latest products')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $products = Product::getNewProducts(1);

        $io = new SymfonyStyle($input, $output);
        $io->title('Legacy Latest Products listing');

        $io->table(
            ['ID', 'Name', 'Quantity', 'Price', 'Activated?'],
            $this->formatProductInformation($products)
        );

        return 0;
    }

    /**
     * @param array $products the list of the products
     *
     * @return array
     */
    private function formatProductInformation(array $products)
    {
        $productsInformation = [];
        /** @var Product $product */
        foreach ($products as $product) {
            $productsInformation[] = [
                $product['id_product'],
                $product['name'],
                $product['quantity'],
                $product['price'],
                $product['active'] ? '✔' : '✘',
            ];
        }

        return $productsInformation;
    }
}
