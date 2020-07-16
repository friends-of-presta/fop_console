<?php

namespace FOP\Console\Commands;

use FOP\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ThemeResetLayout extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('fop:theme-reset')
            ->setDescription('Reset current theme layout')
            ->setHelp('Disable & re-enable theme configuration')
            ->addArgument('theme', InputArgument::OPTIONAL, 'Theme on which the action will be executed');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $theme = $input->getArgument('theme');
        $container = $this->getContainer();

        if (!$theme) {
            $io->text('(?) No theme selected, trying to select current theme...');
            $shopService = $container->get('prestashop.adapter.shop.context');
            $shops = $shopService->getShops();

            // Retrieve current shop
            $shopId = $shopService->getContextShopId();

            if (isset($shops[$shopId]) && is_array($shops[$shopId])) {
                $shop = $shops[$shopId];

                $theme = $shop['theme_name'];
                $io->title('resetting current theme : ' . $theme);
            } else {
                $io->error('An error occurred while selecting current theme');

                return 1;
            }
        }

        $themeManager = $this->getContainer()->get('prestashop.core.addon.theme.theme_manager');

        try {
            $themeManager->enable($theme, 1);
            $themeManager->disable($theme);
        } catch (\Exception $e) {
            $io->error(sprintf('The selected theme "%s" is invalid', $theme));

            return 1;
        }

        $io->success(sprintf('Theme "%s" has been successfully reset.', $theme));
    }
}
