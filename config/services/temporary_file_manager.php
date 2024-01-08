<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Component\Finder\Finder;
use Webgriffe\SyliusAkeneoPlugin\TemporaryFilesManager;

return static function (ContainerConfigurator $containerConfigurator) {
    $containerConfigurator->parameters()->set('webgriffe_sylius_akeneo.temporary_files_prefix', 'akeneo-');
    $services = $containerConfigurator->services();

    $services->set('webgriffe_sylius_akeneo.temporary_file_manager', TemporaryFilesManager::class)
        ->args([
            service('filesystem'),
            inline_service(Finder::class),
            param('webgriffe_sylius_akeneo.temporary_directory'),
            param('webgriffe_sylius_akeneo.temporary_files_prefix'),
        ])
    ;
};
