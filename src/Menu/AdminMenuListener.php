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
            ->addChild('webgriffe_sylius_akeneo.queue_item', ['route' => 'webgriffe_sylius_akeneo_admin_queue_item_index'])
            ->setLabel('webgriffe_sylius_akeneo.ui.queue_items')
            ->setLabelAttribute('icon', 'cloud download')
        ;
    }
}
