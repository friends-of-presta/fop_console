<?php

namespace FOP\Console\Commands;

use Shop;
use FOP\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
            ->setName('fop:console:shop-status')
            ->setDescription('Display shop(s) status(es)')
            ->setHelp('Use the "--all" option to display all shops information')
            ->addOption('all', '-a', InputOption::VALUE_NONE, 'Allows to display all shops information')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);


        if ($input->getOption('all')) {
            $io->title('Shops statuses report');
            $shops = $this->getContainer()->get('prestashop.core.admin.shop.repository')->findAll();
            $io->table(
                ['ID', 'Name', 'Theme', 'Activated?', 'Deleted?'],
                $this->formatShopsInformation($shops)
            );

            return 0;
        }

        $shop = new Shop($input->getOption('id_shop'));

        if (null !== $shop->id) {
            $io->title(sprintf('Information for shop "%s"', $shop->name));

            $io->table(
                ['ID', 'Name', 'Theme', 'Activated?', 'Deleted?'],
                [
                    [$shop->id, $shop->name, $shop->theme_name, $shop->active ? '✔' : '✘', $shop->deleted ? '✔' : '✘'],
                ]
            );

            return 0;
        }

        $io->error(sprintf('Information for Shop with the id "%s" not found: did you set a valid "id_shop" option?', $shop->id));
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
