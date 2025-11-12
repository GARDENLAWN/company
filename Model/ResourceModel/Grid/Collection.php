<?php

namespace GardenLawn\Company\Model\ResourceModel\Grid;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'company_id';

    /**
     * Define resource model.
     */
    protected function _construct(): void
    {
        $this->_init('GardenLawn\Company\Model\Grid', 'GardenLawn\Company\Model\ResourceModel\Grid');
    }
}
