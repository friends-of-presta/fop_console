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
use Shop;
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
            ->setHelp('This command :'
                . PHP_EOL . '   - Enable or disable a category.'
                . PHP_EOL . '   - Disable final categories without product.'
                . PHP_EOL . '   - Enable final categories with an active product.'
                . PHP_EOL . '   - This command DON\'T SUPPORT multi-shop.')
            ->addUsage('--exclude=[XX,YY,ZZ] (id_category separate by coma)')
            ->addArgument(
                'action',
                InputArgument::OPTIONAL,
                'Disable, Enable, Disable empty categories or Enable no empty categories ( possible values : ' . $this->getPossibleActions() . ') ',
                'status'
            )
            ->addOption('id_lang', null, InputOption::VALUE_OPTIONAL, 'Id lang')
            ->addOption('id_category', null, InputOption::VALUE_OPTIONAL)
            ->addOption('exclude', null, InputOption::VALUE_OPTIONAL, 'Ids Category to exclude')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force the command when the MultiShop is enable.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $force = $input->getOption('force');

        if (1 < Shop::getTotalShops(false)) {
            if (!$force) {
                $io->error('Actualy this command don\'t work with MultiShop.'
                . PHP_EOL . 'Use force (-f) option to run the command.');

                return 1;
            } else {
                $io->warning('MultiShop Enable, Force Mode');
            }
        }

        $action = $input->getArgument('action');
        $id_lang = (int) $input->getOption('id_lang') ?? Configuration::get('PS_LANG_DEFAULT');
        $exclude = $input->getOption('exclude') ? explode(',', $input->getOption('exclude')) : [];

        switch ($action) {
            case 'status':
                $categories = $this->getCategoriesToClean($id_lang, $action, $exclude);

                if (!$categories['empty'] && !$categories['noempty']) {
                    $io->title('No Categories to update');

                    return 0;
                }

                if ($categories['empty']) {
                    $io->title('The following categorie(s) are enabled but without product active');
                    $io->text(implode(' / ', $categories['empty']));
                    $io->text(' -- You can run `./bin/console fop:category disable-empty` to fix it');
                    $io->text(' -- If you want exclude categories you can add --exclude ID,ID2,ID3');
                }

                if ($categories['noempty']) {
                    $io->title('The following categorie(s) are disabled but with product active in the category');
                    $io->text(implode(' / ', $categories['noempty']));
                    $io->text(' -- You can run `./bin/console fop:category enable-noempty` to fix it');
                    $io->text(' -- If you want exclude categories you can add --exclude ID,ID2,ID3');
                }

                return 0;

                break;
            case 'disable-empty':
                try {
                    $categories = $this->getCategoriesToClean($id_lang, $action, $exclude);
                } catch (\Exception $e) {
                    $io->getErrorStyle()->error($e->getMessage());

                    return 1;
                }

                if (!$categories['empty']) {
                    $io->title('No Categories to update');

                    return 0;
                } else {
                    $io->title('The following categories have been disabled');
                    $io->text(implode(', ', $categories['empty']));

                    return 0;
                }

                break;
            case 'enable-noempty':
                try {
                    $categories = $this->getCategoriesToClean($id_lang, $action, $exclude);
                } catch (\Exception $e) {
                    $io->getErrorStyle()->error($e->getMessage());

                    return 1;
                }

                if (!$categories['noempty']) {
                    $io->title('No Categories to update');

                    return 0;
                } else {
                    $io->title('The following categories have been enabled');
                    $io->text(implode(', ', $categories['noempty']));

                    return 0;
                }

                break;
            case 'toggle':
                $helper = $this->getHelper('question');
                $id_category = $input->getOption('id_category') ?? $helper->ask($input, $output, new Question('<question>Wich id_category you want to toggle</question>'));
                if (!Category::categoryExists($id_category)) {
                    $io->error('Hum i don\'t think id_category ' . $id_category . ' exist');

                    return 1;
                }
                $category = new Category($id_category, $id_lang);

                if (0 === (int) $category->active) {
                    $category->active = 1;
                    if (!$category->update()) {
                        $io->error('Failed to update Category with ID : ' . $id_category);

                        return 1;
                    }

                    $io->success('The category : ' . $category->name . ' is now enabled.');

                    return 0;
                } else {
                    $category->active = 0;
                    if (!$category->update()) {
                        $io->error('Failed to update Category with ID : ' . $id_category);

                        return 1;
                    }

                    $io->success('The category : ' . $category->name . ' is now disabled.');

                    return 0;
                }

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
                            $categorieToCheck->active = 0;
                            if (!$categorieToCheck->update()) {
                                throw new \Exception('Failed to update Category : ' . $categorieToCheck->name);
                            }
                        }
                        $categoriesToDesactive[] = $categorieToCheck->name . ' (' . $categorie['id_category'] . ')';
                    } elseif ($NbProducts && 1 != $categorieToCheck->active) {
                        if ($action === 'enable-noempty') {
                            $categorieToCheck->active = 1;
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
