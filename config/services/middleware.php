<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Webgriffe\SyliusAkeneoPlugin\Middleware\ItemImportResultPersisterMiddleware;

return static function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();

    $services->set('webgriffe_sylius_akeneo.middleware.item_import_result_persister', ItemImportResultPersisterMiddleware::class)
        ->args([
            service('doctrine.orm.entity_manager'),
            service('webgriffe_sylius_akeneo.repository.item_import_result'),
            service('monolog.logger.webgriffe_sylius_akeneo_plugin'),
        ])
    ;
};
