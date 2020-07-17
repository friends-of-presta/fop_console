<?php

namespace FOP\Console\Commands;

use FOP\Console\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ThemeResetLayout extends Command
{
    const ID_SHOP_ARGUMENT = 'id_shop';
    const COMMAND_FAILURE = 1;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('fop:theme-reset')
            ->setDescription('Reset current theme layout')
            ->setHelp('Disable & re-enable theme configuration')
            ->addArgument('theme', InputArgument::OPTIONAL, 'Theme on which the action will be executed')
            ->addOption(self::ID_SHOP_ARGUMENT, null, InputOption::VALUE_OPTIONAL, 'Shop on which the action will be executed');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $theme = $input->getArgument('theme');
        $container = $this->getContainer();
        $shopService = $container->get('prestashop.adapter.shop.context');
        $shops = $shopService->getShops();

        // Have more than 1 shop without previously select a shop in input option
        if (count($shops) > 1 && $input->getOption(self::ID_SHOP_ARGUMENT) === null) {
            $io->error('Your Prestashop installation seems to be a multistore, please define the --' . self::ID_SHOP_ARGUMENT . ' argument');

            $table = new Table($output);
            $table
                ->setHeaders(['id_shop', 'Shop name'])
                ->setRows(array_map(function ($shop) {
                    return [$shop['id_shop'], $shop['name']];
                }, $shops))
                ->render();

            return self::COMMAND_FAILURE;
        } elseif (count($shops) > 1 && $shopId = $input->getOption(self::ID_SHOP_ARGUMENT)) {
            if ($shop = $this->findShopById($shopService->getContextShopId()) === false) {
                $io->error('An error occurred while selecting shop');

                return self::COMMAND_FAILURE;
            }
        } else {
            $shop = $this->findShopById(1);
        }

        // If no theme defined in argument, retrieve the current active one
        if (null === $theme) {
            $theme = $shop['theme_name'];
        }

        $themeManager = $this->getContainer()->get('prestashop.core.addon.theme.theme_manager');

        try {
            $themeManager->enable($theme, 1);
            $themeManager->disable($theme);
        } catch (\Exception $e) {
            $io->error(sprintf('The selected theme "%s" is invalid', $theme));

            return self::COMMAND_FAILURE;
        }

        $io->success(sprintf('Theme "%s" has been successfully reset.', $theme));
    }

    private function findShopById(int $shopId)
    {
        $container = $this->getContainer();
        $shopService = $container->get('prestashop.adapter.shop.context');
        $shops = $shopService->getShops();

        foreach ($shops as $shop) {
            if ($shop['id_shop'] == $shopId) {
                return $shop;
            }
        }

        return false;
    }
}
