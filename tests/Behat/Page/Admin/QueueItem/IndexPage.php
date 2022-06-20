<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Page\Admin\QueueItem;

use Sylius\Behat\Page\Admin\Crud\IndexPage as BaseIndexPage;

class IndexPage extends BaseIndexPage implements IndexPageInterface
{
    public function chooseImportedFilter(string $imported): void
    {
        $this->getElement('filter_imported')->selectOption($imported);
    }

    public function specifyImporterFilter(string $importer): void
    {
        $this->getElement('filter_importer')->setValue($importer);
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
                'filter_imported' => '#criteria_imported',
                'filter_importer' => '#criteria_akeneoEntity_value',
                'filter_identifier' => '#criteria_akeneoIdentifier_value',
            ],
        );
    }
}
