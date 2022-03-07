<?php

declare(strict_types=1);

namespace spec\Webgriffe\SyliusAkeneoPlugin\ValueHandler;

use Doctrine\Common\Collections\ArrayCollection;
use PhpSpec\ObjectBehavior;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariant;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Webgriffe\SyliusAkeneoPlugin\ValueHandler\GenericPropertyValueHandler;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface;

class GenericPropertyValueHandlerSpec extends ObjectBehavior
{
    private const AKENEO_ATTRIBUTE_CODE = 'akeneo_attribute_code';

    private const PROPERTY_PATH = 'property_path';

    private const NEW_VALUE_DATA = ['locale' => null, 'scope' => null, 'data' => 'New value'];

    public function let(
        PropertyAccessorInterface $propertyAccessor,
        ProductVariantInterface $productVariant,
        ProductInterface $product
    ): void {
        $commerceChannel = new Channel();
        $commerceChannel->setCode('ecommerce');
        $supportChannel = new Channel();
        $supportChannel->setCode('support');
        $product->getChannels()->willReturn(new ArrayCollection([$commerceChannel, $supportChannel]));

        $productVariant->getProduct()->willReturn($product);
        $propertyAccessor->isWritable($productVariant, self::PROPERTY_PATH)->willReturn(true);
        $propertyAccessor->isWritable($product, self::PROPERTY_PATH)->willReturn(true);

        $this->beConstructedWith($propertyAccessor, self::AKENEO_ATTRIBUTE_CODE, self::PROPERTY_PATH);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(GenericPropertyValueHandler::class);
    }

    public function it_implements_value_handler_interface(): void
    {
        $this->shouldHaveType(ValueHandlerInterface::class);
    }

    public function it_supports_provided_akeneo_attribute_code(): void
    {
        $this->supports(new ProductVariant(), self::AKENEO_ATTRIBUTE_CODE, [])->shouldReturn(true);
    }

    public function it_does_not_support_any_other_attribute_except_provided_akeneo_attribute_code(): void
    {
        $this->supports(new ProductVariant(), 'another_attribute', [])->shouldReturn(false);
    }

    public function it_throws_trying_to_handle_not_supported_property(): void
    {
        $this
            ->shouldThrow(
                new \InvalidArgumentException(
                    sprintf(
                        'Cannot handle Akeneo attribute "%s". %s only supports Akeneo attribute "%s".',
                        'not_supported_property',
                        GenericPropertyValueHandler::class,
                        self::AKENEO_ATTRIBUTE_CODE
                    )
                )
            )
            ->during('handle', [new ProductVariant(), 'not_supported_property', []]);
    }

    public function it_sets_value_on_provided_property_path_on_both_product_and_product_variant(
        PropertyAccessorInterface $propertyAccessor,
        ProductVariantInterface $productVariant,
        ProductInterface $product
    ): void {
        $this->handle($productVariant, self::AKENEO_ATTRIBUTE_CODE, [self::NEW_VALUE_DATA]);

        $propertyAccessor->setValue($productVariant, self::PROPERTY_PATH, 'New value')->shouldHaveBeenCalled();
        $propertyAccessor->setValue($product, self::PROPERTY_PATH, 'New value')->shouldHaveBeenCalled();
    }

    public function it_sets_value_on_provided_property_path_on_variant_only_if_product_is_not_writable(
        PropertyAccessorInterface $propertyAccessor,
        ProductVariantInterface $productVariant,
        ProductInterface $product
    ): void {
        $propertyAccessor->isWritable($product, self::PROPERTY_PATH)->willReturn(false);

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE_CODE, [self::NEW_VALUE_DATA]);

