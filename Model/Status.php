<?php

namespace GardenLawn\Company\Model;

use Magento\Framework\Data\OptionSourceInterface;

class Status implements OptionSourceInterface
{
    /**
     * Get Grid row status labels array with empty value for option element.
     *
     * @return array
     */
    public function getAllOptions(): array
    {
        $res = $this->getOptions();
        array_unshift($res, ['value' => '', 'label' => '']);
        return $res;
    }

    /**
     * Get Grid row type array for option element.
     * @return array
     */
    public function getOptions(): array
    {
        $res = [];
        foreach ($this->getOptionArray() as $index => $value) {
            $res[] = ['value' => $index, 'label' => $value];
        }
        return $res;
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

    public static function getStatusName(?int $status): string
    {
        return self::getOptionArray()[$status ?? 0]['label'];
    }

    public function toOptionArray(): array
    {
        return $this->getOptions();
    }
}
