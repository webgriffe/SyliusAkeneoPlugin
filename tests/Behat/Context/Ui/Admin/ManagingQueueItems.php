<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Ui\Admin;

use Behat\Behat\Context\Context;
use Sylius\Behat\Service\SharedStorageInterface;
use Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Page\Admin\QueueItem\IndexPageInterface;
use Webgriffe\SyliusAkeneoPlugin\Entity\QueueItemInterface;
use Webmozart\Assert\Assert;

final class ManagingQueueItems implements Context
{
    public function __construct(private IndexPageInterface $indexPage, private SharedStorageInterface $sharedStorage)
    {
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

    /**
     * @When /^I specify "([^"]*)" as an importer filter$/
     */
    public function iSpecifyAsAnImporterFilter(string $importer): void
    {
        $this->indexPage->specifyImporterFilter($importer);
    }

    /**
     * @Then /^I should see (\d+) queue items? in the list$/
     */
    public function iShouldSeeQueueItemInTheList(int $numberOfItems): void
    {
        Assert::same($this->indexPage->countItems(), $numberOfItems);
    }

    /**
     * @Given /^I specify "([^"]*)" as an identifier filter$/
     */
    public function iSpecifyAsAnIdentifierFilter(string $identifier): void
    {
        $this->indexPage->specifyIdentifierFilter($identifier);
    }

    /**
     * @When /^I delete the ("([^"]*)" queue item)$/
     */
    public function iDeleteTheQueueItem(QueueItemInterface $queueItem): void
    {
        $this->indexPage->deleteResourceOnPage(['akeneoIdentifier' => $queueItem->getAkeneoIdentifier()]);

        $this->sharedStorage->set('queue_item', $queueItem);
    }

    /**
     * @Given /^(this queue item) should no longer exist in the queue$/
     */
    public function thisQueueItemShouldNoLongerExistInTheQueue(QueueItemInterface $queueItem): void
    {
        Assert::false(
            $this->indexPage->isSingleResourceOnPage(
                [
                    'akeneoIdentifier' => $queueItem->getAkeneoIdentifier(),
                    'akeneoEntity' => $queueItem->getAkeneoEntity(),
                ],
            ),
        );
    }

    /**
     * @Given /^I check the ("([^"]*)" queue item)$/
     * @Given /^I check also the ("([^"]*)" queue item)$/
     */
    public function iCheckAlsoTheQueueItem(QueueItemInterface $queueItem): void
    {
        $this->indexPage->checkResourceOnPage(['akeneoIdentifier' => $queueItem->getAkeneoIdentifier()]);
    }

    /**
     * @When I delete them
     */
    public function iDeleteThem(): void
    {
        $this->indexPage->bulkDelete();
    }

    /**
     * @Given /^I should see a single queue item in the list$/
     */
    public function iShouldSeeASingleQueueItemInTheList(): void
    {
        Assert::eq($this->indexPage->countItems(), 1);
    }

    /**
     * @Given /^I should see the ("([^"]*)" queue item) in the list$/
     */
    public function iShouldSeeTheQueueItemInTheList(QueueItemInterface $queueItem): void
    {
        Assert::true($this->indexPage->isSingleResourceOnPage(['akeneoIdentifier' => $queueItem->getAkeneoIdentifier()]));
    }
}
