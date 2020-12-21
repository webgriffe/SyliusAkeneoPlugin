<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Setup;

use Behat\Behat\Context\Context;
use Sylius\Behat\NotificationType;
use Sylius\Behat\Service\Helper\JavaScriptTestHelperInterface;
use Sylius\Behat\Service\NotificationCheckerInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTranslationInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Sylius\Component\Product\Factory\ProductFactoryInterface;
use Sylius\Component\Product\Factory\ProductVariantFactoryInterface;
use Sylius\Component\Product\Generator\SlugGeneratorInterface;
use Sylius\Component\Product\Repository\ProductRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

final class ProductContext implements Context
{
    /** @var ProductFactoryInterface */
    private $productFactory;

    /** @var ProductRepositoryInterface */
    private $productRepository;

    /** @var ProductVariantFactoryInterface */
    private $productVariantFactory;

    /** @var ProductVariantRepositoryInterface */
    private $productVariantRepository;

    /** @var FactoryInterface */
    private $productTranslationFactory;

    /** @var RepositoryInterface */
    private $productTranslationRepository;

    /** @var LocaleContextInterface */
    private $localeContext;

    /** @var SlugGeneratorInterface */
    private $slugGenerator;

    /** @var JavaScriptTestHelperInterface */
    private $testHelper;

    /** @var NotificationCheckerInterface */
    private $notificationChecker;

    /**
     * ProductContext constructor.
     */
    public function __construct(
        ProductFactoryInterface $productFactory,
        ProductVariantFactoryInterface $productVariantFactory,
        ProductVariantRepositoryInterface $productVariantRepository,
        ProductRepositoryInterface $productRepository,
        FactoryInterface $productTranslationFactory,
        RepositoryInterface $productTranslationRepository,
        LocaleContextInterface $localeContext,
        SlugGeneratorInterface $slugGenerator,
        JavaScriptTestHelperInterface $testHelper,
        NotificationCheckerInterface $notificationChecker
    ) {
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
        $this->productVariantFactory = $productVariantFactory;
        $this->productVariantRepository = $productVariantRepository;
        $this->productTranslationFactory = $productTranslationFactory;
        $this->productTranslationRepository = $productTranslationRepository;
        $this->localeContext = $localeContext;
        $this->slugGenerator = $slugGenerator;
        $this->testHelper = $testHelper;
        $this->notificationChecker = $notificationChecker;
    }

    /**
     * @Given there is a product item with identifier :arg1
     */
    public function thereIsAProductItemWithIdentifier($code)
    {
        /** @var ProductInterface $productItem */
        $productItem = $this->productFactory->createNew();
        $productItem->setCode($code);
        $productItem->setCreatedAt(new \DateTime());
        $productItem->setEnabled(true);
        $productItem->setVariantSelectionMethod('match');
        $this->productRepository->add($productItem);

        /** @var ProductVariantInterface $productVariant */
        $productVariant = $this->productVariantFactory->createNew();
        $productVariant->setName('test');
        $productVariant->setCode($code);
        $productVariant->setPosition(1);
        $productVariant->setProduct($productItem);
        $productVariant->setEnabled(true);
        $this->productVariantRepository->add($productVariant);

        /** @var ProductTranslationInterface $productTranslation */
        $productTranslation = $this->productTranslationFactory->createNew(ProductTranslationInterface::class);
        $productTranslation->setName($code);
        $productTranslation->setSlug($this->slugGenerator->generate($code));
        $productTranslation->setTranslatable($productItem);
        $productTranslation->setLocale($this->localeContext->getLocaleCode());
        $this->productTranslationRepository->add($productTranslation);
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
