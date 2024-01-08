<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Akeneo\Pim\ApiClient\AkeneoPimClient;
use Akeneo\Pim\ApiClient\AkeneoPimClientBuilder;
use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;

return static function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();

    $services->set('webgriffe_sylius_akeneo.api_client.factory', AkeneoPimClientBuilder::class)
        ->args([
            param('webgriffe_sylius_akeneo.api_client.base_url'),
        ])
    ;

    $services->set('webgriffe_sylius_akeneo.api_client', AkeneoPimClient::class)
        ->factory([service('webgriffe_sylius_akeneo.api_client.factory'), 'buildAuthenticatedByPassword'])
        ->args([
            param('webgriffe_sylius_akeneo.api_client.client_id'),
            param('webgriffe_sylius_akeneo.api_client.secret'),
            param('webgriffe_sylius_akeneo.api_client.username'),
            param('webgriffe_sylius_akeneo.api_client.password'),
        ])
    ;

    $services->alias(AkeneoPimClientInterface::class, 'webgriffe_sylius_akeneo.api_client');
};
