<?php

namespace Webgriffe\SyliusAkeneoPlugin\Converter;

interface DefaultUnitMeasurementValueConverterInterface
{
    public function convert(string $amount, string $unitMeasurementCode, ?string $defaultAkeneoUnitMeasurementCode): float;
}
