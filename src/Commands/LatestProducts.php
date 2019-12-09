<?php
/**
 * 2019-present Friends of Presta community
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License
 * that is bundled with this package in the file LICENSE
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/MIT
 *
 * @author Friends of Presta community
 * @copyright 2019-present Friends of Presta community
 * @license https://opensource.org/licenses/MIT MIT
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
    protected function configure()
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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $products = Product::getNewProducts(1);

        $io = new SymfonyStyle($input, $output);
        $io->title('Legacy Latest Products listing');

        $io->table(
            ['ID', 'Name', 'Quantity', 'Price', 'Activated?'],
            $this->formatProductInformation($products)
        );
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
