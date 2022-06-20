<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ValueHandler;

use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTranslationInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Product\Model\ProductVariantTranslationInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Translation\Provider\TranslationLocaleProviderInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface;
use Webmozart\Assert\Assert;

final class TranslatablePropertyValueHandler implements ValueHandlerInterface
{
    public function __construct(private PropertyAccessorInterface $propertyAccessor, private FactoryInterface $productTranslationFactory, private FactoryInterface $productVariantTranslationFactory, private TranslationLocaleProviderInterface $localeProvider, private string $akeneoAttributeCode, private string $translationPropertyPath)
    {
    }

    /**
     * @param mixed $subject
     */
    public function supports($subject, string $attribute, array $value): bool
    {
        return $subject instanceof ProductVariantInterface && $attribute === $this->akeneoAttributeCode;
    }

    /**
     * @param mixed $subject
     */
    public function handle($subject, string $attribute, array $value): void
    {
        if (!$subject instanceof ProductVariantInterface) {
            throw new \InvalidArgumentException(
                sprintf(
                    'This translatable property value handler only support instances of %s, %s given.',
                    ProductVariantInterface::class,
                    get_debug_type($subject),
                ),
            );
        }

        $availableLocalesCodes = $this->localeProvider->getDefinedLocalesCodes();

        /** @var ProductInterface|null $product */
        $product = $subject->getProduct();
        Assert::isInstanceOf($product, ProductInterface::class);
        $productChannelCodes = array_map(static fn (ChannelInterface $channel): ?string => $channel->getCode(), $product->getChannels()->toArray());

        foreach ($value as $valueData) {
            if (!is_array($valueData)) {
                throw new \InvalidArgumentException(sprintf('Invalid Akeneo value data: expected an array, "%s" given.', gettype($valueData)));
            }
            if (!array_key_exists('scope', $valueData)) {
                throw new \InvalidArgumentException('Invalid Akeneo value data: required "scope" information was not found.');
            }
            if ($valueData['scope'] !== null && !in_array($valueData['scope'], $productChannelCodes, true)) {
                continue;
            }

            $localeCode = $valueData['locale'];
            if (!$localeCode) {
                $this->setValueOnAllTranslations($subject, $valueData);

                continue;
            }

            if (!in_array($localeCode, $availableLocalesCodes, true)) {
                continue;
            }

            $this->setValueOnProductVariantAndProductTranslation($subject, $localeCode, $valueData['data']);
        }
    }

    private function setValueOnAllTranslations(ProductVariantInterface $subject, array $value): void
    {
        foreach ($this->localeProvider->getDefinedLocalesCodes() as $localeCode) {
            $this->setValueOnProductVariantAndProductTranslation($subject, $localeCode, $value['data']);
        }
    }

    /**
     * @param mixed $value
     */
    private function setValueOnProductVariantAndProductTranslation(
        ProductVariantInterface $variant,
        string $localeCode,
        $value,
    ): void {
        if ($value === null) {
            $this->setNullOnExistingProductVariantAndProductTranslation($variant, $localeCode);

            return;
        }

        $hasBeenSet = false;

        $variantTranslation = $this->getOrCreateNewProductVariantTranslation($variant, $localeCode);
        if ($this->propertyAccessor->isWritable($variantTranslation, $this->translationPropertyPath)) {
            $this->propertyAccessor->setValue(
                $variantTranslation,
                $this->translationPropertyPath,
                $value,
            );
            $hasBeenSet = true;
        }

        $product = $variant->getProduct();
        Assert::isInstanceOf($product, ProductInterface::class);
        $productTranslation = $this->getOrCreateNewProductTranslation($product, $variantTranslation->getLocale());
        if ($this->propertyAccessor->isWritable($productTranslation, $this->translationPropertyPath)) {
            $this->propertyAccessor->setValue(
                $productTranslation,
                $this->translationPropertyPath,
                $value,
            );
            $hasBeenSet = true;
        }
        if (!$hasBeenSet) {
            throw new \RuntimeException(
                sprintf(
                    'Property path "%s" is not writable on both %s and %s but it should be for at least once.',
                    $this->translationPropertyPath,
                    ProductVariantTranslationInterface::class,
                    ProductTranslationInterface::class,
                ),
            );
        }
    }

    private function getOrCreateNewProductTranslation(
        ProductInterface $subject,
        string $localeCode,
    ): ProductTranslationInterface {
        $translation = $subject->getTranslation($localeCode);
        if ($translation->getLocale() !== $localeCode) {
            $translation = $this->productTranslationFactory->createNew();
            Assert::isInstanceOf($translation, ProductTranslationInterface::class);
            $translation->setLocale($localeCode);
            $subject->addTranslation($translation);
        }

        return $translation;
    }

    private function getOrCreateNewProductVariantTranslation(
        ProductVariantInterface $subject,
        string $localeCode,
    ): ProductVariantTranslationInterface {
        $translation = $subject->getTranslation($localeCode);
        if ($translation->getLocale() !== $localeCode) {
            $translation = $this->productVariantTranslationFactory->createNew();
            Assert::isInstanceOf($translation, ProductVariantTranslationInterface::class);
            $translation->setLocale($localeCode);
            $subject->addTranslation($translation);
        }

        return $translation;
    }

    private function setNullOnExistingProductVariantAndProductTranslation(
        ProductVariantInterface $variant,
        string $localeCode,
    ): void {
        /** @var ProductVariantTranslationInterface|null $variantTranslation */
        $variantTranslation = $variant->getTranslations()->get($localeCode);
        if ($variantTranslation !== null) {
            if ($this->propertyAccessor->isWritable($variantTranslation, $this->translationPropertyPath)) {
                $this->propertyAccessor->setValue($variantTranslation, $this->translationPropertyPath, null);
            }
        }

        $product = $variant->getProduct();
        Assert::isInstanceOf($product, ProductInterface::class);
        /** @var ProductTranslationInterface|null $productTranslation */
        $productTranslation = $product->getTranslations()->get($localeCode);
        if ($productTranslation !== null) {
            if ($this->propertyAccessor->isWritable($productTranslation, $this->translationPropertyPath)) {
                $this->propertyAccessor->setValue($productTranslation, $this->translationPropertyPath, null);
            }
        }
    }
}
