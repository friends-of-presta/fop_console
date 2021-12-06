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

namespace FOP\Console\Commands\Theme;

use FOP\Console\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class ThemeResetLayout extends Command
{
    public const ID_SHOP_ARGUMENT = 'id_shop';
    public const COMMAND_FAILURE = 1;

    protected function configure(): void
    {
        $this
            ->setName('fop:theme:reset-layout')
            ->setAliases(['fop:theme-reset'])
            ->setDescription('Reset current theme layout')
            ->setHelp('Disable & re-enable theme configuration')
            ->addArgument('theme', InputArgument::OPTIONAL, 'Theme on which the action will be executed')
            ->addOption(self::ID_SHOP_ARGUMENT, null, InputOption::VALUE_OPTIONAL, 'Shop on which the action will be executed');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $theme = $input->getArgument('theme');
        $container = $this->getContainer();
        $shopService = $container->get('prestashop.adapter.shop.context');
        $shops = $shopService->getShops();

        // Have more than 1 shop without previously selected an id_shop in input option
        if (count($shops) > 1 && $input->getOption(self::ID_SHOP_ARGUMENT) === null) {
            $this->io->error('Your Prestashop installation seems to be a multistore, please define the --' . self::ID_SHOP_ARGUMENT . ' argument');

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
                $this->io->error('An error occurred while selecting shop');

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
            $this->io->error(sprintf('The selected theme "%s" is invalid', $theme));

            return self::COMMAND_FAILURE;
        }

        $this->io->success(sprintf('Theme "%s" has been successfully reset.', $theme));

        return 0;
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
