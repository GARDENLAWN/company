<?php
declare(strict_types=1);

namespace GardenLawn\Company\Model\ResourceModel\Company\Grid;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\Data\Collection;

class DataProvider extends AbstractDataProvider
{
    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param Collection $collection
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        Collection $collection,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collection;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }
}
