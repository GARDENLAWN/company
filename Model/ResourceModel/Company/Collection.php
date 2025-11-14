<?php
declare(strict_types=1);

namespace GardenLawn\Company\Model\ResourceModel\Company;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \GardenLawn\Company\Model\Company::class,
            \GardenLawn\Company\Model\ResourceModel\Company::class
        );
    }
}
