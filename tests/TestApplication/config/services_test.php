<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Sylius\Component\Core\Generator\ImagePathGeneratorInterface;
use Sylius\Component\Core\Generator\UploadedImagePathGenerator;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\InMemoryAkeneoPimClient;
use Tests\Webgriffe\SyliusAkeneoPlugin\TestDouble\DateTimeBuilder;

return static function (ContainerConfigurator $container) {
    if (str_starts_with((string) $container->env(), 'test')) {
        $container->import('../../../vendor/sylius/sylius/src/Sylius/Behat/Resources/config/services.xml');
        $container->import('@WebgriffeSyliusAkeneoPlugin/tests/Behat/Resources/services.xml');

        $services = $container->services();

        $services->set('webgriffe_sylius_akeneo.api_client', InMemoryAkeneoPimClient::class);

        $services->set('webgriffe_sylius_akeneo.date_time_builder', DateTimeBuilder::class);

        $services->set(ImagePathGeneratorInterface::class, UploadedImagePathGenerator::class);
    }
};
