<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Ui\Admin;

use Behat\Behat\Context\Context;
use Behat\Mink\Element\NodeElement;
use Sylius\Behat\Page\Admin\Product\IndexPageInterface;
use Webmozart\Assert\Assert;

final class ManagingProducts implements Context
{
    /** @var IndexPageInterface */
    private $indexPage;

    /**
     * ProductItems constructor.
     */
    public function __construct(IndexPageInterface $indexPage)
    {
        $this->indexPage = $indexPage;
    }

    /**
     * @When I browse product item
     */
    public function iBrowseProductItem()
    {
        $this->indexPage->open();
    }

    /**
     * @Then /^I should see (\d+) products in the list$/
     */
    public function iShouldSeeProductsInTheList(int $countProducts)
    {
        Assert::same($this->indexPage->countItems(), $countProducts);
    }

    /**
     * @When /^I click "([^"]*)" button on "([^"]*)" product$/
     */
    public function iClickButtonOnProduct($button, $code)
    {
        /** @var NodeElement $actionsNodeProduct */
        $actionsNodeProduct = $this->indexPage->getActionsForResource(['code' => $code]);

        $actionsNodeProduct->clickLink($button);
    }
}
