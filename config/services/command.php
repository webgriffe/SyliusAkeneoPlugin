<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Webgriffe\SyliusAkeneoPlugin\Command\ImportCommand;
use Webgriffe\SyliusAkeneoPlugin\Command\ItemImportResultCleanupCommand;
use Webgriffe\SyliusAkeneoPlugin\Command\ReconcileCommand;

return static function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();

    $services->set('webgriffe_sylius_akeneo.command.import', ImportCommand::class)
        ->args([
            service('webgriffe_sylius_akeneo.date_time_builder'),
            service('webgriffe_sylius_akeneo.importer_registry'),
            service('webgriffe_sylius_akeneo.command_bus'),
        ])
        ->tag('console.command')
    ;

    $services->set('webgriffe_sylius_akeneo.command.reconcile', ReconcileCommand::class)
        ->args([
            service('webgriffe_sylius_akeneo.reconciler_registry'),
        ])
        ->tag('console.command')
    ;

    $services->set('webgriffe_sylius_akeneo.command.item_import_result_cleanup', ItemImportResultCleanupCommand::class)
        ->args([
            service('webgriffe_sylius_akeneo.repository.item_import_result'),
        ])
        ->tag('console.command')
    ;
};
