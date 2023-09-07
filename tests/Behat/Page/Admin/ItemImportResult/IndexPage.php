<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Page\Admin\ItemImportResult;

use Sylius\Behat\Page\Admin\Crud\IndexPage as BaseIndexPage;

final class IndexPage extends BaseIndexPage implements IndexPageInterface
{
    public function chooseSuccessfulFilter(string $successful): void
    {
        $this->getElement('filter_successful')->selectOption($successful);
    }

    public function specifyEntityFilter(string $entity): void
    {
        $this->getElement('filter_entity')->setValue($entity);
    }

    public function specifyIdentifierFilter(string $identifier): void
    {
        $this->getElement('filter_identifier')->setValue($identifier);
    }

    protected function getDefinedElements(): array
    {
        return array_merge(
            parent::getDefinedElements(),
            [
                'filter_successful' => '#criteria_successful',
                'filter_entity' => '#criteria_akeneoEntity_value',
                'filter_identifier' => '#criteria_akeneoIdentifier_value',
            ],
        );
    }
}
