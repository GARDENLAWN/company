<?php

namespace GardenLawn\Company\Cron;

use Exception;
use GardenLawn\Company\Api\Data\CeidgService;
use GardenLawn\Company\Enum\Status;
use GardenLawn\Core\Utils\Logger;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\Adapter\AdapterInterface;

class CompanyCeidg
{
    protected AdapterInterface $connection;
    protected CeidgService $ceidgService;

    public function __construct(CeidgService $ceidgService)
    {
        $this->ceidgService = $ceidgService;
        $objectManager = ObjectManager::getInstance();
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $this->connection = $resource->getConnection();
    }

    /**
     * @throws Exception
     */
    public function execute(): void
    {
        $page = $this->ceidgService->getDataByUrl("https://dane.biznes.gov.pl/api/ceidg/v2/firmy?pkd=8130Z&wojewodztwo=opolskie&status=AKTYWNY");
        $govlink = str_replace('&page=0', '&page=', $page->links->first);
        $last = $page->links->last;
        $url_components = parse_url($last);
        parse_str($url_components['query'], $params);
        $max = $params['page'];
        $status = Status::New->value;
        for ($i = 1; $i <= $max; $i++) {
            $current = $govlink . $i;
            $page = $this->ceidgService->getDataByUrl($current);
            sleep(1);
            try {
                if ($page != null && property_exists($page, 'firmy')) {
                    foreach ($page->firmy as $key => $item) {
                        $url = $item->link;
                        $f = $this->ceidgService->getDataByUrl($url);
                        sleep(1);
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
                            $distance = 0;
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
}
