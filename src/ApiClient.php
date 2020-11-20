<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Symfony\Component\HttpFoundation\File\File;
use Webgriffe\SyliusAkeneoPlugin\AttributeOptions\ApiClientInterface as AttributeOptionsApiClientInterface;
use Webmozart\Assert\Assert;

final class ApiClient implements ApiClientInterface, AttributeOptionsApiClientInterface
{
    /** @var string */
    private $token;

    /** @var ClientInterface */
    private $httpClient;

    /** @var string */
    private $baseUrl;

    /** @var string */
    private $username;

    /** @var string */
    private $password;

    /** @var string */
    private $clientId;

    /** @var string */
    private $secret;

    public function __construct(
        ClientInterface $httpClient,
        string $baseUrl,
        string $username,
        string $password,
        string $clientId,
        string $secret
    ) {
        $this->httpClient = $httpClient;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->username = $username;
        $this->password = $password;
        $this->clientId = $clientId;
        $this->secret = $secret;
    }

    /**
     * @throws GuzzleException
     * @throws \HttpException
     */
    public function authenticatedRequest(string $uri, string $method, array $headers): array
    {
        if (strpos($uri, '/') === 0) {
            $uri = $this->baseUrl . $uri;
        }

        if (!(bool) $this->token) {
            $this->login();
        }

        $headers = array_merge(
            $headers,
            [
                'Content-Type' => 'application/json',
                'Authorization' => sprintf('Bearer %s', $this->token),
            ]
        );
        $request = new Request($method, $uri, $headers);
        $response = $this->httpClient->send($request);
        $statusClass = (int) ($response->getStatusCode() / 100);
        $responseResult = json_decode($response->getBody()->getContents(), true);
        if ($statusClass !== 2) {
            throw new \HttpException($responseResult['message'], $responseResult['code']);
        }

        return $responseResult;
    }

    /**
     * @throws GuzzleException
     * @throws \HttpException
     */
    public function findProductModel(string $code): ?array
    {
        return $this->getResourceOrNull(sprintf('/api/rest/v1/product-models/%s', $code));
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
        $headers = ['Authorization' => sprintf('Bearer %s', $this->token)];
        $request = new Request('GET', $this->baseUrl . $endpoint, $headers);
        $response = $this->httpClient->send($request);
        $statusClass = (int) ($response->getStatusCode() / 100);
        $bodyContents = $response->getBody()->getContents();
        if ($statusClass !== 2) {
            $responseResult = json_decode($bodyContents, true);

            throw new \HttpException($responseResult['message'], $responseResult['code']);
        }
        $tempName = tempnam(sys_get_temp_dir(), 'akeneo-');
        Assert::string($tempName);
        file_put_contents($tempName, $bodyContents);

        return new File($tempName);
    }

    /**
     * @throws GuzzleException
     * @throws \HttpException
     */
    public function findProduct(string $code): ?array
    {
        return $this->getResourceOrNull(sprintf('/api/rest/v1/products/%s', $code));
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
            '/api/rest/v1/products?search={"updated":[{"operator":">","value":"%s"}]}&limit=20&page=1',
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

    private function login(): void
    {
        $body = json_encode(
            [
                'grant_type' => 'password',
                'username' => $this->username,
                'password' => $this->password,
            ]
        );
        Assert::string($body);
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
        $responseResult = json_decode($rawResponse->getBody()->getContents(), true);

        $this->token = $responseResult['access_token'];
    }

    /**
     * @throws \HttpException
     * @throws GuzzleException
     */
    private function getResourceOrNull(string $endpoint): ?array
    {
        try {
            $response = $this->authenticatedRequest($endpoint, 'GET', []);
        } catch (\HttpException $exception) {
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
}
