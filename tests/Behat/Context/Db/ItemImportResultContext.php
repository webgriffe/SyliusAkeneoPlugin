<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Db;

use Behat\Behat\Context\Context;
use RuntimeException;
use Sylius\Behat\Service\SharedStorageInterface;
use Throwable;
use Webgriffe\SyliusAkeneoPlugin\Respository\ItemImportResultRepositoryInterface;
use Webmozart\Assert\Assert;

final class ItemImportResultContext implements Context
{
    public function __construct(
        private ItemImportResultRepositoryInterface $itemImportResultRepository,
        private SharedStorageInterface $sharedStorage,
    ) {
    }

    /**
     * @Given /^this error should also be logged in the database$/
     */
    public function thisErrorShouldAlsoBeLoggedInTheDatabase(): void
    {
        $error = $this->sharedStorage->get('error');
        Assert::isInstanceOf($error, Throwable::class);

        $itemImportResults = $this->itemImportResultRepository->findAll();
        foreach ($itemImportResults as $itemImportResult) {
            if ($error->getMessage() === $itemImportResult->getMessage()) {
                return;
            }
        }

        throw new RuntimeException(sprintf('Log entry with message "%s" not found.', $error->getMessage()));
    }
}
