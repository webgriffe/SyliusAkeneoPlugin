<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Ui\Admin;

use Behat\Behat\Context\Context;
use Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Page\Admin\QueueItem\IndexPageInterface;
use Webmozart\Assert\Assert;

final class ManagingQueueItems implements Context
{
    /** @var IndexPageInterface */
    private $indexPage;

    public function __construct(IndexPageInterface $indexPage)
    {
        $this->indexPage = $indexPage;
    }

    /**
     * @When /^I browse Akeneo queue items$/
     */
    public function iBrowseAkeneoQueueItems(): void
    {
        $this->indexPage->open();
    }

    /**
     * @Then /^I should see (\d+), not imported, queue items in the list$/
     */
    public function iShouldSeeQueueItemsInTheList(int $numberOfItems): void
    {
        Assert::same($this->indexPage->countItems(), $numberOfItems);
        foreach ($this->indexPage->getColumnFields('importedAt') as $columnField) {
            Assert::eq($columnField, 'No');
        }
    }

    /**
     * @Given /^I choose "([^"]*)" as an imported filter$/
     */
    public function iChooseAsAnImportedFilter(string $imported): void
    {
        $this->indexPage->chooseImportedFilter($imported);
    }

    /**
     * @When /^I filter$/
     */
    public function iFilter(): void
    {
        $this->indexPage->filter();
    }

    /**
     * @Then /^I should see (\d+), imported, queue items? in the list$/
     */
    public function iShouldSeeImportedQueueItemInTheList(int $numberOfItems): void
    {
        Assert::same($this->indexPage->countItems(), $numberOfItems);
        foreach ($this->indexPage->getColumnFields('importedAt') as $columnField) {
            Assert::contains($columnField, 'Yes');
        }
    }
}
