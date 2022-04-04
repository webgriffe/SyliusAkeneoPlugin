<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Symfony\Component\HttpFoundation\File\File;
use Webgriffe\SyliusAkeneoPlugin\AttributeOptions\ApiClientInterface as AttributeOptionsApiClientInterface;
use Webmozart\Assert\Assert;

final class ApiClient implements ApiClientInterface, AttributeOptionsApiClientInterface, FamilyAwareApiClientInterface, MeasurementFamiliesApiClientInterface
{
    private ?string $accessToken = null;

    private ?string $refreshToken = null;

    private string $baseUrl;

    private ?\Webgriffe\SyliusAkeneoPlugin\TemporaryFilesManagerInterface $temporaryFilesManager;

    public function __construct(
        private ClientInterface $httpClient,
        string $baseUrl,
        private string $username,
        private string $password,
        private string $clientId,
        private string $secret,
        TemporaryFilesManagerInterface $temporaryFilesManager = null
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        if (null === $temporaryFilesManager) {
            trigger_deprecation(
                'webgriffe/sylius-akeneo-plugin',
                '1.3',
                'Not passing a temporary files manager to %s is deprecated and will not be possible anymore in %s',
                self::class,
                '2.0'
            );
        }
        $this->temporaryFilesManager = $temporaryFilesManager;
    }

    /**
     * @throws GuzzleException
     * @throws \HttpException
     */
    public function authenticatedRequest(string $uri, string $method, array $headers, bool $withRefresh = false): array
    {
        if (str_starts_with($uri, '/')) {
            $uri = $this->baseUrl . $uri;
        }

        if (!(bool) $this->accessToken) {
            $this->login();
        }

        if ($withRefresh) {
            $this->refreshAccessToken();
        }

        $headers = array_merge(
            $headers,
            [
                'Content-Type' => 'application/json',
                'Authorization' => sprintf('Bearer %s', (string) $this->accessToken),
            ]
        );
        $request = new Request($method, $uri, $headers);

        try {
            $response = $this->httpClient->send($request);

            return (array) json_decode($response->getBody()->getContents(), true, 512, \JSON_THROW_ON_ERROR);
        } catch (RequestException $requestException) {
            $erroredResponse = $requestException->getResponse();
            Assert::notNull($erroredResponse);
            $accessTokenHasExpired = $erroredResponse->getStatusCode() === 401;
            if ($accessTokenHasExpired && !$withRefresh) {
                return $this->authenticatedRequest($uri, $method, $headers, true);
            }

            throw $requestException;
        }
    }

    /**
     * @throws GuzzleException
     * @throws \HttpException
     */
    public function findProductModel(string $code): ?array
    {
        return $this->getResourceOrNull(sprintf('/api/rest/v1/product-models/%s', $this->getEncodedProductCode($code)));
    }

    /**
     * @throws GuzzleException
     * @throws \HttpException
     */
    public function findFamilyVariant(string $familyCode, string $familyVariantCode): ?array
    {
        return $this->getResourceOrNull(
            sprintf('/api/rest/v1/families/%s/variants/%s', $familyCode, $familyVariantCode)
        );
    }

    /**
     * @throws GuzzleException
     * @throws \HttpException
     */
    public function findAttribute(string $code): ?array
    {
        return $this->getResourceOrNull(sprintf('/api/rest/v1/attributes/%s', $code));
    }

    /**
     * @throws GuzzleException
     * @throws \HttpException
     */
    public function downloadFile(string $code): \SplFileInfo
    {
        $endpoint = sprintf('/api/rest/v1/media-files/%s/download', $code);
        Assert::string($this->accessToken);
        $headers = ['Authorization' => sprintf('Bearer %s', $this->accessToken)];
        $request = new Request('GET', $this->baseUrl . $endpoint, $headers);
        $response = $this->httpClient->send($request);
        $statusClass = (int) ($response->getStatusCode() / 100);
        $bodyContents = $response->getBody()->getContents();
        if ($statusClass !== 2) {
            $responseResult = json_decode($bodyContents, true, 512, \JSON_THROW_ON_ERROR);

            throw new \HttpException($responseResult['message'], $responseResult['code']);
        }
        $tempName = $this->generateTempFilePath();
        file_put_contents($tempName, $bodyContents);

        return new File($tempName);
    }

    /**
     * @throws GuzzleException
     * @throws \HttpException
     */
    public function findProduct(string $code): ?array
    {
        return $this->getResourceOrNull(sprintf('/api/rest/v1/products/%s', $this->getEncodedProductCode($code)));
    }

