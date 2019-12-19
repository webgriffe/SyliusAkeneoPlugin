<?php

declare(strict_types=1);

namespace spec\Webgriffe\SyliusAkeneoPlugin\ProductModel;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTranslation;
use Sylius\Component\Core\Model\ProductTranslationInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Webgriffe\SyliusAkeneoPlugin\ProductModel\NameValueHandler;
use Webgriffe\SyliusAkeneoPlugin\ProductModel\ValueHandlerInterface;

class NameValueHandlerSpec extends ObjectBehavior
{
    function let(FactoryInterface $productTranslationFactory)
    {
        $this->beConstructedWith($productTranslationFactory);
    }

    function it_implements_value_handler_interface()
    {
        $this->shouldHaveType(ValueHandlerInterface::class);
    }

    function it_sets_name_on_an_already_existent_product_translation(
        FactoryInterface $productTranslationFactory,
        ProductTranslationInterface $existingProductTranslation,
        ProductTranslationInterface $newProductTranslation
    ) {
        $productTranslationFactory->createNew()->willReturn($newProductTranslation);
        $product = new Product();
        $product->addTranslation($existingProductTranslation->getWrappedObject());

        $this->handle($product, 'name', [['locale' => 'en_US', 'scope' => null, 'data' => 'New name']]);

        $existingProductTranslation->setName('New name')->shouldHaveBeenCalled();
    }

    function it_sets_name_on_a_not_existent_translation(

    ) {

    }
}
