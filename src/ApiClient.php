<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Webmozart\Assert\Assert;

final class ApiClient implements ApiClientInterface
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
    public function findProductModel(string $code): ?array
    {
        if (!$this->token) {
            $this->login();
        }

        return $this->getResourceOrNull(sprintf('/api/rest/v1/product-models/%s', $code));
    }

    /**
     * @throws GuzzleException
     * @throws \HttpException
     */
    public function findFamilyVariant(string $familyCode, string $familyVariantCode): ?array
    {
        if (!$this->token) {
            $this->login();
        }

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
        if (!$this->token) {
            $this->login();
        }

        return $this->getResourceOrNull(sprintf('/api/rest/v1/attributes/%s', $code));
    }

    public function downloadFile(string $url): \SplFileInfo
    {
        // TODO: Implement downloadFile() method.
        return new \SplFileInfo('');
    }

    /**
     * @throws GuzzleException
     * @throws \HttpException
     */
    public function findProduct(string $code): ?array
    {
        if (!$this->token) {
            $this->login();
        }

        return $this->getResourceOrNull(sprintf('/api/rest/v1/products/%s', $code));
    }

    /**
     * @throws GuzzleException
     * @throws \HttpException
     */
    public function findAttributeOption(string $attributeCode, string $optionCode): ?array
    {
        if (!$this->token) {
            $this->login();
        }

        return $this->getResourceOrNull(sprintf('/api/rest/v1/attributes/%s/options/%s', $attributeCode, $optionCode));
    }

    /**
     * @throws GuzzleException
     * @throws \HttpException
     */
    public function findProductsModifiedAfter(\DateTime $date): ?array
    {
        if (!$this->token) {
            $this->login();
        }
        $endpoint = sprintf(
            '/api/rest/v1/products?search={"updated":[{"operator":">","value":"%s"}]}',
            $date->format('Y-m-d H:i:s')
        );

        $responseResult = $this->doRequest($endpoint);

        return $responseResult['_embedded']['items'];
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
     * @return mixed
     *
     * @throws GuzzleException
     * @throws \HttpException
     */
    private function doRequest(string $endpoint)
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => sprintf('Bearer %s', $this->token),
        ];
        $request = new Request('GET', $this->baseUrl . $endpoint, $headers);
        $response = $this->httpClient->send($request);
        $statusClass = (int) ($response->getStatusCode() / 100);
        $responseResult = json_decode($response->getBody()->getContents(), true);
        if ($statusClass !== 2) {
            throw new \HttpException($responseResult['message'], $responseResult['code']);
        }

        return $responseResult;
    }

    /**
     * @throws \HttpException
     * @throws GuzzleException
     */
    private function getResourceOrNull(string $endpoint): ?array
    {
        try {
            $response = $this->doRequest($endpoint);
        } catch (\HttpException $exception) {
            if ($exception->getCode() !== 404) {
                throw $exception;
            }

            return null;
        }
        Assert::isArray($response);

        return $response;
    }
}
