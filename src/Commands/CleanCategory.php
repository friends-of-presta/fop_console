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

declare(strict_types=1);

namespace FOP\Console\Commands;

use Category;
use Configuration;
use FOP\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class CleanCategory extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('fop:clean-category')
            ->setDescription('Active or desactive categorys depending on products')
            ->setHelp('This command desactive final categories witout product, and active final catgories with product')
            ->addUsage('--exclude=[XX,YY,ZZ] (id_category separate by coma)')
            ->addOption('id_lang', null, InputOption::VALUE_OPTIONAL, 'Id lang')
            ->addOption('exclude', null, InputOption::VALUE_OPTIONAL, 'Ids Category to exclude')
            ->addOption('active_only', null, InputOption::VALUE_OPTIONAL, 'Only active category')
            ->addOption('desactive_only', null, InputOption::VALUE_OPTIONAL, 'Only desactive category');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $id_lang = $input->getOption('id_lang') ?? Configuration::get('PS_LANG_DEFAULT');

        try {
            $exclude = $input->getOption('exclude') ? explode(',', $input->getOption('exclude')) : [];
            $desactivedCategories = [];
            $activedCategories = [];

            $categories = Category::getCategories($id_lang, false, false);

            foreach ($categories as $categorie) {
                if (!in_array($categorie['id_category'], $exclude)) {
                    if (!Category::getChildren($categorie['id_category'], $id_lang, false)) {
                        $categorieToCheck = new Category($categorie['id_category']);

                        $NbProducts = $categorieToCheck->getProducts($id_lang, 1, 1);

                        if (!$NbProducts && 0 != $categorieToCheck->active && !$input->getOption('active_only')) {
                            $categorieToCheck->active = 0;
                            if (!$categorieToCheck->update()) {
                                throw new \Exception('Failed to update Category : ' . $categorieToCheck->name[$id_lang]);
                            }
                            $desactivedCategories[] = $categorieToCheck->name[$id_lang];
                        } elseif ($NbProducts && 1 != $categorieToCheck->active && !$input->getOption('desactive_only')) {
                            $categorieToCheck->active = 1;
                            if (!$categorieToCheck->update()) {
                                throw new \Exception('Failed to update Category : ' . $categorieToCheck->name[$id_lang]);
                            }
                            $activedCategories[] = $categorieToCheck->name[$id_lang];
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $io->getErrorStyle()->error($e->getMessage());

            return 1;
        }

        if (!$desactivedCategories && !$activedCategories) {
            $io->title('No Categories updated');

            return 0;
        }

        if ($desactivedCategories) {
            $io->title('Categories desactived');
            $io->text(implode(',', $desactivedCategories));
        }

        if ($activedCategories) {
            $io->title('Categories actived');
            $io->text(implode(',', $activedCategories));
        }

        return 0;
    }
}
