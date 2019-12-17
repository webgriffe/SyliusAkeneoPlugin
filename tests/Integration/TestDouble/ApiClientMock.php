<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Integration\TestDouble;

use Webgriffe\SyliusAkeneoPlugin\ApiClientInterface;

final class ApiClientMock implements ApiClientInterface
{
    public function findProductModelByIdentifier(string $identifier): ?array
    {
        $filename = __DIR__ . '/../DataFixtures/ApiClientMock/ProductModel/' . $identifier . '.json';
        if (file_exists($filename)) {
            return json_decode(file_get_contents($filename), true);
        }
        return null;
    }
}