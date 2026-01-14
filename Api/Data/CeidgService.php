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

    private HelperData $helperData;
    private Curl $curlClient;
    private LoggerInterface $logger;
    private CacheInterface $cache;

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
     * @param string $taxvat
     * @return object|null
     * @throws CeidgApiException
     */
    public function getDataByNip(string $taxvat): ?object
    {
        if (strlen($taxvat) === 0) {
            return null;
        }
        $url = $this->helperData->getCeidgApiBaseUrl() . "firma?nip=" . $taxvat;
        $response = $this->makeRequest($url);

        if (isset($response->firma) && count($response->firma) > 0) {
            $companyData = $response->firma[0];
            $address = $companyData->adresDzialalnosci;
            $owner = $companyData->wlasciciel;

            return (object)[
                'companyName' => $companyData->nazwa,
                'firstName' => $owner->imie,
                'lastName' => $owner->nazwisko,
                'street' => $address->ulica . ' ' . $address->budynek . (property_exists($address, 'lokal') ? "/" . $address->lokal : ""),
                'postcode' => $address->kod,
                'city' => $address->miasto,
                'region' => $address->wojewodztwo
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
        // Consider moving sleep logic to the caller (Cron) if possible to avoid blocking frontend calls if reused.
        // However, if this service is strictly for scraping/syncing, sleep here is a simple rate limiter.
        sleep(4);
        return $this->makeRequest($url);
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

        $startTime = microtime(true);
        try {
            $this->curlClient->get($url);
        } catch (\Exception $e) {
            $this->logger->error('CEIDG API Connection Error: ' . $e->getMessage());
            throw new CeidgApiException(__('CEIDG API Connection Error: %1', $e->getMessage()));
        }
        $duration = microtime(true) - $startTime;

        $status = $this->curlClient->getStatus();
        $responseBody = $this->curlClient->getBody();

        if ($status !== 200) {
            $errorMessage = sprintf(
                'CEIDG API request to %s failed with status %d (Duration: %.2fs). Response: %s',
                $url,
                $status,
                $duration,
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
