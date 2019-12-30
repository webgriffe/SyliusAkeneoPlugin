<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ProductModel;

use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTranslationInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Translation\Provider\TranslationLocaleProviderInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

final class TranslatablePropertyValueHandler implements ValueHandlerInterface
{
    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

    /** @var FactoryInterface */
    private $productTranslationFactory;

    /**
     * @var TranslationLocaleProviderInterface
     */
    private $localeProvider;

    /** @var string */
    private $akeneoAttributeCode;

    /** @var string */
    private $translationPropertyPath;

    public function __construct(
        PropertyAccessorInterface $propertyAccessor,
        FactoryInterface $productTranslationFactory,
        TranslationLocaleProviderInterface $localeProvider,
        string $akeneoAttributeCode,
        string $translationPropertyPath
    ) {
        $this->propertyAccessor = $propertyAccessor;
        $this->productTranslationFactory = $productTranslationFactory;
        $this->localeProvider = $localeProvider;
        $this->akeneoAttributeCode = $akeneoAttributeCode;
        $this->translationPropertyPath = $translationPropertyPath;
    }

    public function supports(ProductInterface $product, string $attribute, array $value): bool
    {
        return $attribute === $this->akeneoAttributeCode;
    }

    public function handle(ProductInterface $product, string $attribute, array $value)
    {
        if (!$this->supports($product, $attribute, $value)) {
            throw new \InvalidArgumentException('Cannot handle');
        }
        foreach ($value as $item) {
            $localeCode = $item['locale'];
            if (!$localeCode) {
                $this->setValueOnAllTranslations($product, $item);

                continue;
            }
            $translation = $this->getOrCreateNewProductTranslation($product, $localeCode);
            $this->propertyAccessor->setValue(
                $translation,
                $this->translationPropertyPath,
                $item['data']
            );
        }
    }

    private function setValueOnAllTranslations(ProductInterface $product, array $value): void
    {
        foreach ($this->localeProvider->getDefinedLocalesCodes() as $localeCode) {
            $translation = $this->getOrCreateNewProductTranslation($product, $localeCode);
            $this->propertyAccessor->setValue(
                $translation,
                $this->translationPropertyPath,
                $value['data']
            );
        }
    }

    private function getOrCreateNewProductTranslation(
        ProductInterface $product,
        string $localeCode
    ): ProductTranslationInterface {
        $translation = $product->getTranslation($localeCode);
        if ($translation->getLocale() !== $localeCode) {
            /** @var ProductTranslationInterface $translation */
            $translation = $this->productTranslationFactory->createNew();
            $translation->setLocale($localeCode);
            $product->addTranslation($translation);
        }
        return $translation;
    }
}
