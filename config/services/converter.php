<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Webgriffe\SyliusAkeneoPlugin\Converter\UnitMeasurementValueConverter;
use Webgriffe\SyliusAkeneoPlugin\Converter\ValueConverter;

return static function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();

    $services->set('webgriffe_sylius_akeneo.converter.value', ValueConverter::class)
        ->args([
            service('translator'),
        ])
    ;

    $services->set('webgriffe_sylius_akeneo.converter.unit_measurement_value', UnitMeasurementValueConverter::class)
        ->args([
            service('webgriffe_sylius_akeneo.api_client'),
        ])
    ;
};
