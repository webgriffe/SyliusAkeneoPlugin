<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Psr\Container\ContainerInterface;
use Webgriffe\SyliusAkeneoPlugin\Controller\ProductImportController;
use Webgriffe\SyliusAkeneoPlugin\Controller\WebhookController;

return static function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();

    $services->set('webgriffe_sylius_akeneo.controller.product_import_controller', ProductImportController::class)
        ->args([
            service('sylius.repository.product'),
            service('webgriffe_sylius_akeneo.command_bus'),
            service('translator'),
        ])
        ->call('setContainer', [service('service_container')])
        ->tag('controller.service_arguments')
    ;

    $services->set('webgriffe_sylius_akeneo.controller.webhook', WebhookController::class)
        ->args([
            service('monolog.logger.webgriffe_sylius_akeneo_plugin'),
            service('webgriffe_sylius_akeneo.command_bus'),
            param('webgriffe_sylius_akeneo.webhook.secret'),
            service('event_dispatcher'),
        ])
        ->call('setContainer', [service('service_container')])
        ->tag('controller.service_arguments')
    ;
};
