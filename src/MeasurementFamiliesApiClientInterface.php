<?php


namespace Webgriffe\SyliusAkeneoPlugin;

interface MeasurementFamiliesApiClientInterface
{
    /**
     * @return array<array-key, array{code: string, labels: array{localeCode: string}, standard_unit_code: string, units: array{unitCode: array{code: string, labels: array<string, string>, convert_from_standard: array{operator: string, value: string}, symbol: string}}}>
     */
    public function getMeasurementFamilies(): array;
}
