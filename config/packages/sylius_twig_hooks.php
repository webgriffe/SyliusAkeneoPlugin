<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('sylius_twig_hooks', [
        'hooks' => [
            'sylius_admin.product.show.content.header.title_block.actions' => [
                'import' => [
                    'template' => '@WebgriffeSyliusAkeneoPlugin/admin/shared/crud/show/content/header/title_block/actions/import.html.twig',
                    'priority' => 200,
                ],
            ],
        ],
    ]);
};
