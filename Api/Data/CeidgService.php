<?php
declare(strict_types=1);

namespace GardenLawn\Company\Api\Data;

use GardenLawn\Company\Api\Data\Exception\CeidgApiException;
use GardenLawn\Company\Helper\Data as HelperData;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;

class CeidgService
{
    protected HelperData $helperData;
    protected Curl $curlClient;
    protected LoggerInterface $logger;

    private array $lastResponseHeaders = [];

    public function __construct(
        HelperData      $helperData,
        Curl            $curlClient,
        LoggerInterface $logger
    )
    {
        $this->helperData = $helperData;
        $this->curlClient = $curlClient;
        $this->logger = $logger;
    }

    /**
     * Get data by NIP from CEIDG API.
     *
     * @param string $nip
     * @return object|null
     * @throws CeidgApiException
     */
    public function getDataByNip(string $nip): ?object
    {
        if (strlen($nip) === 0) {
            return null;
        }
        $url = $this->helperData->getCeidgApiBaseUrl() . "?nip=" . $nip;
        $response = $this->makeRequest($url);

        if (isset($response->firmy) && count($response->firmy) > 0) {
            $companyData = $response->firmy[0];
            $address = $companyData->adresDzialalnosci;

            return (object)[
                'name' => $companyData->nazwa,
                'street' => $address->ulica . ' ' . $address->budynek,
                'postcode' => $address->kod,
                'city' => $address->miasto,
                'region_id' => strtolower($address->wojewodztwo)
            ];
        }

        return null;
    }

    /**
     * Get data by URL from CEIDG API.
     *
     * @param string $url
     * @return object|null
     * @throws CeidgApiException
     */
    public function getDataByUrl(string $url): ?object
    {
        return $this->makeRequest($url);
    }

    /**
     * Get last response headers.
     *
     * @return array
     */
    public function getLastResponseHeaders(): array
    {
        return $this->lastResponseHeaders;
    }

    /**
     * Make an HTTP request to the CEIDG API.
     *
     * @param string $url
     * @return object|null
     * @throws CeidgApiException
     */
    private function makeRequest(string $url): ?object
    {
        $token = $this->helperData->getCeidgApiToken();
        if (!$token) {
            $this->logger->error('CEIDG API Token is not configured.');
            throw new CeidgApiException(__('CEIDG API Token is not configured.'));
        }

        $this->curlClient->addHeader('Authorization', 'Bearer ' . $token);
        $this->curlClient->get($url);

        $status = $this->curlClient->getStatus();
        $responseBody = $this->curlClient->getBody();
        $this->lastResponseHeaders = $this->curlClient->getHeaders();

        if ($status !== 200) {
            $errorMessage = sprintf(
                'CEIDG API request to %s failed with status %d. Response: %s',
                $url,
                $status,
                $responseBody
            );
            $this->logger->error($errorMessage);
            throw new CeidgApiException(__($errorMessage));
        }

        $decodedResponse = json_decode($responseBody);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $errorMessage = sprintf(
                'CEIDG API response for %s could not be decoded. Error: %s. Response: %s',
                $url,
                json_last_error_msg(),
                $responseBody
            );
            $this->logger->error($errorMessage);
            throw new CeidgApiException(__($errorMessage));
        }

        return $decodedResponse;
    }
}
