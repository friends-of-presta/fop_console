<?php

namespace FOP\Console\Commands;

use Customer;
use FOP\Console\Command;
use Order;
use Product;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * This command is an exporter.
 */
final class Export extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('fop:console:export')
            ->setDescription('Allows to export data in XML')
            ->setHelp('This command allows you to export most of your data in XML')
            ->addArgument('model', InputArgument::OPTIONAL, 'The Object Model to export', 'Product')
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'The limit if any, default to 100', 100)
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $objectModel = $input->getArgument('model');
        $limit = $input->getOption('limit');

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

        $io->text($serializer->serialize([strtolower($objectModel) => $objects], 'xml', ['xml_format_output' => true]));
    }
}
