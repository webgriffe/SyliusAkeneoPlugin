<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Webgriffe\SyliusAkeneoPlugin\DateTimeBuilder;
use Webgriffe\SyliusAkeneoPlugin\DateTimeBuilderInterface;

return static function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();

    $services->set('webgriffe_sylius_akeneo.date_time_builder', DateTimeBuilder::class);

    $services->alias(DateTimeBuilderInterface::class, 'webgriffe_sylius_akeneo.date_time_builder');
};
