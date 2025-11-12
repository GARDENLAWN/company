<?php

namespace GardenLawn\Company\Cron;

use Exception;
use GardenLawn\Company\Enum\Status;
use GardenLawn\Core\Model\Carrier\DistanceShipping;
use GardenLawn\Core\Utils\Logger;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\Adapter\AdapterInterface;

class CompanyCeidg
{
    protected ObjectManager $objectManager;
    protected DistanceShipping $distanceShipping;
    protected AdapterInterface $connection;

    public function __construct(DistanceShipping $distanceShipping)
    {
        $this->distanceShipping = $distanceShipping;
        $this->objectManager = ObjectManager::getInstance();
        $resource = $this->objectManager->get('Magento\Framework\App\ResourceConnection');
        $this->connection = $resource->getConnection();
    }

    /**
     * @throws Exception
     */
    public function execute(): void
    {
        $page = $this->getCeidg("https://dane.biznes.gov.pl/api/ceidg/v2/firmy?pkd=8130Z&wojewodztwo=opolskie&status=AKTYWNY");
        $govlink = str_replace('&page=0', '&page=', $page->links->first);
        $last = $page->links->last;
        $url_components = parse_url($last);
        parse_str($url_components['query'], $params);
        $max = $params['page'];
        $status = Status::New->value;
        for ($i = 1; $i <= $max; $i++) {
            $current = $govlink . $i;
            $page = $this->getCeidg($current);
            try {
                if ($page != null && property_exists($page, 'firmy')) {
                    foreach ($page->firmy as $key => $item) {
                        $url = $item->link;
                        $f = $this->getCeidg($url);
                        if (property_exists($f, 'firma') && $f->firma[0]->pkdGlowny == '8130Z') {
                            $name = $f->firma[0]->nazwa;
                            $email = property_exists($f->firma[0], 'email') ? $f->firma[0]->email : "";
                            $phone = property_exists($f->firma[0], 'telefon') ? $f->firma[0]->telefon : "";
                            $www = property_exists($f->firma[0], 'www') ? $f->firma[0]->www : "";
                            $link = $f->firma[0]->link;
                            $nip = $f->firma[0]->wlasciciel->nip;
                            $a = $f->firma[0]->adresDzialalnosci;
                            $address = (property_exists($a, 'ulica') ? $a->ulica . " " : (property_exists($a, 'miasto') ? $a->miasto . " " : "")) .
                                (property_exists($a, 'budynek') ? $a->budynek . ", " : "") .
                                (property_exists($a, 'kod') ? $a->kod . " " : "") .
                                (property_exists($a, 'miasto') ? $a->miasto : "");
                            $distance = $this->distanceShipping->getDistance("ul. Namysłowska 2, 46-081 Dobrzeń Wielki", $address);
                            $sql = "SELECT COUNT(company_id) AS Number FROM gardenlawn_company WHERE nip = '$nip';";
                            $count = $this->connection->fetchAll($sql)[0]["Number"];
                            if ($count == 0) {
                                $sql = "INSERT INTO gardenlawn_company(nip, name, url, address, distance, status, ceidg_email, ceidg_phone, email, phone, www) VALUES ('$nip', '$name', '$link', '$address', $distance, $status, '$email', '$phone', '$email', '$phone', '$www');";
                            } else {
                                $sql = "UPDATE gardenlawn_company SET ceidg_email = '$email', ceidg_phone = '$phone', www = '$www', address = '$address', distance = $distance WHERE nip = '$nip' AND (ceidg_email IS NULL OR ceidg_email <> '$email' OR ceidg_phone <> '$phone' OR www <> '$www' OR address <> '$address' OR distance <> $distance);";
                            }
                            Logger::writeLog($sql);
                            $this->connection->query($sql);
                        }
                    }
                }
            } catch (Exception $e) {
                Logger::writeLog($e);
            }
        }
    }

    private function getCeidg($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BEARER);
        curl_setopt($curl, CURLOPT_XOAUTH2_BEARER, 'eyJraWQiOiJjZWlkZyIsImFsZyI6IkhTNTEyIn0.eyJnaXZlbl9uYW1lIjoiUmFmYcWCIiwicGVzZWwiOiI4NzA2MTUxMTkxMyIsImlhdCI6MTcyMDAzMzUwNCwiZmFtaWx5X25hbWUiOiJQaWVjaG90YSIsImNsaWVudF9pZCI6IlVTRVItODcwNjE1MTE5MTMtUkFGQcWBLVBJRUNIT1RBIn0.cEcG_lWVHDqWD5_VWp4cqjo-cteNUhmdoWcOCD4phuUp17_F1C27o9q9Ejq1FG5x6Hedl_s4jFB6oS7Fww-KEQ');
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        $response = curl_exec($curl);
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        curl_close($curl);
        $body = substr($response, $header_size);
        sleep(3);
        return json_decode($body);
    }
}
