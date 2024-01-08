<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Webgriffe\SyliusAkeneoPlugin\ImporterRegistry;
use Webgriffe\SyliusAkeneoPlugin\ImporterRegistryInterface;
use Webgriffe\SyliusAkeneoPlugin\ReconcilerRegistry;

return static function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();

    $services->set('webgriffe_sylius_akeneo.importer_registry', ImporterRegistry::class);

    $services->alias(ImporterRegistryInterface::class, 'webgriffe_sylius_akeneo.importer_registry');

    $services->set('webgriffe_sylius_akeneo.reconciler_registry', ReconcilerRegistry::class);
};
