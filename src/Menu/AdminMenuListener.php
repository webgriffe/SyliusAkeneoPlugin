<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Menu;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class AdminMenuListener
{
    public function addAdminMenuItems(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();
        $catalogMenu = $menu->getChild('catalog');
        if ($catalogMenu === null) {
            return;
        }

        $catalogMenu
            ->addChild('webgriffe_sylius_akeneo.item_import_result', ['route' => 'webgriffe_sylius_akeneo_admin_item_import_result_index'])
            ->setLabel('webgriffe_sylius_akeneo.ui.import')
            ->setLabelAttribute('icon', 'cloud download')
        ;
    }
}
