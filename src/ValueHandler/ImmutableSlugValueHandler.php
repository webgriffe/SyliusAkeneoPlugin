<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ValueHandler;

use Cocur\Slugify\SlugifyInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTranslationInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Translation\Provider\TranslationLocaleProviderInterface;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface;
use Webmozart\Assert\Assert;

final class ImmutableSlugValueHandler implements ValueHandlerInterface
{
    /** @var SlugifyInterface */
    private $slugify;

    /** @var FactoryInterface */
    private $productTranslationFactory;

    /** @var TranslationLocaleProviderInterface */
    private $translationLocaleProvider;

    /** @var string */
    private $akeneoAttributeToSlugify;

    public function __construct(
        SlugifyInterface $slugify,
        FactoryInterface $productTranslationFactory,
        TranslationLocaleProviderInterface $translationLocaleProvider,
        string $akeneoAttributeToSlugify
    ) {
        $this->slugify = $slugify;
        $this->productTranslationFactory = $productTranslationFactory;
        $this->translationLocaleProvider = $translationLocaleProvider;
        $this->akeneoAttributeToSlugify = $akeneoAttributeToSlugify;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($subject, string $attribute, array $value): bool
    {
        return $subject instanceof ProductVariantInterface && $attribute === $this->akeneoAttributeToSlugify;
    }

    /**
     * {@inheritdoc}
     */
    public function handle($subject, string $attribute, array $value): void
    {
        if (!$subject instanceof ProductVariantInterface) {
            throw new \InvalidArgumentException(
                sprintf(
                    'This immutable slug value handler only supports instances of %s, %s given.',
                    ProductVariantInterface::class,
                    is_object($subject) ? get_class($subject) : gettype($subject)
                )
            );
        }

        /** @var ProductInterface $product */
        $product = $subject->getProduct();
        Assert::isInstanceOf($product, ProductInterface::class);
        foreach ($value as $item) {
            $localeCode = $item['locale'];
            $valueToSlugify = $item['data'];
            Assert::string($valueToSlugify);
            /** @var string $valueToSlugify */
            if (!$localeCode) {
                $this->setSlugOnAllTranslations($product, $valueToSlugify);

                continue;
            }
            $productTranslation = $this->getOrCreateNewProductTranslation($product, $localeCode);
            if ($productTranslation->getSlug()) {
                continue;
            }
            $productTranslation->setSlug($this->slugify->slugify($valueToSlugify));
        }
    }

    private function getOrCreateNewProductTranslation(
        ProductInterface $product,
        string $localeCode
    ): ProductTranslationInterface {
        $translation = $product->getTranslation($localeCode);
        if ($translation->getLocale() !== $localeCode) {
            $translation = $this->productTranslationFactory->createNew();
            Assert::isInstanceOf($translation, ProductTranslationInterface::class);
            /** @var ProductTranslationInterface $translation */
            $translation->setLocale($localeCode);
            $product->addTranslation($translation);
        }

        return $translation;
    }

    private function setSlugOnAllTranslations(ProductInterface $product, string $valueToSlugify): void
    {
        foreach ($this->translationLocaleProvider->getDefinedLocalesCodes() as $localeCode) {
            $productTranslation = $this->getOrCreateNewProductTranslation($product, $localeCode);
            if ($productTranslation->getSlug()) {
                continue;
            }
            $productTranslation->setSlug($this->slugify->slugify($valueToSlugify));
        }
    }
}
