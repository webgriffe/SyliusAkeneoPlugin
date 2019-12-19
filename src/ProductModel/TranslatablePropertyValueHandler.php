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
     * @var FactoryInterface
     */
    private $productTranslationFactory;
    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;
    /**
     * @var string
     */
    private $akeneoAttributeCode;
    /**
     * @var string
     */
    private $translationPropertyPath;

    public function __construct(
        FactoryInterface $productTranslationFactory,
        PropertyAccessorInterface $propertyAccessor,
        string $akeneoAttributeCode,
        string $translationPropertyPath
    ) {
        $this->productTranslationFactory = $productTranslationFactory;
        $this->propertyAccessor = $propertyAccessor;
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
            /** @var ProductTranslationInterface $newTranslation */
            $newTranslation = $this->productTranslationFactory->createNew();
            $newTranslation->setLocale($item['locale']);
            if (!$product->hasTranslation($newTranslation)) {
                $product->addTranslation($newTranslation);
            }
            $translation = $product->getTranslation($item['locale']);
            $this->propertyAccessor->setValue(
                $translation,
                $this->translationPropertyPath,
                $item['data']
            );
        }
    }
}
