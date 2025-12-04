<?php
declare(strict_types=1);

namespace GardenLawn\Company\Cron;

use Exception;
use GardenLawn\Company\Api\Data\CeidgService;
use GardenLawn\Company\Api\Data\Exception\CeidgApiException;

// Added this use statement
use GardenLawn\Company\Enum\Status;
use GardenLawn\Company\Helper\Data as CompanyHelper;
use GardenLawn\Company\Model\CompanyFactory;
use GardenLawn\Company\Model\ResourceModel\Company as CompanyResource;
use GardenLawn\Core\Utils\Logger;

// Assuming this Logger is still desired for general logging

class CompanyCeidg
{
    protected CeidgService $ceidgService;
    protected CompanyFactory $companyFactory;
    protected CompanyResource $companyResource;
    protected CompanyHelper $companyHelper;

    public function __construct(
        CeidgService    $ceidgService,
        CompanyFactory  $companyFactory,
        CompanyResource $companyResource,
        CompanyHelper   $companyHelper
    )
    {
        $this->ceidgService = $ceidgService;
        $this->companyFactory = $companyFactory;
        $this->companyResource = $companyResource;
        $this->companyHelper = $companyHelper;
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
            $internalCompanyStatus = Status::New->value; // This status is for internal company status, not CEIDG API status

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
                                    $companyData = $f->firma[0];

                                    $nip = $companyData->wlasciciel->nip ?? null;
                                    $name = $companyData->nazwa ?? null;

                                    // Basic validation for NIP and Name
                                    if (empty($nip) || empty($name)) {
                                        Logger::writeLog('Skipping company due to missing NIP or Name: ' . json_encode($companyData));
                                        continue;
                                    }

                                    $email = property_exists($companyData, 'email') ? $companyData->email : "";
                                    $phone = property_exists($companyData, 'telefon') ? $companyData->telefon : "";
                                    $www = property_exists($companyData, 'www') ? $companyData->www : "";
                                    $link = $companyData->link;
                                    $a = $companyData->adresDzialalnosci;
                                    $address = (property_exists($a, 'ulica') ? $a->ulica . " " : (property_exists($a, 'miasto') ? $a->miasto . " " : "")) .
                                        (property_exists($a, 'budynek') ? $a->budynek : "") .
                                        (property_exists($a, 'lokal') ? "/" . $a->lokal . ", " : ", ") .
                                        (property_exists($a, 'kod') ? $a->kod . " " : "") .
                                        (property_exists($a, 'miasto') ? $a->miasto : "");
                                    $distance = 0; // This seems to be a default value, consider if it should be dynamic

                                    $company = $this->companyFactory->create();
                                    $this->companyResource->load($company, $nip, 'nip'); // Load by NIP

                                    $isNewCompany = !$company->getId();
                                    $originalData = $company->getData();

                                    $company->setNip($nip);
                                    $company->setName($name);
                                    $company->setUrl($link);
                                    $company->setAddress($address);
                                    $company->setDistance($distance);
                                    $company->setCeidgEmail($email);
                                    $company->setCeidgPhone($phone);
                                    $company->setWww($www);

                                    // Only set initial status for new companies
                                    if ($isNewCompany) {
                                        $company->setStatus($internalCompanyStatus);
                                        // Also set email/phone for new companies from CEIDG data
                                        $company->setEmail($email);
                                        $company->setPhone($phone);
                                    }

                                    $isDataChanged = false;
                                    if (!$isNewCompany) {
                                        $newData = $company->getData();
                                        $fieldsToCheck = ['name', 'url', 'address', 'ceidg_email', 'ceidg_phone', 'www'];
                                        foreach ($fieldsToCheck as $field) {
                                            if ($originalData[$field] != $newData[$field]) {
                                                $isDataChanged = true;
                                                break;
                                            }
                                        }
                                    }

                                    if ($isNewCompany || $isDataChanged) {
                                        $this->companyResource->save($company);
                                        Logger::writeLog('Company ' . $nip . ' ' . ($isNewCompany ? 'created' : 'updated') . '.');
                                    } else {
                                        Logger::writeLog('Company ' . $nip . ' data unchanged. Skipping save.');
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
