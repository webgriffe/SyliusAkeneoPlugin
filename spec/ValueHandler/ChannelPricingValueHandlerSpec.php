<?php

declare(strict_types=1);

namespace spec\Webgriffe\SyliusAkeneoPlugin\ValueHandler;

use Doctrine\Common\Collections\ArrayCollection;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Currency\Model\CurrencyInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Webgriffe\SyliusAkeneoPlugin\ValueHandler\ChannelPricingValueHandler;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface;

class ChannelPricingValueHandlerSpec extends ObjectBehavior
{
    private const AKENEO_ATTRIBUTE = 'akeneo_attribute';

    private const ITALY_CHANNEL_CODE = 'ITALY';

    private const US_CHANNEL_CODE = 'US';

    function let(
        FactoryInterface $channelPricingFactory,
        ChannelRepositoryInterface $channelRepository,
        CurrencyInterface $eurCurrency,
        ChannelInterface $italyChannel,
        CurrencyInterface $usdCurrency,
        ChannelInterface $usChannel,
        RepositoryInterface $currencyRepository,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $channelRepository
            ->findBy(['baseCurrency' => $eurCurrency])
            ->willReturn(new ArrayCollection([$italyChannel->getWrappedObject()]));
        $channelRepository
            ->findBy(['baseCurrency' => $usdCurrency])
            ->willReturn(new ArrayCollection([$usChannel->getWrappedObject()]));
        $italyChannel->getCode()->willReturn(self::ITALY_CHANNEL_CODE);
        $usChannel->getCode()->willReturn(self::US_CHANNEL_CODE);

        $this->beConstructedWith(
            $channelPricingFactory,
            $channelRepository,
            $currencyRepository,
            $propertyAccessor,
            self::AKENEO_ATTRIBUTE
        );
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(ChannelPricingValueHandler::class);
    }

    function it_implements_value_handler_interface()
    {
        $this->shouldHaveType(ValueHandlerInterface::class);
    }

    function it_supports_product_variant_as_subject(ProductVariantInterface $productVariant)
    {
        $this->supports($productVariant, self::AKENEO_ATTRIBUTE, [])->shouldReturn(true);
    }

    function it_does_not_support_other_type_of_subject()
    {
        $this->supports(new \stdClass(), self::AKENEO_ATTRIBUTE, [])->shouldReturn(false);
    }

    function it_supports_provided_akeneo_attribute(ProductVariantInterface $productVariant)
    {
        $this->supports($productVariant, self::AKENEO_ATTRIBUTE, [])->shouldReturn(true);
    }

    function it_does_not_support_any_other_akeneo_attribute(ProductVariantInterface $productVariant)
    {
        $this->supports($productVariant, 'other_attribute', [])->shouldReturn(false);
    }

    function it_throws_exception_during_handle_when_subject_is_not_product_variant()
    {
        $this
            ->shouldThrow(
                new \InvalidArgumentException(
                    sprintf(
                        'This channel pricing value handler only supports instances of %s, %s given.',
                        ProductVariantInterface::class,
                        \stdClass::class
                    )
                )
            )
            ->during('handle', [new \stdClass(), self::AKENEO_ATTRIBUTE, []]);
    }

    function it_does_nothing_when_currency_does_not_exists(
        ProductVariantInterface $productVariant,
        FactoryInterface $channelPricingFactory,
        RepositoryInterface $currencyRepository
    ) {
        $value = [
            [
                'locale' => null,
                'scope' => null,
                'data' => [
                    [
                        'amount' => '29.99',
                        'currency' => 'EUR',
                    ],
                    [
                        'amount' => '31.99',
                        'currency' => 'USD',
                    ],
                ],
            ],
        ];
        /** @noinspection PhpParamsInspection */
        $currencyRepository->findOneBy(Argument::any())->willReturn(null);

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE, $value);

