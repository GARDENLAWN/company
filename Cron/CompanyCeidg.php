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
                                    $tableName = $this->connection->getTableName('gardenlawn_company');

                                    // Check if company exists and get current data for comparison
                                    $select = $this->connection->select()
                                        ->from($tableName, ['ceidg_email', 'ceidg_phone', 'www', 'address', 'distance'])
                                        ->where('nip = ?', $nip);
                                    $existingCompany = $this->connection->fetchRow($select);

                                    if (!$existingCompany) {
                                        // Company does not exist, insert it
                                        $insertData = [
                                            'nip' => $nip,
                                            'name' => $name,
                                            'url' => $link,
                                            'address' => $address,
                                            'distance' => $distance,
                                            'status' => $status,
                                            'ceidg_email' => $email,
                                            'ceidg_phone' => $phone,
                                            'email' => $email,
                                            'phone' => $phone,
                                            'www' => $www
                                        ];
                                        $this->connection->insert($tableName, $insertData);
                                        Logger::writeLog('CEIDG Cron: Inserted new company with NIP: ' . $nip);
                                    } else {
                                        // Company exists, check if an update is needed
                                        $needsUpdate =
                                            $existingCompany['name'] !== $name||
                                            $existingCompany['url'] !==  $link||
                                            $existingCompany['address'] !==  $address||
                                            $existingCompany['status'] !==  $status||
                                            $existingCompany['ceidg_email' ] !==  $email||
                                            $existingCompany['ceidg_phone' ] !==  $phone||
                                            $existingCompany['email' ] !==  $email||
                                            $existingCompany['phone' ] !==  $phone||
                                            $existingCompany['www' ] !==  $www;

                                        if ($needsUpdate) {
                                            $updateData = [
                                                'name' => $name,
                                                'url' => $link,
                                                'address' => $address,
                                                'status' => $status,
                                                'ceidg_email' => $email,
                                                'ceidg_phone' => $phone,
                                                'email' => $email,
                                                'phone' => $phone,
                                                'www' => $www,
                                                'updated_at' => date('Y-m-d H:i:s')
                                            ];
                                            $where = ['nip = ?' => $nip];
                                            $this->connection->update($tableName, $updateData, $where);
                                            Logger::writeLog('CEIDG Cron: Updated company with NIP: ' . $nip);
                                        }
                                    }
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
