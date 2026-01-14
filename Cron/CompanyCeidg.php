<?php
declare(strict_types=1);

namespace GardenLawn\Company\Cron;

use Exception;
use GardenLawn\Company\Api\Data\CeidgService;
use GardenLawn\Company\Api\Data\Exception\CeidgApiException;
use GardenLawn\Company\Model\Config\Source\Status;
use GardenLawn\Company\Helper\Data as CompanyHelper;
use GardenLawn\Company\Model\CompanyFactory;
use GardenLawn\Company\Model\ResourceModel\Company as CompanyResource;
use GardenLawn\Company\Model\ResourceModel\Company\CollectionFactory as CompanyCollectionFactory;
use GardenLawn\Core\Utils\Logger;

class CompanyCeidg
{
    private CeidgService $ceidgService;
    private CompanyHelper $companyHelper;
    private CompanyFactory $companyFactory;
    private CompanyResource $companyResource;
    private CompanyCollectionFactory $companyCollectionFactory;

    public function __construct(
        CeidgService $ceidgService,
        CompanyHelper $companyHelper,
        CompanyFactory $companyFactory,
        CompanyResource $companyResource,
        CompanyCollectionFactory $companyCollectionFactory
    ) {
        $this->ceidgService = $ceidgService;
        $this->companyHelper = $companyHelper;
        $this->companyFactory = $companyFactory;
        $this->companyResource = $companyResource;
        $this->companyCollectionFactory = $companyCollectionFactory;
    }

    /**
     * @throws Exception
     */
    public function execute(): void
    {
        $baseUrl = $this->companyHelper->getCeidgApiBaseUrl() . 'firmy';
        $pkdCode = $this->companyHelper->getCeidgApiPkdCode();
        $voivodeship = $this->companyHelper->getCeidgApiVoivodeship();
        $companyApiStatus = $this->companyHelper->getCeidgApiCompanyStatus();

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
            $status = Status::StatusNew;

            for ($i = 1; $i <= $max; $i++) {
                $current = $govlink . $i;
                try {
                    $page = $this->ceidgService->getDataByUrl($current);

                    if ($page != null && property_exists($page, 'firmy')) {
                        foreach ($page->firmy as $item) {
                            $this->processCompanyItem($item, $pkdCode, $status);
                        }
                    }
                } catch (CeidgApiException $e) {
                    Logger::writeLog('CEIDG API Error for page (' . ($current ?? 'N/A') . '): ' . $e->getMessage());
                }
            }
        } catch (CeidgApiException $e) {
            Logger::writeLog('CEIDG API Error during initial page fetch: ' . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            Logger::writeLog('CEIDG Cron Job failed: ' . $e->getMessage());
            throw $e;
        }
    }

    private function processCompanyItem($item, $pkdCode, $status): void
    {
        try {
            $url = $item->link;
            $f = $this->ceidgService->getDataByUrl($url);

            if (!property_exists($f, 'firma') || empty($f->firma)) {
                return;
            }

            $companyData = $f->firma[0];
            if ($companyData->pkdGlowny->kod != $pkdCode) {
                return;
            }

            $nip = $companyData->wlasciciel->nip;

            // Load company by NIP using Collection to avoid loading full model if not needed
            // Or use ResourceModel load which is standard
            $companyModel = $this->companyFactory->create();
            $this->companyResource->load($companyModel, $nip, 'nip');

            $name = $companyData->nazwa;
            $email = $companyData->email ?? "";
            $phone = $companyData->telefon ?? "";
            $www = $companyData->www ?? "";
            $link = $companyData->link;

            $a = $companyData->adresDzialalnosci;
            $address = (property_exists($a, 'ulica') ? $a->ulica . " " : (property_exists($a, 'miasto') ? $a->miasto . " " : "")) .
                (property_exists($a, 'budynek') ? $a->budynek : "") .
                (property_exists($a, 'lokal') ? "/" . $a->lokal . ", " : ", ") .
                (property_exists($a, 'kod') ? $a->kod . " " : "") .
                (property_exists($a, 'miasto') ? $a->miasto : "");

            $distance = 0; // Placeholder logic preserved

            if (!$companyModel->getId()) {
                // New Company
                $companyModel->setData([
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
                ]);
                $this->companyResource->save($companyModel);
                Logger::writeLog('CEIDG Cron: Inserted new company with NIP: ' . $nip);
            } else {
                // Update existing company if data changed
                $needsUpdate =
                    $companyModel->getName() !== $name ||
                    $companyModel->getUrl() !== $link ||
                    $companyModel->getAddress() !== $address ||
                    $companyModel->getCeidgEmail() !== $email ||
                    $companyModel->getCeidgPhone() !== $phone ||
                    $companyModel->getWww() !== $www;

                if ($needsUpdate) {
                    $companyModel->setName($name)
                        ->setUrl($link)
                        ->setAddress($address)
                        ->setCeidgEmail($email)
                        ->setCeidgPhone($phone)
                        ->setWww($www);
                    // updated_at is handled automatically by DB schema (on_update="true") or Model
                    $this->companyResource->save($companyModel);
                    Logger::writeLog('CEIDG Cron: Updated company with NIP: ' . $nip);
                }
            }

        } catch (CeidgApiException $e) {
            Logger::writeLog('CEIDG API Error for company detail (' . ($url ?? 'N/A') . '): ' . $e->getMessage());
        } catch (Exception $e) {
            Logger::writeLog('Error processing company from CEIDG: ' . $e->getMessage());
        }
    }
}
