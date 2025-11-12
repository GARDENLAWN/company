<?php

namespace GardenLawn\Company\Api\Data;

class CeidgService
{
    public static function getDataByNip($nip)
    {
        if (strlen($nip) == 0) return null;

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, "https://dane.biznes.gov.pl/api/ceidg/v2/firmy?nip=" . $nip);
        //https://dane.biznes.gov.pl/api/ceidg/v2/firmy?pkd=8130Z&wojewodztwo=opolskie&status=AKTYWNY
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BEARER);
        curl_setopt($curl, CURLOPT_XOAUTH2_BEARER, 'eyJraWQiOiJjZWlkZyIsImFsZyI6IkhTNTEyIn0.eyJnaXZlbl9uYW1lIjoiUmFmYcWCIiwicGVzZWwiOiI4NzA2MTUxMTkxMyIsImlhdCI6MTcyMDAzMzUwNCwiZmFtaWx5X25hbWUiOiJQaWVjaG90YSIsImNsaWVudF9pZCI6IlVTRVItODcwNjE1MTE5MTMtUkFGQcWBLVBJRUNIT1RBIn0.cEcG_lWVHDqWD5_VWp4cqjo-cteNUhmdoWcOCD4phuUp17_F1C27o9q9Ejq1FG5x6Hedl_s4jFB6oS7Fww-KEQ');
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));

        $response = curl_exec($curl);
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);

        curl_close($curl);

        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);

        return json_decode($body);
    }
}
