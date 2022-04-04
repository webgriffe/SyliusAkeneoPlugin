<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ValueHandler;

use Cocur\Slugify\SlugifyInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTranslationInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Component\Resource\Translation\Provider\TranslationLocaleProviderInterface;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface;
use Webmozart\Assert\Assert;

final class ImmutableSlugValueHandler implements ValueHandlerInterface
{
    private const MAX_DEDUPLICATION_INCREMENT = 100;

    public function __construct(private SlugifyInterface $slugify, private FactoryInterface $productTranslationFactory, private TranslationLocaleProviderInterface $translationLocaleProvider, private RepositoryInterface $productTranslationRepository, private string $akeneoAttributeToSlugify)
    {
    }

    /**
     * @inheritdoc
     */
    public function supports($subject, string $attribute, array $value): bool
    {
        return $subject instanceof ProductVariantInterface && $attribute === $this->akeneoAttributeToSlugify;
    }

    /**
     * @inheritdoc
     */
    public function handle($subject, string $attribute, array $value): void
    {
        if (!$subject instanceof ProductVariantInterface) {
            throw new \InvalidArgumentException(
                sprintf(
                    'This immutable slug value handler only supports instances of %s, %s given.',
                    ProductVariantInterface::class,
                    get_debug_type($subject)
                )
            );
        }

        $product = $subject->getProduct();
        Assert::isInstanceOf($product, ProductInterface::class);
        foreach ($value as $item) {
            $localeCode = $item['locale'];
            $valueToSlugify = $item['data'];
            Assert::string($valueToSlugify);
            if (!$localeCode) {
                $this->setSlugOnAllTranslations($product, $valueToSlugify);

                continue;
            }

            if (!in_array($localeCode, $this->translationLocaleProvider->getDefinedLocalesCodes(), true)) {
                continue;
            }

            $productTranslation = $this->getOrCreateNewProductTranslation($product, $localeCode);
            if ($productTranslation->getSlug() !== null) {
                continue;
            }
            $slug = $this->slugify->slugify($valueToSlugify);
            $slug = $this->getDeduplicatedSlug($slug, $localeCode, $product);
            $productTranslation->setSlug($slug);
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
            $translation->setLocale($localeCode);
            $product->addTranslation($translation);
        }

        return $translation;
    }

    private function setSlugOnAllTranslations(ProductInterface $product, string $valueToSlugify): void
    {
        foreach ($this->translationLocaleProvider->getDefinedLocalesCodes() as $localeCode) {
            $productTranslation = $this->getOrCreateNewProductTranslation($product, $localeCode);
            if ($productTranslation->getSlug() !== null) {
                continue;
            }
            $slug = $this->slugify->slugify($valueToSlugify);
            $slug = $this->getDeduplicatedSlug($slug, $localeCode, $product);
            $productTranslation->setSlug($slug);
        }
    }

    private function getDeduplicatedSlug(
        string $slug,
        string $localeCode,
        ProductInterface $product,
        int $_increment = 0
    ): string {
        if ($_increment > self::MAX_DEDUPLICATION_INCREMENT) {
            throw new \RuntimeException('Maximum slug deduplication increment reached.');
        }
        $deduplicatedSlug = $slug;
        if ($_increment > 0) {
            $deduplicatedSlug .= '-' . $_increment;
        }

        /** @var ProductTranslationInterface|null $anotherProductTranslation */
        $anotherProductTranslation = $this->productTranslationRepository->findOneBy(
            ['slug' => $deduplicatedSlug, 'locale' => $localeCode]
        );
        if ($anotherProductTranslation !== null &&
            $anotherProductTranslation->getTranslatable() instanceof ProductInterface &&
            $anotherProductTranslation->getTranslatable()->getId() !== $product->getId()) {
            return $this->getDeduplicatedSlug($slug, $localeCode, $product, ++$_increment);
        }

        return $deduplicatedSlug;
    }
}
