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

declare(strict_types=1);

namespace FOP\Console\Commands\Category;

use Category;
use Configuration;
use FOP\Console\Command;
use Shop;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

final class CategoryClean extends Command
{
    /**
     * @var array possible command
     */
    public const ALLOWED_COMMAND = ['status', 'toggle', 'enable-no-empty', 'disable-empty'];

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('fop:category:clean')
            ->setAliases(['fop:category'])
            ->setDescription('Manage your categories, this command don\'t support multishop')
            ->setHelp('This command :'
                . PHP_EOL . '   - Enable or disable a category.'
                . PHP_EOL . '   - Disable final categories without product.'
                . PHP_EOL . '   - Enable final categories with an active product.'
                . PHP_EOL . '   - This command DON\'T SUPPORT multi-shop.')
            ->addUsage('./bin/console fop:category:clean toggle -c 3 ( enable or disable the category with id 3')
            ->addUsage('--exclude=[XX,YY,ZZ] (id-category separate by coma)')
            ->addArgument(
                'action',
                InputArgument::OPTIONAL,
                'Disable, Enable, Disable empty categories or Enable no empty categories ( possible values : ' . $this->getPossibleActions() . ') ',
                'status'
            )
            ->addOption('id-lang', null, InputOption::VALUE_OPTIONAL, 'Id lang')
            ->addOption('id-category', 'c', InputOption::VALUE_OPTIONAL)
            ->addOption('exclude', null, InputOption::VALUE_OPTIONAL, 'Ids Category to exclude')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force the command when the MultiShop is enable.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $force = $input->getOption('force');

        if (1 < Shop::getTotalShops(false)) {
            if (!$force) {
                $this->io->error('Currently this command don\'t work with MultiShop.'
                . PHP_EOL . 'Use force (-f) option to run the command.');

                return 1;
            } else {
                $this->io->warning('MultiShop Enable, Force Mode');
            }
        }

        $action = $input->getArgument('action');
        $id_lang = $input->getOption('id-lang') ? (int) $input->getOption('id-lang') : (int) Configuration::get('PS_LANG_DEFAULT');
        $exclude = $input->getOption('exclude') ? explode(',', $input->getOption('exclude')) : [];

        switch ($action) {
            case 'status':
                $categories = $this->getCategoriesToClean($id_lang, $action, $exclude);

                if (!$categories['empty'] && !$categories['noempty']) {
                    $this->io->title('All categories with active product are enable and all categories without product active are disable.');

                    return 0;
                }

                if ($categories['empty']) {
                    $this->io->title('The following category(s) are enabled but without active product');
                    $this->io->text(implode(' / ', $categories['empty']));
                    $this->io->text(' -- You can run `./bin/console fop:category disable-empty` to fix it');
                    $this->io->text(' -- If you want exclude categories you can add --exclude ID,ID2,ID3');
                }

                if ($categories['noempty']) {
                    $this->io->title('The following categories are disabled but contain active products.');
                    $this->io->text(implode(' / ', $categories['noempty']));
                    $this->io->text(' -- You can run `./bin/console fop:category enable-no-empty` to fix it');
                    $this->io->text(' -- If you want exclude categories you can add --exclude ID,ID2,ID3');
                }

                return 0;

            case 'disable-empty':
                try {
                    $categories = $this->getCategoriesToClean($id_lang, $action, $exclude);
                } catch (\Exception $e) {
                    $this->io->getErrorStyle()->error($e->getMessage());

                    return 1;
                }

                if (!$categories['empty']) {
                    $this->io->title('All categories without product active are disable.');

                    return 0;
                } else {
                    $this->io->title('The following categories have been disabled');
                    $this->io->text(implode(', ', $categories['empty']));

                    return 0;
                }

            case 'enable-no-empty':
                try {
                    $categories = $this->getCategoriesToClean($id_lang, $action, $exclude);
                } catch (\Exception $e) {
                    $this->io->getErrorStyle()->error($e->getMessage());

                    return 1;
                }

                if (!$categories['noempty']) {
                    $this->io->title('All categories with active product are enable.');

                    return 0;
                } else {
                    $this->io->title('The following categories have been enabled');
                    $this->io->text(implode(', ', $categories['noempty']));

                    return 0;
                }

            case 'toggle':
                $helper = $this->getHelper('question');
                $id_category = $input->getOption('id-category') ?? $helper->ask($input, $output, new Question('<question>Wich id_category you want to toggle</question>'));
                if (!Category::categoryExists($id_category)) {
                    $this->io->error('Hum i don\'t think id_category ' . $id_category . ' exist');

                    return 1;
                }
                $category = new Category($id_category, $id_lang);

                if (0 === (int) $category->active) {
                    $category->active = true;
                    if (!$category->update()) {
                        $this->io->error('Failed to update Category with ID : ' . $id_category);

                        return 1;
                    }

                    $this->io->success('The category : ' . $category->name . ' is now enabled.');

                    return 0;
                } else {
                    $category->active = false;
                    if (!$category->update()) {
                        $this->io->error('Failed to update Category with ID : ' . $id_category);

                        return 1;
                    }

                    $this->io->success('The category : ' . $category->name . ' is now disabled.');

                    return 0;
                }

                // no break
            default:
                $this->io->error("Action $action not allowed." . PHP_EOL . 'Possible actions : ' . $this->getPossibleActions());

                return 1;
        }
    }

    private function getPossibleActions(): string
    {
        return implode(',', self::ALLOWED_COMMAND);
    }

    /**
     * @param int $id_lang
     * @param string $action
     * @param array $exclude
     *
     * @return array
     */
    private function getCategoriesToClean(int $id_lang, string $action, array $exclude): array
    {
        $categoriesToActive = [];
        $categoriesToDesactive = [];
        $categories = Category::getCategories($id_lang, false, false);
        $excludeDefault = [Configuration::get('PS_ROOT_CATEGORY'), Configuration::get('PS_HOME_CATEGORY')];

        foreach ($categories as $categorie) {
            if (!in_array($categorie['id_category'], $exclude) && !in_array($categorie['id_category'], $excludeDefault)) {
                if (!Category::getChildren($categorie['id_category'], $id_lang, false)) {
                    $categorieToCheck = new Category($categorie['id_category'], $id_lang);
                    $NbProducts = $categorieToCheck->getProducts($id_lang, 1, 1);

                    if (!$NbProducts && 1 === (int) $categorieToCheck->active) {
                        if ($action === 'disable-empty') {
                            $categorieToCheck->active = false;
                            if (!$categorieToCheck->update()) {
                                throw new \Exception('Failed to update Category : ' . $categorieToCheck->name);
                            }
                        }
                        $categoriesToDesactive[] = $categorieToCheck->name . ' (' . $categorie['id_category'] . ')';
                    } elseif ($NbProducts && 1 != $categorieToCheck->active) {
                        if ($action === 'enable-no-empty') {
                            $categorieToCheck->active = true;
                            if (!$categorieToCheck->update()) {
                                throw new \Exception('Failed to update Category : ' . $categorieToCheck->name);
                            }
                        }
                        $categoriesToActive[] = $categorieToCheck->name . ' (' . $categorie['id_category'] . ')';
                    }
                }
            }
        }

        $categories = ['empty' => $categoriesToDesactive, 'noempty' => $categoriesToActive];

        return $categories;
    }
}
