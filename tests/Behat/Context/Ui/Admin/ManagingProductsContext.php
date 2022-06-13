<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Ui\Admin;

use Behat\Behat\Context\Context;
use Behat\Mink\Element\NodeElement;
use Sylius\Behat\NotificationType;
use Sylius\Behat\Page\Admin\Product\IndexPageInterface;
use Sylius\Behat\Service\Helper\JavaScriptTestHelperInterface;
use Sylius\Behat\Service\NotificationCheckerInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Webmozart\Assert\Assert;

final class ManagingProductsContext implements Context
{
    private const SCHEDULE_AKENEO_PIM_IMPORT = 'Schedule Akeneo PIM import';

    public function __construct(
        private IndexPageInterface $indexPage,
        private JavaScriptTestHelperInterface $testHelper,
        private NotificationCheckerInterface $notificationChecker
    ) {
    }

    /**
     * @When /^I schedule an Akeneo PIM import for the ("[^"]*" product)$/
     */
    public function scheduleAnAkeneoPimImportForTheProduct(ProductInterface $product): void
    {
        /** @var NodeElement $actionsNodeProduct */
        $actionsNodeProduct = $this->indexPage->getActionsForResource(['code' => $product->getCode()]);

        $actionsNodeProduct->clickLink(self::SCHEDULE_AKENEO_PIM_IMPORT);
    }

    /**
     * @Then I should be notified that it has been successfully enqueued
     */
    public function iShouldBeNotifiedThatItHasBeenSuccessfullyEnqueued(): void
    {
        $this->testHelper->waitUntilNotificationPopups(
            $this->notificationChecker,
            NotificationType::success(),
            'Akeneo PIM product import has been successfully scheduled',
        );
    }

    /**
     * @Given /^I should be notified that it has been already enqueued$/
     */
    public function iShouldBeNotifiedThatItHasBeenAlreadyEnqueued(): void
    {
        $this->testHelper->waitUntilNotificationPopups(
            $this->notificationChecker,
            NotificationType::success(),
            'Akeneo PIM import for this product has been already scheduled before',
        );
    }

    /**
     * @Then /^I should see (\d+), not imported, items? in the Akeneo queue items list$/
     */
    public function iShouldSeeNotImportedItemInTheAkeneoQueueItemsList(int $numberOfItems): void
    {
        $this->queueItemsIndexPage->open();
        Assert::same($this->queueItemsIndexPage->countItems(), $numberOfItems);
        foreach ($this->indexPage->getColumnFields('importedAt') as $columnField) {
            Assert::eq($columnField, 'No');
        }
    }
}
