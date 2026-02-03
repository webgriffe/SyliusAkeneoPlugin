<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Webgriffe\SyliusAkeneoPlugin\Message\ItemImport;

return static function (ContainerConfigurator $configurator): void {
    $configurator->extension('framework', [
        'messenger' => [
            'routing' => [
                ItemImport::class => 'main',
            ],

            'buses' => [
                'webgriffe_sylius_akeneo.command_bus' => [
                    'middleware' => [
                        'webgriffe_sylius_akeneo.middleware.item_import_result_persister',
                        # each time a message is handled, the Doctrine connection
                        # is "pinged" and reconnected if it's closed. Useful
                        # if your workers run for a long time and the database
                        # connection is sometimes lost
                        'doctrine_ping_connection',
                        # After handling, the Doctrine connection is closed,
                        # which can free up database connections in a worker,
                        # instead of keeping them open forever
                        'doctrine_close_connection',
                        # logs an error when a Doctrine transaction was opened but not closed
                        'doctrine_open_transaction_logger',
                        # wraps all handlers in a single Doctrine transaction
                        # handlers do not need to call flush() and an error
                        # in any handler will cause a rollback
                        'doctrine_transaction',
                    ],
                ],
            ],
        ],
    ]);
};
