<?php
declare(strict_types=1);

namespace GardenLawn\Company\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class CompanyStatus implements OptionSourceInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'AKTYWNY', 'label' => __('Active')],
            ['value' => 'ZAWIESZONY', 'label' => __('Suspended')],
            ['value' => 'WYKRESLONY', 'label' => __('Removed')]
        ];
    }
}
