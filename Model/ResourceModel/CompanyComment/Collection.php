<?php

namespace GardenLawn\Company\Model\ResourceModel\CompanyComment;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'comment_id';

    /**
     * Define resource model.
     */
    protected function _construct(): void
    {
        $this->_init('GardenLawn\Company\Model\CompanyComment', 'GardenLawn\Company\Model\ResourceModel\CompanyComment');
    }
}
