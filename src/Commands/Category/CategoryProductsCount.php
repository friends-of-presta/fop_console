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
use Exception;
use FOP\Console\Command;
use PBergman\Console\Helper\TreeHelper;
use PrestaShop\PrestaShop\Adapter\LegacyContext;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class CategoryProductsCount extends Command
{
    /**
     * @var int
     */
    private $languageId;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('fop:category:products-count')
            ->setDescription('Get the number of products for category and its children (root category by default)')
            ->addUsage('2')
            ->addUsage('--output=filename.csv')
            ->addArgument('id-category', InputArgument::OPTIONAL, 'Category id')
            ->addOption('id-lang', 'l', InputOption::VALUE_REQUIRED, 'Language id')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Write the output to a csv file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $categoryId = $input->getArgument('id-category')
            ? intval($input->getArgument('id-category'))
            : Category::getRootCategory()->id
        ;

        $this->languageId = intval($input->getOption('id-lang'));
        if (!$this->languageId) {
            try {
                /** @var LegacyContext|null */
                $legacyContext = $this->getContainer()->get('prestashop.adapter.legacy.context');

                if ($legacyContext) {
                    $this->languageId = $legacyContext->getContext()->language->id;
                }
            } catch (Exception $e) {
            }
        }

        if (!$this->languageId) {
            $this->io->error("Language id can't be retrieved.");

            return 0;
        }

        $outputPathname = $input->getOption('output');

        $nestedCategories = Category::getNestedCategories($categoryId);
        if (!is_array($nestedCategories) || !key_exists($categoryId, $nestedCategories)) {
            $this->io->error('No categories to display.');

            return 0;
        }

        $rootCategory = $nestedCategories[$categoryId];

        if (!$outputPathname) {
            $tree = new TreeHelper();
            $tree->addArray($this->formatCategoriesToTree($rootCategory));

            $tree->printTree($output);
        } else {
            $fp = fopen($outputPathname, 'w');

            $maxDepth = $this->findMaxDepth($rootCategory);
            fputcsv($fp, array_merge(['ID', 'Name'], array_fill(0, $maxDepth - 1, ' '), ['Products']));

            $categoriesCSV = $this->formatCategoriesToCSV($rootCategory, $maxDepth);
            foreach ($categoriesCSV as $fields) {
                fputcsv($fp, $fields);
            }

            fclose($fp);
        }

        return 1;
    }

    /**
     * @param array<string, mixed> $category
     *
     * @return array<string|array>
     */
    private function formatCategoriesToTree(array $category)
    {
        $categoryObject = new Category((int) $category['id_category'], $this->languageId);
        $categoryLabel = $this->getCategoryLabel(
            $categoryObject->name,
            $this->getCategoryProductsCount($categoryObject)
        );

        if (empty($category['children'])) {
            return [$categoryLabel => []];
        }

        $categoriesTree[$categoryLabel] = [];
        foreach ($category['children'] as $child) {
            $categoriesTree[$categoryLabel] = array_merge(
                $categoriesTree[$categoryLabel],
                $this->formatCategoriesToTree($child)
            );
        }

        return $categoriesTree;
    }

    /**
     * @param array<string, mixed> $category
     *
     * @return array<string[]>
     */
    private function formatCategoriesToCSV(array $category, int $maxDepth)
    {
        $categoryId = (int) $category['id_category'];
        $categoryObject = new Category($categoryId, $this->languageId);
        $productsCount = $this->getCategoryProductsCount($categoryObject);
        $categoryLabel = $this->getCategoryLabel($categoryObject->name, $productsCount);
        $depth = $categoryObject->level_depth;

        $categoriesCSV = [array_merge(
            [$categoryId],
            array_fill(0, $depth - 1, ' '),
            [$categoryLabel],
            array_fill(0, max(0, $maxDepth - $depth), ' '),
            [$productsCount]
        )];

        if (key_exists('children', $category)) {
            foreach ($category['children'] as $child) {
                $categoriesCSV = array_merge(
                    $categoriesCSV,
                    $this->formatCategoriesToCSV($child, $maxDepth)
                );
            }
        }

        return $categoriesCSV;
    }

    /**
     * @param array<string, mixed> $category
     */
    public function findMaxDepth(array $category): int
    {
        if (key_exists('children', $category)) {
            return max(array_map([$this, 'findMaxDepth'], $category['children']));
        }

        return (int) $category['level_depth'];
    }

    public function getCategoryProductsCount(Category $category): int
    {
        return intval($category->getProducts($this->languageId, 0, 0, null, null, true));
    }

    public function getCategoryLabel(string $name, int $productsCount)
    {
        return "$name ($productsCount)";
    }
}
