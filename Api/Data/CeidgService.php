<?php

namespace GardenLawn\Company\Api\Data;

use GardenLawn\Company\Helper\Data as HelperData;

class CeidgService
{
    protected HelperData $helperData;

    public function __construct(HelperData $helperData)
    {
        $this->helperData = $helperData;
    }

    public function getDataByNip($nip)
    {
        if (strlen($nip) == 0) {
            return null;
        }
        $url = "https://dane.biznes.gov.pl/api/ceidg/v2/firmy?nip=" . $nip;
        return $this->_makeRequest($url);
    }

    public function getDataByUrl(string $url)
    {
        return $this->_makeRequest($url);
    }

    private function _makeRequest(string $url)
    {
        $curl = curl_init();

        $xoauth2_bearer = $this->helperData->getXoauth2Bearer();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BEARER);
        curl_setopt($curl, CURLOPT_XOAUTH2_BEARER, $xoauth2_bearer);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));

        $response = curl_exec($curl);
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);

        curl_close($curl);

        $body = substr($response, $header_size);

        return json_decode($body);
    }
}