        $propertyAccessor->setValue($productVariant, self::PROPERTY_PATH, 'New value')->shouldHaveBeenCalled();
        $propertyAccessor->setValue($product, self::PROPERTY_PATH, 'New value')->shouldNotHaveBeenCalled();
    }

    public function it_sets_value_on_provided_property_path_on_product_only_if_variant_is_not_writable(
        PropertyAccessorInterface $propertyAccessor,
        ProductVariantInterface $productVariant,
        ProductInterface $product
    ): void {
        $propertyAccessor->isWritable($productVariant, self::PROPERTY_PATH)->willReturn(false);

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE_CODE, [self::NEW_VALUE_DATA]);

        $propertyAccessor->setValue($productVariant, self::PROPERTY_PATH, 'New value')->shouldNotHaveBeenCalled();
        $propertyAccessor->setValue($product, self::PROPERTY_PATH, 'New value')->shouldHaveBeenCalled();
    }

    public function it_throws_if_provided_property_path_is_not_writeable_on_both_product_and_variant(
        PropertyAccessorInterface $propertyAccessor,
        ProductVariantInterface $productVariant,
        ProductInterface $product
    ): void {
        $propertyAccessor->isWritable($productVariant, self::PROPERTY_PATH)->willReturn(false);
        $propertyAccessor->isWritable($product, self::PROPERTY_PATH)->willReturn(false);

        $this
            ->shouldThrow(
                new \RuntimeException(
                    sprintf(
                        'Property path "%s" is not writable on both %s and %s but it should be for at least once.',
                        self::PROPERTY_PATH,
                        get_class($productVariant->getWrappedObject()),
                        get_class($product->getWrappedObject())
                    )
                )
            )
            ->during(
                'handle',
                [
                    $productVariant,
                    self::AKENEO_ATTRIBUTE_CODE,
                    [self::NEW_VALUE_DATA],
                ]
            );
    }

    public function it_skips_values_related_to_channels_that_are_not_associated_to_the_product(
        PropertyAccessorInterface $propertyAccessor,
        ProductVariantInterface $productVariant,
        ProductInterface $product
    ): void {
        $value = [
            [
                'scope' => 'print',
                'locale' => 'en_US',
                'data' => 'New value other',
            ],
            [
                'scope' => 'ecommerce',
                'locale' => 'en_US',
                'data' => 'New value commerce',
            ],
            [
                'scope' => 'paper_catalog',
                'locale' => 'en_US',
                'data' => 'New value other',
            ],
        ];

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE_CODE, $value);

        $propertyAccessor->setValue($productVariant, self::PROPERTY_PATH, 'New value commerce')->shouldHaveBeenCalled();
        $propertyAccessor->setValue($product, self::PROPERTY_PATH, 'New value commerce')->shouldHaveBeenCalled();
        $propertyAccessor->setValue($productVariant, self::PROPERTY_PATH, 'New value other')->shouldNotHaveBeenCalled();
        $propertyAccessor->setValue($product, self::PROPERTY_PATH, 'New value other')->shouldNotHaveBeenCalled();
    }

    public function it_skips_subsequent_values_after_that_one_has_already_been_set_successfully(
        PropertyAccessorInterface $propertyAccessor,
        ProductVariantInterface $productVariant,
        ProductInterface $product
    ): void {
        $value = [
            [
                'scope' => 'ecommerce',
                'locale' => 'en_US',
                'data' => 'New value commerce',
            ],
            [
                'scope' => 'ecommerce',
                'locale' => 'it_IT',
                'data' => 'New value other',
            ],
        ];

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE_CODE, $value);

        $propertyAccessor->setValue($productVariant, self::PROPERTY_PATH, 'New value commerce')->shouldHaveBeenCalled();
        $propertyAccessor->setValue($product, self::PROPERTY_PATH, 'New value commerce')->shouldHaveBeenCalled();
        $propertyAccessor->setValue($productVariant, self::PROPERTY_PATH, 'New value other')->shouldNotHaveBeenCalled();
        $propertyAccessor->setValue($product, self::PROPERTY_PATH, 'New value other')->shouldNotHaveBeenCalled();
    }

    public function it_throws_when_data_doesnt_contain_scope_info(ProductVariantInterface $productVariant): void
    {
        $this
            ->shouldThrow(new \InvalidArgumentException('Invalid Akeneo value data: required "scope" information was not found.',))
            ->during(
                'handle',
                [
                    $productVariant,
                    self::AKENEO_ATTRIBUTE_CODE,
                    [
                        [
                            'locale' => 'en_US',
                            'data' => 'New value commerce',
                        ],
                    ],
                ]
            );
    }
}
