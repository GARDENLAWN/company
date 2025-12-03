<?php
declare(strict_types=1);

namespace GardenLawn\Company\Model\ResourceModel\Company\Grid;

use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider as UiDataProvider;
use GardenLawn\Company\Model\ResourceModel\Company\CollectionFactory;

class DataProvider extends UiDataProvider
{
    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }
}
