<?php


namespace Webgriffe\SyliusAkeneoPlugin;


use GuzzleHttp\Exception\GuzzleException;

interface MeasurementFamiliesApiClientInterface
{
    /**
     * @return array<array-key, array{code: string, labels: array{localeCode: string}, standard_unit_code: string, units: array{unitCode: array{code: string, labels: array<string, string>, convert_from_standard: array{operator: string, value: string}, symbol: string}}}>
     * @throws \HttpException
     * @throws GuzzleException
     */
    public function getMeasurementFamilies(): array;
}