    /**
     * @throws GuzzleException
     * @throws \HttpException
     */
    public function findAttributeOption(string $attributeCode, string $optionCode): ?array
    {
        return $this->getResourceOrNull(sprintf('/api/rest/v1/attributes/%s/options/%s', $attributeCode, $optionCode));
    }

    /**
     * @throws GuzzleException
     * @throws \HttpException
     */
    public function findProductsModifiedSince(\DateTime $date): array
    {
        $endpoint = sprintf(
            '/api/rest/v1/products?search={"updated":[{"operator":">","value":"%s"}]}&pagination_type=search_after&limit=20',
            $date->format('Y-m-d H:i:s')
        );

        return $this->traversePagination($this->authenticatedRequest($endpoint, 'GET', []));
    }

    public function findAllAttributeOptions(string $attributeCode): array
    {
        return $this->traversePagination(
            $this->authenticatedRequest("/api/rest/v1/attributes/$attributeCode/options", 'GET', [])
        );
    }

    public function findAllAttributes(): array
    {
        return $this->traversePagination($this->authenticatedRequest('/api/rest/v1/attributes', 'GET', []));
    }

    public function findAllFamilies(): array
    {
        return $this->traversePagination($this->authenticatedRequest('/api/rest/v1/families', 'GET', []));
    }

    public function findFamily(string $code): ?array
    {
        return $this->getResourceOrNull(sprintf('/api/rest/v1/families/%s', $code));
    }

    private function login(): void
    {
        $body = json_encode(
            [
                'grant_type' => 'password',
                'username' => $this->username,
                'password' => $this->password,
            ],
            \JSON_THROW_ON_ERROR
        );
        $responseResult = $this->makeOauthRequest($body);

        $this->accessToken = $responseResult['access_token'];
        $this->refreshToken = $responseResult['refresh_token'];
    }

    private function refreshAccessToken(): void
    {
        $body = json_encode(
            [
                'grant_type' => 'refresh_token',
                'refresh_token' => $this->refreshToken,
            ],
            \JSON_THROW_ON_ERROR
        );
        $responseResult = $this->makeOauthRequest($body);

        $this->accessToken = $responseResult['access_token'];
        $this->refreshToken = $responseResult['refresh_token'];
    }

    /**
     * @throws \HttpException
     * @throws GuzzleException
     */
    private function getResourceOrNull(string $endpoint): ?array
    {
        try {
            $response = $this->authenticatedRequest($endpoint, 'GET', []);
        } catch (ClientException $exception) {
            if ($exception->getCode() !== 404) {
                throw $exception;
            }

            return null;
        }

        return $response;
    }

    private function traversePagination(array $responseResult): array
    {
        $items = $responseResult['_embedded']['items'];

        while ($nextPageUrl = ($responseResult['_links']['next']['href'] ?? null)) {
            Assert::string($nextPageUrl);
            $responseResult = $this->authenticatedRequest($nextPageUrl, 'GET', []);

            /** @noinspection SlowArrayOperationsInLoopInspection */
            $items = array_merge($items, $responseResult['_embedded']['items']);
        }

        return $items;
    }

    private function generateTempFilePath(): string
    {
        if (null === $this->temporaryFilesManager) {
            $tempName = tempnam(sys_get_temp_dir(), 'akeneo-');
            Assert::string($tempName);

            return $tempName;
        }

        return $this->temporaryFilesManager->generateTemporaryFilePath();
    }

    /**
     * @return array{access_token: string, refresh_token: string, expires_in: int, token_type: string, scope: string|null}
     *
     * @throws GuzzleException
     */
    private function makeOauthRequest(string $body): array
    {
        $headers = [
            'Content-Type' => 'application/json',
        ];
        $request = new Request(
            'POST',
            $this->baseUrl . '/api/oauth/v1/token',
            $headers,
            $body
        );
        $options = [
            'auth' => [
                $this->clientId,
                $this->secret,
            ],
        ];
        $rawResponse = $this->httpClient->send($request, $options);

        /** @var array{access_token: string, refresh_token: string, expires_in: int, token_type: string, scope: string|null} $result */
        $result = json_decode($rawResponse->getBody()->getContents(), true, 512, \JSON_THROW_ON_ERROR);

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getMeasurementFamilies(): array
    {
        /** @var array<array-key, array{code: string, labels: array{localeCode: string}, standard_unit_code: string, units: array{unitCode: array{code: string, labels: array<string, string>, convert_from_standard: array{operator: string, value: string}, symbol: string}}}> $unitMeasurements */
        $unitMeasurements = $this->authenticatedRequest(
            '/api/rest/v1/measurement-families',
            'GET',
            []
        );

        return $unitMeasurements;
    }

    private function getEncodedProductCode(string $code): string
    {
        return implode('/', array_map('urlencode', explode('/', $code)));
    }
}
