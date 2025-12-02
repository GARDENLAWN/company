<?php
declare(strict_types=1);

namespace GardenLawn\Company\Api\Data;

use GardenLawn\Company\Api\Data\Exception\CeidgApiException;
use GardenLawn\Company\Helper\Data as HelperData;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;

class CeidgService
{
    private const string CACHE_KEY_PREFIX = 'ceidg_api_';
    private const int CACHE_LIFETIME = 86400; // 24 hours

    protected HelperData $helperData;
    protected Curl $curlClient;
    protected LoggerInterface $logger;
    private CacheInterface $cache;

    private array $lastResponseHeaders = [];

    public function __construct(
        HelperData      $helperData,
        Curl            $curlClient,
        LoggerInterface $logger,
        CacheInterface  $cache
    )
    {
        $this->helperData = $helperData;
        $this->curlClient = $curlClient;
        $this->logger = $logger;
        $this->cache = $cache;
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
        $url = $this->helperData->getCeidgApiBaseUrl() . "firma?nip=" . $nip;
        $response = $this->makeRequest($url);

        if (isset($response->firma) && count($response->firma) > 0) {
            $companyData = $response->firma[0];
            $address = $companyData->adresDzialalnosci;

            return (object)[
                'companyName' => $companyData->nazwa,
                'firstName' => $companyData->wlasciciel->imie,
                'lastName' => $companyData->wlasciciel->nazwisko,
                'street' => $address->ulica . ' ' . $address->budynek . (property_exists($address, 'lokal') ? "/" . $address->lokal : ""),
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
        $cacheKey = self::CACHE_KEY_PREFIX . md5($url);
        $cachedResponse = $this->cache->load($cacheKey);

        if ($cachedResponse) {
            return json_decode($cachedResponse);
        }

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

        $this->cache->save(
            json_encode($decodedResponse),
            $cacheKey,
            [],
            self::CACHE_LIFETIME
        );

        return $decodedResponse;
    }
}
