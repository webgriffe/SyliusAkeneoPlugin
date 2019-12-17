<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ProductModel;


use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTranslationInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;

final class NameValueHandler implements ValueHandlerInterface
{
    /**
     * @var FactoryInterface
     */
    private $productTranslationFactory;

    public function __construct(FactoryInterface $productTranslationFactory)
    {
        $this->productTranslationFactory = $productTranslationFactory;
    }

    public function supports(ProductInterface $product, string $attribute, array $value): bool
    {
        return $attribute === 'name';
    }

    public function handle(ProductInterface $product, string $attribute, array $value)
    {
        foreach ($value as $item) {
            /** @var ProductTranslationInterface $productTranslation */
            $productTranslation = $this->productTranslationFactory->createNew();
            $productTranslation->setLocale($item['locale']);
            if (!$product->hasTranslation($productTranslation)) {
                $product->addTranslation($productTranslation);
            }
            $product->getTranslation($item['locale'])->setName($item['data']);
        }
    }
}
