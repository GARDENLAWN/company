<?php
declare(strict_types=1);

namespace GardenLawn\Company\Cron;

use Exception;
use GardenLawn\Company\Api\Data\CeidgService;
use GardenLawn\Company\Api\Data\Exception\CeidgApiException;
use GardenLawn\Company\Enum\Status;
use GardenLawn\Company\Helper\Data as CompanyHelper;
use GardenLawn\Core\Utils\Logger;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\Adapter\AdapterInterface;

class CompanyCeidg
{
    protected CeidgService $ceidgService;
    protected CompanyHelper $companyHelper;
    protected AdapterInterface $connection;
    protected ObjectManager $objectManager;

    public function __construct(
        CeidgService    $ceidgService,
        CompanyHelper   $companyHelper
    )
    {
        $this->ceidgService = $ceidgService;
        $this->companyHelper = $companyHelper;
        $this->objectManager = ObjectManager::getInstance();
        $resource = $this->objectManager->get('Magento\Framework\App\ResourceConnection');
        $this->connection = $resource->getConnection();
    }

    /**
     * @throws Exception
     */
    public function execute(): void
    {
        $baseUrl = $this->companyHelper->getCeidgApiBaseUrl() . 'firmy';
        $pkdCode = $this->companyHelper->getCeidgApiPkdCode();
        $voivodeship = $this->companyHelper->getCeidgApiVoivodeship();
        $companyApiStatus = $this->companyHelper->getCeidgApiCompanyStatus(); // Renamed to avoid conflict

        $initialUrl = sprintf(
            "%s?pkd=%s&wojewodztwo=%s&status=%s",
            $baseUrl,
            $pkdCode,
            $voivodeship,
            $companyApiStatus
        );

        try {
            $page = $this->ceidgService->getDataByUrl($initialUrl);

            if (!$page || !property_exists($page, 'links') || !property_exists($page->links, 'first') || !property_exists($page->links, 'last')) {
                Logger::writeLog('CEIDG API: Initial page or links not found.');
                return;
            }

            $govlink = str_replace('&page=0', '&page=', $page->links->first);
            $last = $page->links->last;
            $url_components = parse_url($last);
            parse_str($url_components['query'], $params);
            $max = (int)($params['page'] ?? 1);
            $status = Status::New->value; // This status is for internal company status, not CEIDG API status

            for ($i = 1; $i <= $max; $i++) {
                $current = $govlink . $i;
                try {
                    $page = $this->ceidgService->getDataByUrl($current);

                    if ($page != null && property_exists($page, 'firmy')) {
                        foreach ($page->firmy as $item) {
                            try {
                                $url = $item->link;
                                $f = $this->ceidgService->getDataByUrl($url);

                                if (property_exists($f, 'firma') && !empty($f->firma) && $f->firma[0]->pkdGlowny == $pkdCode) {
                                    $name = $f->firma[0]->nazwa;
                                    $email = property_exists($f->firma[0], 'email') ? $f->firma[0]->email : "";
                                    $phone = property_exists($f->firma[0], 'telefon') ? $f->firma[0]->telefon : "";
                                    $www = property_exists($f->firma[0], 'www') ? $f->firma[0]->www : "";
                                    $link = $f->firma[0]->link;
                                    $nip = $f->firma[0]->wlasciciel->nip;
                                    $a = $f->firma[0]->adresDzialalnosci;
                                    $address = (property_exists($a, 'ulica') ? $a->ulica . " " : (property_exists($a, 'miasto') ? $a->miasto . " " : "")) .
                                        (property_exists($a, 'budynek') ? $a->budynek : "") .
                                        (property_exists($a, 'lokal') ? "/" . $a->lokal . ", " : ", ") .
                                        (property_exists($a, 'kod') ? $a->kod . " " : "") .
                                        (property_exists($a, 'miasto') ? $a->miasto : "");
                                    $distance = 0;//$this->distanceShipping->getDistance("ul. Namysłowska 2, 46-081 Dobrzeń Wielki", $address);
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
                            } catch (CeidgApiException $e) {
                                Logger::writeLog('CEIDG API Error for company detail (' . ($url ?? 'N/A') . '): ' . $e->getMessage());
                            } catch (Exception $e) {
                                Logger::writeLog('Error processing company from CEIDG: ' . $e->getMessage());
                            }
                        }
                    }
                } catch (CeidgApiException $e) {
                    Logger::writeLog('CEIDG API Error for page (' . ($current ?? 'N/A') . '): ' . $e->getMessage());
                }
            }
        } catch (CeidgApiException $e) {
            Logger::writeLog('CEIDG API Error during initial page fetch: ' . $e->getMessage());
            throw $e; // Re-throw to allow Magento to mark cron as failed
        } catch (Exception $e) {
            Logger::writeLog('CEIDG Cron Job failed: ' . $e->getMessage());
            throw $e; // Re-throw to allow Magento to mark cron as failed
        }
    }
}
