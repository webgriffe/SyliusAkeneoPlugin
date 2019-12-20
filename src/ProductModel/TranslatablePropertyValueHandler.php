<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ProductModel;


use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTranslationInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

final class TranslatablePropertyValueHandler implements ValueHandlerInterface
{
    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;
    /**
     * @var FactoryInterface
     */
    private $productTranslationFactory;
    /**
     * @var string
     */
    private $akeneoAttributeCode;
    /**
     * @var string
     */
    private $translationPropertyPath;

    public function __construct(
        PropertyAccessorInterface $propertyAccessor,
        FactoryInterface $productTranslationFactory,
        string $akeneoAttributeCode,
        string $translationPropertyPath
    ) {
        $this->propertyAccessor = $propertyAccessor;
        $this->productTranslationFactory = $productTranslationFactory;
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
            if (!$item['locale']) {
                $this->setValueOnAllTranslations($product, $item);
                continue;
            }
            $translation = $product->getTranslation($item['locale']);
            if ($translation->getLocale() !== $item['locale']) {
                /** @var ProductTranslationInterface $translation */
                $translation = $this->productTranslationFactory->createNew();
                $translation->setLocale($item['locale']);
                $product->addTranslation($translation);
            }
            $this->propertyAccessor->setValue(
                $translation,
                $this->translationPropertyPath,
                $item['data']
            );
        }
    }

    private function setValueOnAllTranslations(ProductInterface $product, array $value)
    {
        foreach ($product->getTranslations() as $translation) {
            $this->propertyAccessor->setValue(
                $translation,
                $this->translationPropertyPath,
                $value['data']
            );
        }
    }
}
