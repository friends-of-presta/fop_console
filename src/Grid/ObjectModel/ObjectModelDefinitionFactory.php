<?php

namespace FOP\Console\Grid\ObjectModel;

use PrestaShop\PrestaShop\Core\Grid\Column\ColumnCollectionInterface;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\DateTimeColumn;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\BulkActionColumn;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\DataColumn;
use PrestaShop\PrestaShop\Core\Grid\Definition\Factory\GridDefinitionFactoryInterface;
use PrestaShop\PrestaShop\Core\Grid\Column\ColumnCollection;
use PrestaShop\PrestaShop\Core\Grid\Filter\FilterCollection;
use PrestaShop\PrestaShop\Core\Grid\Action\GridActionCollection;
use PrestaShop\PrestaShop\Core\Grid\Action\Bulk\BulkActionCollection;
use PrestaShop\PrestaShop\Core\Grid\Definition\GridDefinition;
use ObjectModel;
use Symfony\Component\Finder\Finder;

/**
 * Creates a Grid valid definition from an Object Model.
 */
class ObjectModelDefinitionFactory implements GridDefinitionFactoryInterface
{
    /**
     * @var string
     */
    private $objectModelClass = null;

    /**
     * {@inheritDoc}
     */
    public function getDefinition()
    {
        if ($this->objectModelClass === null) {
            throw new \PrestaShopException('Set the object model using ``setObjectModelClass`` function.');
        }

        $id = $this->objectModelClass;
        $name = $this->objectModelClass;

        $columns = $this->getColumns();
        $filters = new FilterCollection();
        $actions = new GridActionCollection();
        $bulkActions = new BulkActionCollection();

        return new GridDefinition($id, $name, $columns, $filters, $actions, $bulkActions);
    }

    private function getColumns(): ColumnCollectionInterface
    {
        $columnCollection = new ColumnCollection();
        $objectModelDefinition = $this->objectModelClass::$definition;

        foreach ($objectModelDefinition['fields'] as $name => $field) {
            // guess Column Type from Definition Type

            switch ($field['type']) {
                case ObjectModel::TYPE_INT:
                case ObjectModel::TYPE_STRING:
                case ObjectModel::TYPE_FLOAT:
                case ObjectModel::TYPE_HTML:
                    if ($name === $objectModelDefinition['primary']) {
                        $type = BulkActionColumn::class;
                    } else {
                        $type = DataColumn::class;
                    }
                    break;
                case ObjectModel::TYPE_DATE:
                    $type = DateTimeColumn::class;

                    break;

                default:
                    $type = DataColumn::class;
            }

            // @todo: manage action columns

            $column = new $type($name);
            $column->setName($name);

            switch ($type) {
                case DataColumn::class:
                case DateTimeColumn::class:
                    $column->setOptions([
                        'field' => $name,
                        'sortable' => false,
                    ]);

                    break;
                case BulkActionColumn::class:
                    $column->setOptions([
                        'bulk_field' => $name,
                    ]);

                    break;
                default:
            }

            $columnCollection->add($column);
        }

        return $columnCollection;
    }


    public function setObjectModelClass(string $objectModelClass)
    {
        // Have to patch this fuckin PS autoloader !
        $finder = new Finder();
        $finder->name($objectModelClass.".php")->files()->in(_PS_MODULE_DIR_);

        foreach ($finder as $file) {
            require_once($file->getRealPath());
        }

        $this->objectModelClass = $objectModelClass;

        return $this;
    }
}