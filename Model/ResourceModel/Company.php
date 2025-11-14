<?php
declare(strict_types=1);

namespace GardenLawn\Company\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Company extends AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('gardenlawn_company', 'company_id');
    }
}
