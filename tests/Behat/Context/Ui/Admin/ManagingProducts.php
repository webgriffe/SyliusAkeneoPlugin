<?php


namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Ui\Admin;


use Behat\Behat\Context\Context;
use Sylius\Behat\Page\Admin\Product\IndexPageInterface;
use Webmozart\Assert\Assert;

final class ManagingProducts implements Context
{
    /**
     * @var IndexPageInterface
     */
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
}
