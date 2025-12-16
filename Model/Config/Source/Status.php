<?php

namespace GardenLawn\Company\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Status implements OptionSourceInterface
{
    public const int StatusNew = 1;
    public const int StatusCreateCustomer = 4;

    /**
     * Get Grid row status labels array with empty value for option element.
     *
     * @return array
     */
    public function getAllOptions(): array
    {
        $res = $this->toOptionArray();
        array_unshift($res, ['value' => '', 'label' => '']);
        return $res;
    }

    /**
     * Get Grid row type array for option element.
     * @return array
     * @deprecated use toOptionArray
     */
    public function getOptions(): array
    {
        return $this->toOptionArray();
    }

    /**
     * Get Grid row status type labels array.
     * @return array
     */
    public static function getOptionArray(): array
    {
        return [
            ['value' => 0, 'label' => __('Brak')],
            ['value' => 1, 'label' => __('Nowy')],
            ['value' => 2, 'label' => __('Po rozmowie')],
            ['value' => 3, 'label' => __('Oddzwonić')],
            ['value' => 4, 'label' => __('Konto utworzone')],
            ['value' => 5, 'label' => __('Niezainteresowany')],
            ['value' => 6, 'label' => __('Wysłano maila')],
            ['value' => 7, 'label' => __('Inna plantacja')],
            ['value' => 8, 'label' => __('Brak telefonu')],
            ['value' => 9, 'label' => __('Nie odebrano')]
        ];
    }

    /**
     * @param int|null $status
     * @return string
     */
    public static function getStatusName(?int $status): string
    {
        $statuses = array_column(self::getOptionArray(), 'label', 'value');
        return (string)($statuses[$status] ?? $statuses[0] ?? '');
    }

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return self::getOptionArray();
    }
}
