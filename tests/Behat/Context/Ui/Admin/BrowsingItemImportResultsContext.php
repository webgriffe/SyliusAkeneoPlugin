<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Ui\Admin;

use Behat\Behat\Context\Context;
use Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Page\Admin\ItemImportResult\IndexPageInterface;
use Webmozart\Assert\Assert;

final class BrowsingItemImportResultsContext implements Context
{
    public function __construct(private IndexPageInterface $indexPage)
    {
    }

    /**
     * @When /^I browse the import from Akeneo results history$/
     */
    public function iBrowseTheImportFromAkeneoResultsHistory(): void
    {
        $this->indexPage->open();
    }

    /**
     * @Then /^I should see (\d+) import result items in the list$/
     */
    public function iShouldSeeImportResultItemsInTheList(int $count): void
    {
        Assert::eq($this->indexPage->countItems(), $count);
    }

    /**
     * @When /^I choose "([^"]*)" as a successful filter$/
     */
    public function iChooseAsASuccessfulFilter(string $successful): void
    {
        $this->indexPage->chooseSuccessfulFilter($successful);
    }

    /**
     * @When /^I filter$/
     */
    public function iFilter(): void
    {
        $this->indexPage->filter();
    }

    /**
     * @When /^I specify "([^"]*)" as an entity filter$/
     */
    public function iSpecifyAsAnEntityFilter(string $entity): void
    {
        $this->indexPage->specifyEntityFilter($entity);
    }

    /**
     * @When /^I specify "([^"]*)" as an identifier filter$/
     */
    public function iSpecifyAsAnIdentifierFilter(string $identifier): void
    {
        $this->indexPage->specifyIdentifierFilter($identifier);
    }
}
