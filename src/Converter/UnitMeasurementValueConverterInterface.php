<?php

namespace Webgriffe\SyliusAkeneoPlugin\Converter;

interface UnitMeasurementValueConverterInterface
{
    public function convert(string $amount, string $sourceUnitMeasurementCode, ?string $destinationUnitMeasurementCode): float;
}
