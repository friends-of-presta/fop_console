<?php

namespace FOP\Console\Commands;

use Product;
use FOP\Console\Command;
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
            ->setName('fop:console:latest-products')
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
