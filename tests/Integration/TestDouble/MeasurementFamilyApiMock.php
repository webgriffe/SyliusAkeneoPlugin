<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Integration\TestDouble;

use Akeneo\Pim\ApiClient\Api\MeasurementFamilyApiInterface;

final class MeasurementFamilyApiMock implements MeasurementFamilyApiInterface
{
    public function all(): array
    {
        return $this->jsonDecodeOrNull(__DIR__ . '/../DataFixtures/ApiClientMock/MeasurementFamilies/MeasurementFamilies.json');
    }

    public function upsertList($resources): array
    {
        // TODO: Implement upsertList() method.
    }

    /** @return mixed|null */
    private function jsonDecodeOrNull(string $filename)
    {
        if (file_exists($filename)) {
            return json_decode(file_get_contents($filename), true, 512, \JSON_THROW_ON_ERROR);
        }

        return null;
    }
}
