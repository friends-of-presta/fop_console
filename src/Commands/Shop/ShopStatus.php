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

namespace FOP\Console\Commands\Shop;

use FOP\Console\Command;
use Shop;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This command display Shop status.
 */
final class ShopStatus extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('fop:shop:status')
            ->setAliases(['fop:shop-status'])
            ->setDescription('Display shops statuses')
            ->addArgument('id_shop', InputArgument::OPTIONAL, 'Specify an id_shop')
            ->addArgument('action', InputArgument::OPTIONAL, 'enable or disable');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id_shop = (int) $input->getArgument('id_shop');
        $action = $input->getArgument('action');

        if (!$id_shop) {
            $this->io->title('Shops statuses report');
            $shops = $this->getContainer()->get('prestashop.core.admin.shop.repository')->findAll();
            $this->io->table(
                ['ID', 'Name', 'Theme', 'Activated?', 'Deleted?'],
                $this->formatShopsInformation($shops)
            );

            return 0;
        } else {
            $shop = new Shop($id_shop);
            if (null !== $shop->id) {
                if ($action == 'enable') {
                    $shop->active = true;
                    $shop->save();
                    $this->io->text('Shop ' . $shop->id . ' enabled');

                    return 0;
                } elseif ($action == 'disable') {
                    $shop->active = false;
                    $shop->save();
                    $this->io->text('Shop ' . $shop->id . ' disabled');

                    return 0;
                } else {
                    $this->io->title(sprintf('Information for shop "%s"', $shop->name));

                    $this->io->table(
                        ['ID', 'Name', 'Theme', 'Activated?', 'Deleted?'],
                        [
                            [$shop->id, $shop->name, $shop->theme_name, $shop->active ? '✔' : '✘', $shop->deleted ? '✔' : '✘'],
                        ]
                    );

                    return 0;
                }
            }
            $this->io->error(sprintf('Information for Shop with the id "%s" not found: did you set a valid "id_shop" ?', $id_shop));

            return 0;
        }
    }

    /**
     * @param array $shops the list of the shops
     *
     * @return array
     */
    private function formatShopsInformation(array $shops): array
    {
        $shopsInformation = [];
        /** @var \PrestaShopBundle\Entity\Shop $shop */
        foreach ($shops as $shop) {
            $shopsInformation[] = [
                $shop->getId(),
                $shop->getName(),
                $shop->getThemeName(),
                $shop->getActive() ? '✔' : '✘',
                $shop->getDeleted() ? '✔' : '✘',
            ];
        }

        return $shopsInformation;
    }
}
