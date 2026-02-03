<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Webgriffe\SyliusAkeneoPlugin\Entity\ItemImportResult;

return static function (ContainerConfigurator $container): void {
    $container->extension('sylius_grid', [
        'grids' => [
            'sylius_admin_product' => [
                'actions' => [
                    'item' => [
                        'import' => [
                            'type' => 'show',
                            'label' => 'webgriffe_sylius_akeneo.ui.schedule_import',
                            'icon' => 'tabler:cloud-download',
                            'options' => [
                                'link' => [
                                    'route' => 'webgriffe_sylius_akeneo_product_import',
                                    'parameters' => [
                                        'productId' => 'resource.id',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'webgriffe_sylius_akeneo_admin_item_import_result' => [
                'driver' => [
                    'name' => 'doctrine/orm',
                    'options' => [
                        'class' => ItemImportResult::class,
                    ],
                ],
                'sorting' => [
                    'createdAt' => 'desc',
                ],
                'fields' => [
                    'createdAt' => [
                        'type' => 'datetime',
                        'label' => 'sylius.ui.created_at',
                        'sortable' => true,
                    ],
                    'akeneoEntity' => [
                        'type' => 'string',
                        'label' => 'webgriffe_sylius_akeneo.ui.entity',
                        'sortable' => true,
                    ],
                    'akeneoIdentifier' => [
                        'type' => 'string',
                        'label' => 'webgriffe_sylius_akeneo.ui.identifier',
                        'sortable' => true,
                    ],
                    'successful' => [
                        'type' => 'twig',
                        'label' => 'webgriffe_sylius_akeneo.ui.successful',
                        'options' => [
                            'template' => '@WebgriffeSyliusAkeneoPlugin\ItemImportResult\Grid\Field\successful.html.twig',
                        ],
                    ],
                    'message' => [
                        'type' => 'string',
                        'label' => 'webgriffe_sylius_akeneo.ui.message',
                    ],
                ],
                'filters' => [
                    'createdAt' => [
                        'type' => 'date',
                        'label' => 'sylius.ui.created_at',
                    ],
                    'akeneoEntity' => [
                        'type' => 'string',
                        'label' => 'webgriffe_sylius_akeneo.ui.entity',
                    ],
                    'akeneoIdentifier' => [
                        'type' => 'string',
                        'label' => 'webgriffe_sylius_akeneo.ui.identifier',
                    ],
                    'successful' => [
                        'type' => 'boolean',
                        'label' => 'webgriffe_sylius_akeneo.ui.successful',
                    ],
                    'message' => [
                        'type' => 'string',
                        'label' => 'webgriffe_sylius_akeneo.ui.message',
                    ],
                ],
            ],
        ],
    ]);
};
