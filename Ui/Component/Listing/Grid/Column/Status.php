<?php
namespace GardenLawn\Company\Ui\Component\Listing\Grid\Column;

use Magento\Ui\Component\Listing\Columns\Column;
use GardenLawn\Company\Model\Config\Source\Status as StatusOptions;

class Status extends Column
{
    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            $statusOptions = new StatusOptions();
            $options = [];
            foreach ($statusOptions->toOptionArray() as $option) {
                $options[$option['value']] = $option['label'];
            }

            foreach ($dataSource['data']['items'] as & $item) {
                $fieldName = $this->getData('name');
                if (isset($item[$fieldName])) {
                    $item[$fieldName] = $options[$item[$fieldName]] ?? $item[$fieldName];
                }
            }
        }

        return $dataSource;
    }
}
