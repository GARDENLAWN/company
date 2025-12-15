<?php

namespace GardenLawn\Company\Ui\Component\Listing\Grid\Column;

use Magento\Ui\Component\Listing\Columns\Column;

class Www extends Column
{
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $fieldName = $this->getData('name');
                if (!empty($item[$fieldName])) {
                    $url = $item[$fieldName];
                    // Add protocol if missing
                    if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
                        $url = "http://" . $url;
                    }

                    $item[$fieldName] = sprintf(
                        '<a href="%s" target="_blank">%s</a>',
                        htmlspecialchars($url, ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($item[$fieldName], ENT_QUOTES, 'UTF-8')
                    );
                }
            }
        }

        return $dataSource;
    }
}
