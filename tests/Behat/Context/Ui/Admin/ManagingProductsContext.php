<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Ui\Admin;

use Behat\Behat\Context\Context;
use Behat\Mink\Element\NodeElement;
use Sylius\Behat\NotificationType;
use Sylius\Behat\Page\Admin\Product\IndexPageInterface;
use Sylius\Behat\Service\Helper\JavaScriptTestHelperInterface;
use Sylius\Behat\Service\NotificationCheckerInterface;

final class ManagingProductsContext implements Context
{
    private const SCHEDULE_AKENEO_PIM_IMPORT = 'Schedule Akeneo PIM import';

    /** @var IndexPageInterface */
    private $indexPage;

    /** @var JavaScriptTestHelperInterface */
    private $testHelper;

    /** @var NotificationCheckerInterface */
    private $notificationChecker;

    /**
     * ProductItems constructor.
     */
    public function __construct(IndexPageInterface $indexPage, JavaScriptTestHelperInterface $testHelper, NotificationCheckerInterface $notificationChecker)
    {
        $this->indexPage = $indexPage;
        $this->testHelper = $testHelper;
        $this->notificationChecker = $notificationChecker;
    }

    /**
     * @When /^I schedule an Akeneo PIM import for the "([^"]*)" product$/
     */
    public function scheduleAnAkeneoPimImportForTheProduct($code)
    {
        /** @var NodeElement $actionsNodeProduct */
        $actionsNodeProduct = $this->indexPage->getActionsForResource(['code' => $code]);

        $actionsNodeProduct->clickLink(self::SCHEDULE_AKENEO_PIM_IMPORT);
    }

    /**
     * @Then I should be notified that it has been successfully enqueued
     */
    public function iShouldBeNotifiedThatItHasBeenSuccessfullyEnqueued()
    {
        $this->testHelper->waitUntilNotificationPopups(
            $this->notificationChecker, NotificationType::success(), 'Akeneo PIM product import has been successfully scheduled'
        );
    }

    /**
     * @Given /^I should be notified that it has been already enqueued$/
     */
    public function iShouldBeNotifiedThatItHasBeenAlreadyEnqueued()
    {
        $this->testHelper->waitUntilNotificationPopups(
            $this->notificationChecker, NotificationType::success(), 'Akeneo PIM import for this product has been already scheduled before'
        );
    }
}
