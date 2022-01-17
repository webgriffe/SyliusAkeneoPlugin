<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Converter;

interface UnitMeasurementValueConverterInterface
{
    public function convert(string $amount, string $sourceUnitMeasurementCode, ?string $destinationUnitMeasurementCode): float;
}
