<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Page\Admin\ItemImportResult;

use Sylius\Behat\Page\Admin\Crud\IndexPageInterface as BaseIndexPageInterface;

interface IndexPageInterface extends BaseIndexPageInterface
{
    public function chooseSuccessfulFilter(string $successful): void;

    public function specifyEntityFilter(string $entity): void;

    public function specifyIdentifierFilter(string $identifier): void;
}
