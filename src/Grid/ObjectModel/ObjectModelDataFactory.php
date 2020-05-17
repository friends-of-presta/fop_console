<?php

namespace FOP\Console\Grid\ObjectModel;

use PrestaShop\PrestaShop\Core\Grid\Data\Factory\GridDataFactoryInterface;
use PrestaShop\PrestaShop\Core\Grid\Data\GridData;
use PrestaShop\PrestaShop\Core\Grid\Record\RecordCollection;
use PrestaShop\PrestaShop\Core\Grid\Search\SearchCriteriaInterface;
use PrestaShopCollection;

class ObjectModelDataFactory implements GridDataFactoryInterface
{
    /**
     * @var string
     */
    private $objectModelClass = null;

    /**
     * {@inheritdoc}
     *
     * @todo: manage the Search Criteria
     */
    public function getData(SearchCriteriaInterface $searchCriteria)
    {
        if ($this->objectModelClass === null) {
            throw new \PrestaShopException('Set the object model using ``setObjectModelClass`` function.');
        }

        ob_start();
        $results = (new PrestaShopCollection($this->objectModelClass))->getAll(true);
        $resultsAsArray = [];

        foreach ($results as $objectModel) {
            $vars = get_object_vars($objectModel);
            $objectModelArray = [];
            foreach ($vars as $key => $value) {
                $objectModelArray[ltrim($key, '_')] = $value;
            }

            $resultsAsArray[] = $objectModelArray;
        }

        $recordsCollection = new RecordCollection($resultsAsArray);
        $recordsTotal = $recordsCollection->count();

        $query = ob_get_contents();
        ob_end_clean();

        return new GridData($recordsCollection, $recordsTotal, $query);
    }

    public function setObjectModelClass(string $objectModelClass)
    {
        $this->objectModelClass = $objectModelClass;

        return $this;
    }
}
