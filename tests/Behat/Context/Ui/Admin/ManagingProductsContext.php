<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Ui\Admin;

use Behat\Behat\Context\Context;
use Behat\Mink\Element\NodeElement;
use Sylius\Behat\Element\Admin\Product\AssociationsFormElementInterface;
use Sylius\Behat\NotificationType;
use Sylius\Behat\Page\Admin\Product\IndexPageInterface;
use Sylius\Behat\Page\Admin\Product\UpdateSimpleProductPageInterface;
use Sylius\Behat\Service\Helper\JavaScriptTestHelperInterface;
use Sylius\Behat\Service\NotificationCheckerInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Product\Model\ProductAssociationTypeInterface;
use Webmozart\Assert\Assert;

final class ManagingProductsContext implements Context
{
    private const SCHEDULE_AKENEO_PIM_IMPORT = 'Schedule Akeneo PIM import';

    public function __construct(
        private IndexPageInterface $indexPage,
        private JavaScriptTestHelperInterface $testHelper,
        private NotificationCheckerInterface $notificationChecker,
        private UpdateSimpleProductPageInterface $updateSimpleProductPage,
        private ProductRepositoryInterface $productRepository,
        private AssociationsFormElementInterface $associationsFormElement,
    ) {
    }

    /**
     * @When /^I schedule an Akeneo PIM import for the ("[^"]*" product)$/
     */
    public function scheduleAnAkeneoPimImportForTheProduct(ProductInterface $product): void
    {
        /** @var NodeElement $actionsNodeProduct */
        $actionsNodeProduct = $this->indexPage->getActionsForResource(['code' => $product->getCode()]);
        $linkButton = $actionsNodeProduct->find(
            'css',
            '[data-test-show-action="' . self::SCHEDULE_AKENEO_PIM_IMPORT . '"]',
        );
        Assert::notNull($linkButton);

        $linkButton->click();
    }

    /**
     * @Then I should be notified that :variantCode has been successfully enqueued
     */
    public function iShouldBeNotifiedThatItHasBeenSuccessfullyEnqueued(string $variantCode): void
    {
        $this->testHelper->waitUntilNotificationPopups(
            $this->notificationChecker,
            NotificationType::success(),
            sprintf('Akeneo PIM import for product "%s" has been successfully scheduled', $variantCode),
        );
    }

    /**
     * @Then the product with code :code should have an association :productAssociationType with product :productCode
     */
    public function theProductShouldHaveAnAssociationWithProducts(
        string $code,
        ProductAssociationTypeInterface $productAssociationType,
        string $productCode,
    ): void {
        $product = $this->productRepository->findOneByCode($code);
        Assert::isInstanceOf($product, ProductInterface::class);
        $this->updateSimpleProductPage->open(['id' => $product->getId()]);
        $associatedProduct = $this->productRepository->findOneByCode($productCode);
        Assert::notNull($associatedProduct);
        Assert::true(
            $this->associationsFormElement->hasAssociatedProduct($associatedProduct, $productAssociationType),
            sprintf(
                'This product should have an association %s with product %s.',
                $productAssociationType->getName(),
                $productCode,
            ),
        );
    }

    /**
     * @Then the product with code :code should not have an association :productAssociationType with product :productCode
     */
    public function theProductShouldNotHaveAnAssociationWithProduct(
        string $code,
        ProductAssociationTypeInterface $productAssociationType,
        string $productCode,
    ): void {
        $product = $this->productRepository->findOneByCode($code);
        Assert::isInstanceOf($product, ProductInterface::class);
        $this->updateSimpleProductPage->open(['id' => $product->getId()]);
        $associatedProduct = $this->productRepository->findOneByCode($productCode);
        Assert::notNull($associatedProduct);
        Assert::false(
            $this->associationsFormElement->hasAssociatedProduct($associatedProduct, $productAssociationType),
            sprintf(
                'This product should have an association %s with product %s.',
                $productAssociationType->getName(),
                $productCode,
            ),
        );
    }
}
