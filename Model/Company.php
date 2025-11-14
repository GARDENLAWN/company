<?php
declare(strict_types=1);

namespace GardenLawn\Company\Model;

use Magento\Framework\Model\AbstractModel;

class Company extends AbstractModel
{
    /**
     * Cache tag
     */
    public const CACHE_TAG = 'gardenlawn_company';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\GardenLawn\Company\Model\ResourceModel\Company::class);
    }

    /**
     * Get identities
     *
     * @return array
     */
    public function getIdentities(): array
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }
}
