<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Setup;

use Behat\Behat\Context\Context;
use Webgriffe\SyliusAkeneoPlugin\Entity\ItemImportResult;
use Webgriffe\SyliusAkeneoPlugin\Respository\ItemImportResultRepositoryInterface;

final class ItemImportResultContext implements Context
{
    public function __construct(private ItemImportResultRepositoryInterface $itemImportResultRepository)
    {
    }

    /**
     * @Given /^there is a (successful|failed) import result for an item with identifier "([^"]*)" for the "([^"]*)" entity$/
     */
    public function thereIsASuccessfulImportResultForAnItemWithIdentifierForTheEntity(string $successfulOrFailed, string $akeneoIdentifier, string $akeneoEntity): void
    {
        $successful = $successfulOrFailed === 'successful';

        $this->itemImportResultRepository->add(
            new ItemImportResult($akeneoEntity, $akeneoIdentifier, $successful, ''),
        );
    }
}
