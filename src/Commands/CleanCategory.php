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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

final class CleanCategory extends Command
{
    /**
     * @var array possible command
     */
    const ALLOWED_COMMAND = ['status', 'toggle', 'enable-noempty', 'disable-empty'];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('fop:category')
            ->setDescription('Manage your categories, this command don\'t support multishop')
            ->setHelp('This command enable or disable a category or disable final categories without product or enable final categories with an active product. This command DON\'T SUPPORT multi-shop ')
            ->addUsage('--exclude=[XX,YY,ZZ] (id_category separate by coma)')
            ->addArgument(
                'action',
                InputArgument::OPTIONAL,
                'Disable, Enable, Disable empty categories or Enable no empty categories ( possible values : ' . $this->getPossibleActions() . ') ',
                'status'
            )
            ->addOption('id_lang', null, InputOption::VALUE_OPTIONAL, 'Id lang')
            ->addOption('id_category', null, InputOption::VALUE_OPTIONAL)
            ->addOption('exclude', null, InputOption::VALUE_OPTIONAL, 'Ids Category to exclude');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $action = $input->getArgument('action');
        $categoriesToActive = [];
        $categoriesToDesactive = [];
        $id_lang = $input->getOption('id_lang') ?? Configuration::get('PS_LANG_DEFAULT');
        $exclude = $input->getOption('exclude') ? explode(',', $input->getOption('exclude')) : [];

        switch ($action) {
            case 'status':
                try {
                    $categories = Category::getCategories($id_lang, false, false);

                    foreach ($categories as $categorie) {
                        if (!in_array($categorie['id_category'], $exclude)) {
                            if (!Category::getChildren($categorie['id_category'], $id_lang, false)) {
                                $categorieToCheck = new Category($categorie['id_category']);

                                $NbProducts = $categorieToCheck->getProducts($id_lang, 1, 1);

                                if (!$NbProducts && 0 != $categorieToCheck->active) {
                                    $categoriesToDesactive[] = $categorieToCheck->name[$id_lang];
                                } elseif ($NbProducts && 1 != $categorieToCheck->active) {
                                    $categoriesToActive[] = $categorieToCheck->name[$id_lang];
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $io->getErrorStyle()->error($e->getMessage());

                    return 1;
                }

                if (!$categoriesToDesactive && !$categoriesToActive) {
                    $io->title('No Categories to updated');

                    return 0;
                }

                if ($categoriesToDesactive) {
                    $io->title('The following categorie(s) are enabled but without product active');
                    $io->text(implode(' / ', $categoriesToDesactive));
                    $io->text(' -- You can run `./bin/console fop:category disable-empty` to fix it');
                    $io->text(' -- If you want exclude categories you can add --exclude ID,ID2,ID3');
                }

                if ($categoriesToActive) {
                    $io->title('The following categorie(s) are disabled but with product active in the category');
                    $io->text(implode(' / ', $categoriesToActive));
                    $io->text(' -- You can run `./bin/console fop:category enable-noempty` to fix it');
                    $io->text(' -- If you want exclude categories you can add --exclude ID,ID2,ID3');
                }

                return 0;

                break;
            case 'disable-empty':
                try {
                    $categories = Category::getCategories($id_lang, false, false);

                    foreach ($categories as $categorie) {
                        if (!in_array($categorie['id_category'], $exclude)) {
                            if (!Category::getChildren($categorie['id_category'], $id_lang, false)) {
                                $categorieToCheck = new Category($categorie['id_category']);

                                $NbProducts = $categorieToCheck->getProducts($id_lang, 1, 1);

                                if (!$NbProducts && 0 != $categorieToCheck->active) {
                                    $categorieToCheck->active = 0;
                                    if (!$categorieToCheck->update()) {
                                        throw new \Exception('Failed to update Category : ' . $categorieToCheck->name[$id_lang]);
                                    }
                                    $categoriesToDesactive[] = $categorieToCheck->name[$id_lang];
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $io->getErrorStyle()->error($e->getMessage());

                    return 1;
                }

                if (!$categoriesToDesactive) {
                    $io->title('No Categories to updated');

                    return 0;
                }

                if ($categoriesToDesactive) {
                    $io->title('The following categories have been disabled');
                    $io->text(implode(',', $categoriesToDesactive));
                }

                return 0;
                break;
            case 'enable-noempty':
                try {
                    $categories = Category::getCategories($id_lang, false, false);

                    foreach ($categories as $categorie) {
                        if (!in_array($categorie['id_category'], $exclude)) {
                            if (!Category::getChildren($categorie['id_category'], $id_lang, false)) {
                                $categorieToCheck = new Category($categorie['id_category']);

                                $NbProducts = $categorieToCheck->getProducts($id_lang, 1, 1);

                                if ($NbProducts && 1 != $categorieToCheck->active) {
                                    $categorieToCheck->active = 1;
                                    if (!$categorieToCheck->update()) {
                                        throw new \Exception('Failed to update Category : ' . $categorieToCheck->name[$id_lang]);
                                    }
                                    $categoriesToActive[] = $categorieToCheck->name[$id_lang];
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $io->getErrorStyle()->error($e->getMessage());

                    return 1;
                }

                if (!$categoriesToActive) {
                    $io->title('No Categories to updated');

                    return 0;
                }

                if ($categoriesToActive) {
                    $io->title('The following categories have been enabled');
                    $io->text(implode(',', $categoriesToActive));
                }

                return 0;
                break;
            case 'toggle':
                $helper = $this->getHelper('question');
                $id_caterory = $input->getOption('id_category') ?? $helper->ask($input, $output, new Question('<question>Wich id_category you want to toggle</question>'));
                if (!Category::categoryExists($id_caterory)) {
                    $io->error('Hum i don\'t think id_category : ' . $id_caterory . ' exist');

                    return 1;
                }
                $category = new Category($id_caterory);

                if (0 == $category->active) {
                    $category->active = 1;
                    if (!$category->update()) {
                        $io->error('Failed to update Category with ID : ' . $id_caterory);
                    }
                    $io->title('The category : ' . $category->name[$id_lang] . ' is now enabled');

                    return 0;
                } else {
                    $category->active = 0;
                    if (!$category->update()) {
                        $io->error('Failed to update Category with ID : ' . $id_caterory);
                    }
                    $io->title('The category : ' . $category->name[$id_lang] . ' is now disaled');

                    return 0;
                }

                return 1;

                break;
            default:
                $io->error("Action $action not allowed." . PHP_EOL . 'Possible actions : ' . $this->getPossibleActions());

                return 1;
        }
    }

    private function getPossibleActions(): string
    {
        return implode(',', self::ALLOWED_COMMAND);
    }
}