        $channelPricingFactory->createNew()->shouldNotHaveBeenCalled();
    }

    function it_creates_new_channel_prices_for_the_matching_currency_channels(
        ProductVariantInterface $productVariant,
        ChannelPricingInterface $italianChannelPricing,
        ChannelPricingInterface $usChannelPricing,
        FactoryInterface $channelPricingFactory,
        CurrencyInterface $eurCurrency,
        CurrencyInterface $usdCurrency,
        RepositoryInterface $currencyRepository,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $value = [
            [
                'locale' => null,
                'scope' => null,
                'data' => [
                    [
                        'amount' => '29.99',
                        'currency' => 'EUR',
                    ],
                    [
                        'amount' => '31.99',
                        'currency' => 'USD',
                    ],
                ],
            ],
        ];
        $currencyRepository->findOneBy(['code' => 'EUR'])->willReturn($eurCurrency);
        $currencyRepository->findOneBy(['code' => 'USD'])->willReturn($usdCurrency);
        $channelPricingFactory->createNew()->willReturn($italianChannelPricing, $usChannelPricing);
        /** @noinspection PhpParamsInspection */
        $productVariant->getChannelPricingForChannel(Argument::any())->willReturn(null);
        $propertyAccessor->isWritable($italianChannelPricing, 'price')->willReturn(true);
        $propertyAccessor->isWritable($usChannelPricing, 'price')->willReturn(true);

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE, $value);

        $channelPricingFactory->createNew()->shouldHaveBeenCalledTimes(2);
        $productVariant->addChannelPricing($italianChannelPricing)->shouldHaveBeenCalled();
        $productVariant->addChannelPricing($usChannelPricing)->shouldHaveBeenCalled();
        $propertyAccessor->setValue($italianChannelPricing, 'price', 2999)->shouldHaveBeenCalled();
        $propertyAccessor->setValue($italianChannelPricing, 'original_price', 2999)->shouldNotHaveBeenCalled();
        $italianChannelPricing->setChannelCode(self::ITALY_CHANNEL_CODE)->shouldHaveBeenCalled();
        $propertyAccessor->setValue($usChannelPricing, 'price', 3199)->shouldHaveBeenCalled();
        $propertyAccessor->setValue($usChannelPricing, 'original_price', 3199)->shouldNotHaveBeenCalled();
        $usChannelPricing->setChannelCode(self::US_CHANNEL_CODE)->shouldHaveBeenCalled();
    }

    function it_updates_existent_channel_prices_for_the_matching_currency_channels(
        ProductVariantInterface $productVariant,
        ChannelPricingInterface $italianChannelPricing,
        ChannelPricingInterface $usChannelPricing,
        FactoryInterface $channelPricingFactory,
        CurrencyInterface $eurCurrency,
        CurrencyInterface $usdCurrency,
        ChannelInterface $italyChannel,
        ChannelInterface $usChannel,
        RepositoryInterface $currencyRepository,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $value = [
            [
                'locale' => null,
                'scope' => null,
                'data' => [
                    [
                        'amount' => '29.99',
                        'currency' => 'EUR',
                    ],
                    [
                        'amount' => '31.99',
                        'currency' => 'USD',
                    ],
                ],
            ],
        ];
        $currencyRepository->findOneBy(['code' => 'EUR'])->willReturn($eurCurrency);
        $currencyRepository->findOneBy(['code' => 'USD'])->willReturn($usdCurrency);
        $productVariant->getChannelPricingForChannel($italyChannel)->willReturn($italianChannelPricing);
        $productVariant->getChannelPricingForChannel($usChannel)->willReturn($usChannelPricing);
        $propertyAccessor->isWritable($italianChannelPricing, 'price')->willReturn(true);
        $propertyAccessor->isWritable($usChannelPricing, 'price')->willReturn(true);

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE, $value);

        $channelPricingFactory->createNew()->shouldNotHaveBeenCalled();
        $propertyAccessor->setValue($italianChannelPricing, 'price', 2999)->shouldHaveBeenCalled();
        $propertyAccessor->setValue($italianChannelPricing, 'original_price', 2999)->shouldNotHaveBeenCalled();
        /** @noinspection PhpStrictTypeCheckingInspection */
        $italianChannelPricing->setChannelCode(Argument::type('string'))->shouldNotHaveBeenCalled();
        $propertyAccessor->setValue($usChannelPricing, 'price', 3199)->shouldHaveBeenCalled();
        $propertyAccessor->setValue($usChannelPricing, 'original_price', 3199)->shouldNotHaveBeenCalled();
        /** @noinspection PhpStrictTypeCheckingInspection */
        $usChannelPricing->setChannelCode(Argument::type('string'))->shouldNotHaveBeenCalled();
    }

    function it_throws_exception_during_handle_when_sylius_property_path_is_not_valid(
        FactoryInterface $channelPricingFactory,
        ChannelRepositoryInterface $channelRepository,
        ProductVariantInterface $productVariant,
        CurrencyInterface $eurCurrency,
        ChannelPricingInterface $italianChannelPricing,
        RepositoryInterface $currencyRepository,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->beConstructedWith(
            $channelPricingFactory,
            $channelRepository,
            $currencyRepository,
            $propertyAccessor,
            self::AKENEO_ATTRIBUTE,
            'fake'
        );

        $value = [
            [
                'locale' => null,
                'scope' => null,
                'data' => [
                    [
                        'amount' => '29.99',
                        'currency' => 'EUR',
                    ],
                ],
            ],
        ];
        $currencyRepository->findOneBy(['code' => 'EUR'])->willReturn($eurCurrency);
        $channelPricingFactory->createNew()->willReturn($italianChannelPricing);
        /** @noinspection PhpParamsInspection */
        $productVariant->getChannelPricingForChannel(Argument::any())->willReturn(null);

        $propertyAccessor->isWritable($italianChannelPricing, 'fake')->willReturn(false);

        $this
            ->shouldThrow(new \RuntimeException(sprintf('Property path "%s" is not writable on %s.', 'fake', get_class($italianChannelPricing->getWrappedObject()))))
            ->during('handle', [$productVariant, self::AKENEO_ATTRIBUTE, $value]);
    }

    function it_creates_new_channel_original_prices_for_the_matching_currency_channels(
        ProductVariantInterface $productVariant,
        ChannelRepositoryInterface $channelRepository,
        ChannelPricingInterface $italianChannelPricing,
        ChannelPricingInterface $usChannelPricing,
        FactoryInterface $channelPricingFactory,
        CurrencyInterface $eurCurrency,
        CurrencyInterface $usdCurrency,
        RepositoryInterface $currencyRepository,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->beConstructedWith(
            $channelPricingFactory,
            $channelRepository,
            $currencyRepository,
            $propertyAccessor,
            self::AKENEO_ATTRIBUTE,
            'original_price'
        );

        $value = [
            [
                'locale' => null,
                'scope' => null,
                'data' => [
                    [
                        'amount' => '29.99',
                        'currency' => 'EUR',
                    ],
                    [
                        'amount' => '31.99',
                        'currency' => 'USD',
                    ],
                ],
            ],
        ];
        $currencyRepository->findOneBy(['code' => 'EUR'])->willReturn($eurCurrency);
        $currencyRepository->findOneBy(['code' => 'USD'])->willReturn($usdCurrency);
        $channelPricingFactory->createNew()->willReturn($italianChannelPricing, $usChannelPricing);
        /** @noinspection PhpParamsInspection */
        $productVariant->getChannelPricingForChannel(Argument::any())->willReturn(null);
        $propertyAccessor->isWritable($italianChannelPricing, 'original_price')->willReturn(true);
        $propertyAccessor->isWritable($usChannelPricing, 'original_price')->willReturn(true);

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE, $value);

        $channelPricingFactory->createNew()->shouldHaveBeenCalledTimes(2);
        $productVariant->addChannelPricing($italianChannelPricing)->shouldHaveBeenCalled();
        $productVariant->addChannelPricing($usChannelPricing)->shouldHaveBeenCalled();
        $propertyAccessor->setValue($italianChannelPricing, 'original_price', 2999)->shouldHaveBeenCalled();
        $propertyAccessor->setValue($italianChannelPricing, 'price', 2999)->shouldNotHaveBeenCalled();
        $italianChannelPricing->setChannelCode(self::ITALY_CHANNEL_CODE)->shouldHaveBeenCalled();
        $propertyAccessor->setValue($usChannelPricing, 'original_price', 3199)->shouldHaveBeenCalled();
        $propertyAccessor->setValue($usChannelPricing, 'price', 3199)->shouldNotHaveBeenCalled();
        $usChannelPricing->setChannelCode(self::US_CHANNEL_CODE)->shouldHaveBeenCalled();
    }

    function it_updates_existent_channel_original_prices_for_the_matching_currency_channels(
        ProductVariantInterface $productVariant,
        ChannelRepositoryInterface $channelRepository,
        ChannelPricingInterface $italianChannelPricing,
        ChannelPricingInterface $usChannelPricing,
        FactoryInterface $channelPricingFactory,
        CurrencyInterface $eurCurrency,
        CurrencyInterface $usdCurrency,
        ChannelInterface $italyChannel,
        ChannelInterface $usChannel,
        RepositoryInterface $currencyRepository,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->beConstructedWith(
            $channelPricingFactory,
            $channelRepository,
            $currencyRepository,
            $propertyAccessor,
            self::AKENEO_ATTRIBUTE,
            'original_price'
        );

        $value = [
            [
                'locale' => null,
                'scope' => null,
                'data' => [
                    [
                        'amount' => '29.99',
                        'currency' => 'EUR',
                    ],
                    [
                        'amount' => '31.99',
                        'currency' => 'USD',
                    ],
                ],
            ],
        ];
        $currencyRepository->findOneBy(['code' => 'EUR'])->willReturn($eurCurrency);
        $currencyRepository->findOneBy(['code' => 'USD'])->willReturn($usdCurrency);
        $productVariant->getChannelPricingForChannel($italyChannel)->willReturn($italianChannelPricing);
        $productVariant->getChannelPricingForChannel($usChannel)->willReturn($usChannelPricing);

        $propertyAccessor->isWritable($italianChannelPricing, 'original_price')->willReturn(true);
        $propertyAccessor->isWritable($usChannelPricing, 'original_price')->willReturn(true);

        $this->handle($productVariant, self::AKENEO_ATTRIBUTE, $value);

        $channelPricingFactory->createNew()->shouldNotHaveBeenCalled();
        $propertyAccessor->setValue($italianChannelPricing, 'original_price', 2999)->shouldHaveBeenCalled();
        $propertyAccessor->setValue($italianChannelPricing, 'price', 2999)->shouldNotHaveBeenCalled();
        /** @noinspection PhpStrictTypeCheckingInspection */
        $italianChannelPricing->setChannelCode(Argument::type('string'))->shouldNotHaveBeenCalled();
        $propertyAccessor->setValue($usChannelPricing, 'original_price', 3199)->shouldHaveBeenCalled();
        $propertyAccessor->setValue($usChannelPricing, 'price', 3199)->shouldNotHaveBeenCalled();
        /** @noinspection PhpStrictTypeCheckingInspection */
        $usChannelPricing->setChannelCode(Argument::type('string'))->shouldNotHaveBeenCalled();
    }
}
