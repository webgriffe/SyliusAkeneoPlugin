<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Webgriffe\SyliusAkeneoPlugin\MessageHandler\ItemImportHandler;

return static function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();

    $services->set('webgriffe_sylius_akeneo.message_handler.item_import', ItemImportHandler::class)
        ->args([
            service('webgriffe_sylius_akeneo.importer_registry'),
            service('webgriffe_sylius_akeneo.temporary_file_manager'),
        ])
        ->tag('messenger.message_handler')
    ;
};
