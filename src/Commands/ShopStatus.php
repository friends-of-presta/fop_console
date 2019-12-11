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
use Shop;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * This command display Shop status.
 */
final class ShopStatus extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('fop:shop-status')
            ->setDescription('Display shops statuses')
            ->addArgument('id_shop', InputArgument::OPTIONAL, 'Specify an id_shop')
            ->addArgument('action', InputArgument::OPTIONAL, 'enable or disable');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $id_shop = (int) $input->getArgument('id_shop');
        $action = $input->getArgument('action');

        if (!$id_shop) {
            $io->title('Shops statuses report');
            $shops = $this->getContainer()->get('prestashop.core.admin.shop.repository')->findAll();
            $io->table(
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
                    $io->text('Shop '.$shop->id.' enabled');
                    return 0;
                } elseif ($action == 'disable') {
                    $shop->active = false;
                    $shop->save();
                    $io->text('Shop '.$shop->id.' disabled');
                    return 0;
                } else {
                    $io->title(sprintf('Information for shop "%s"', $shop->name));

                    $io->table(
                        ['ID', 'Name', 'Theme', 'Activated?', 'Deleted?'],
                        [
                            [$shop->id, $shop->name, $shop->theme_name, $shop->active ? '✔' : '✘', $shop->deleted ? '✔' : '✘'],
                        ]
                    );

                    return 0;
                }
            }
            $io->error(sprintf('Information for Shop with the id "%s" not found: did you set a valid "id_shop" ?', $id_shop));
        }
    }

    /**
     * @param array $shops the list of the shops
     *
     * @return array
     */
    private function formatShopsInformation(array $shops)
    {
        $shopsInformation = [];
        /** @var Shop $shop */
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
