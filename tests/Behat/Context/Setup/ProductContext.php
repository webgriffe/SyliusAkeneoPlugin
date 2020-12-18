<?php


namespace Tests\Webgriffe\SyliusAkeneoPlugin\Behat\Context\Setup;


use Behat\Behat\Context\Context;
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
    /**
     * @var ProductFactoryInterface
     */
    private $productFactory;
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ProductVariantFactoryInterface
     */
    private $productVariantFactory;
    /**
     * @var ProductVariantRepositoryInterface
     */
    private $productVariantRepository;
    /**
     * @var FactoryInterface
     */
    private $productTranslationFactory;
    /**
     * @var RepositoryInterface
     */
    private $productTranslationRepository;
    /**
     * @var LocaleContextInterface
     */
    private $localeContext;
    /**
     * @var SlugGeneratorInterface
     */
    private $slugGenerator;

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
        SlugGeneratorInterface $slugGenerator
    )
    {
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
        $this->productVariantFactory = $productVariantFactory;
        $this->productVariantRepository = $productVariantRepository;
        $this->productTranslationFactory = $productTranslationFactory;
        $this->productTranslationRepository = $productTranslationRepository;
        $this->localeContext = $localeContext;
        $this->slugGenerator = $slugGenerator;
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

}
